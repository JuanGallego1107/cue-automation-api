<?php

namespace App\Jobs;

use App\Enums\SubmissionStatus;
use App\Mail\CoordinatorApprovalMail;
use App\Mail\TeacherNotificationMail;
use App\Models\AiAnalysis;
use App\Models\AuditLog;
use App\Models\DocumentSubmission;
use App\Models\OcrResult;
use App\Models\ValidationJob;
use App\Models\ValidationResult;
use App\Services\GoogleAIStudioValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of automatic retry attempts.
     */
    public int $tries = 0;

    /**
     * Backoff times in seconds between each retry.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly DocumentSubmission $submission,
    ) {}

    /**
     * Execute the job.
     *
     * Flow:
     *  1. Mark submission as 'processing'.
     *  2. Mark validation job as 'running'.
     *  3. Call Azure Document Intelligence for OCR.
     *  4. Save OcrResult.
     *  5. Call Anthropic for validation.
     *  6. Save AiAnalysis + ValidationResult.
     *  7a. Issues found → status 'issues_found', email teacher.
     *  7b. No issues   → status 'pending_approval', email coordinator.
     *  8. Mark validation job 'completed'.
     */
    public function handle(
        // AzureDocumentIntelligenceService $azureService,
        GoogleAIStudioValidationService $googleAIStudioValidationService,
    ): void {
        Log::channel('daily')->info('[ProcessDocumentJob] Starting', [
            'submission_id' => $this->submission->id,
            'uuid'          => $this->submission->uuid,
        ]);

        $startedAt = now();

        // ── Step 1: Mark submission as processing ─────────────────────────────
        $this->submission->update(['status' => SubmissionStatus::PROCESSING]);

        // ── Step 2: Mark validation job as running ────────────────────────────
        $validationJob = ValidationJob::where('document_submission_id', $this->submission->id)
            ->latest()
            ->first();

        if ($validationJob) {
            $validationJob->update([
                'status'     => 'running',
                'started_at' => $startedAt,
                'attempts'   => $validationJob->attempts + 1,
            ]);
        }

        // ── Step 3: Azure OCR ─────────────────────────────────────────────────
        $this->submission->loadMissing('documentType');

        $ocrStartTime = microtime(true);
        // $azureResult  = $azureService->analyze(
        //     $this->submission->file_path,
        //     $this->submission->mime_type,
        // );
        
        // --- BYPASS AZURE OCR ---
        $fileContents = \Illuminate\Support\Facades\Storage::get($this->submission->file_path);
        $base64Pdf = base64_encode($fileContents);
        $azureResult = [
            'status' => 'bypassed',
            'base64_pdf' => $base64Pdf,
            'mime_type' => $this->submission->mime_type,
        ];
        // ------------------------

        $ocrMs = (int) ((microtime(true) - $ocrStartTime) * 1000);

        Log::channel('daily')->info('[ProcessDocumentJob] OCR completed', [
            'submission_id'   => $this->submission->id,
            'processing_time' => $ocrMs,
        ]);

        // ── Step 4: AI Validation ───────────────────────────────────
        $aiStartTime  = microtime(true);
        $aiResult     = $googleAIStudioValidationService->validate($azureResult, $this->submission->documentType);
        $aiMs         = (int) ((microtime(true) - $aiStartTime) * 1000);

        Log::channel('daily')->info('[ProcessDocumentJob] AI validation completed', [
            'submission_id' => $this->submission->id,
            'is_valid'      => $aiResult['is_valid'],
        ]);

        // ── Step 5a: Save AiAnalysis ──────────────────────────────────────────
        AiAnalysis::create([
            'document_submission_id' => $this->submission->id,
            'model_used'             => $aiResult['_model'] ?? config('services.google_ai_studio.model'),
            'analysis_type'          => 'validation',
            'result'                 => $aiResult['is_valid'] ? 'pass' : 'fail',
            'summary'                => $aiResult['summary'] ?? null,
            'findings'               => $aiResult['issues'] ?? [],
            'anomalies'              => $aiResult['inconsistencies'] ?? [],
            'recommendations'        => $aiResult['recommendations'] ?? null,
            'confidence_score'       => $aiResult['confidence_score'] ?? null,
            'tokens_used'            => ($aiResult['_tokens_input'] ?? 0) + ($aiResult['_tokens_output'] ?? 0),
            'processing_time_ms'     => $aiMs,
            'created_at'             => now(),
        ]);

        // ── Step 5b: Save ValidationResult ────────────────────────────────────
        $issues          = $aiResult['issues'] ?? [];
        $checksPassed    = $aiResult['checks_passed'] ?? [];
        $checksFailed    = $aiResult['checks_failed'] ?? [];
        $checksPerformed = $aiResult['checks_performed'] ?? [];
        $totalChecks     = count($checksPerformed);
        $score           = $totalChecks > 0
            ? round((count($checksPassed) / $totalChecks) * 100, 2)
            : null;

        ValidationResult::create([
            'document_submission_id' => $this->submission->id,
            'passed'                 => $aiResult['is_valid'] ? 1 : 0,
            'score'                  => $score,
            'checks_performed'       => $checksPerformed,
            'checks_passed'          => $checksPassed,
            'checks_failed'          => $checksFailed,
            'inconsistencies'        => $aiResult['inconsistencies'] ?? [],
            'recommendations'        => $aiResult['recommendations'] ?? null,
            'processing_time_ms'     => $ocrMs + $aiMs,
            'created_at'             => now(),
        ]);

        // ── Step 7: Evaluate result and update status ─────────────────────────
        $isValid = (bool) $aiResult['is_valid'];

        if (!$isValid) {
            // 7a — Issues found
            $this->submission->update(['status' => SubmissionStatus::ISSUES_FOUND]);

            // Send notification email to the teacher extracted by the AI
            $teacherEmail = $aiResult['teacher']['email'] ?? null;
            if ($teacherEmail) {
                try {
                    Mail::to($teacherEmail)->send(
                        new TeacherNotificationMail($this->submission, $aiResult)
                    );
                } catch (Throwable $e) {
                    Log::channel('daily')->warning('[ProcessDocumentJob] Could not send teacher email', [
                        'submission_id' => $this->submission->id,
                        'email'         => $teacherEmail,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }

            // Record in notifications table
            \DB::table('notifications')->insert([
                'id'               => (string) \Illuminate\Support\Str::uuid(),
                'type'             => TeacherNotificationMail::class,
                'notifiable_type'  => DocumentSubmission::class,
                'notifiable_id'    => $this->submission->id,
                'data'             => json_encode([
                    'submission_uuid' => $this->submission->uuid,
                    'teacher_email'   => $teacherEmail,
                    'summary'         => $aiResult['summary'] ?? '',
                ]),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            AuditLog::create([
                'user_id'     => $this->submission->user_id,
                'action'      => 'submission.issues_found',
                'entity_type' => 'DocumentSubmission',
                'entity_id'   => $this->submission->id,
                'new_values'  => [
                    'status'        => SubmissionStatus::ISSUES_FOUND->value,
                    'teacher_email' => $teacherEmail,
                    'issues_count'  => count($issues),
                ],
                'ip_address'  => null,
                'user_agent'  => 'Queue Worker',
                'created_at'  => now(),
            ]);
        } else {
            // 7b — No critical issues, awaiting coordinator confirmation
            $this->submission->update(['status' => SubmissionStatus::PENDING_APPROVAL]);

            // Load coordinator user relation
            $this->submission->loadMissing('user');

            try {
                Mail::to($this->submission->user->email)->send(
                    new CoordinatorApprovalMail($this->submission)
                );
            } catch (Throwable $e) {
                Log::channel('daily')->warning('[ProcessDocumentJob] Could not send coordinator email', [
                    'submission_id' => $this->submission->id,
                    'error'         => $e->getMessage(),
                ]);
            }

            AuditLog::create([
                'user_id'     => $this->submission->user_id,
                'action'      => 'submission.pending_approval',
                'entity_type' => 'DocumentSubmission',
                'entity_id'   => $this->submission->id,
                'new_values'  => ['status' => SubmissionStatus::PENDING_APPROVAL->value],
                'ip_address'  => null,
                'user_agent'  => 'Queue Worker',
                'created_at'  => now(),
            ]);
        }

        // ── Step 8: Mark validation job as completed ──────────────────────────
        if ($validationJob) {
            $validationJob->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        }

        Log::channel('daily')->info('[ProcessDocumentJob] Finished', [
            'submission_id' => $this->submission->id,
            'status'        => $this->submission->status,
        ]);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(Throwable $e): void
    {
        Log::channel('daily')->error('[ProcessDocumentJob] Failed', [
            'submission_id' => $this->submission->id,
            'error'         => $e->getMessage(),
            'trace'         => array_slice($e->getTrace(), 0, 5),
        ]);

        // Update submission to 'failed'
        $this->submission->update([
            'status'        => SubmissionStatus::FAILED,
            'retry_count'   => $this->submission->retry_count + 1,
            'last_retry_at' => now(),
        ]);

        // Update validation job to 'failed'
        ValidationJob::where('document_submission_id', $this->submission->id)
            ->latest()
            ->first()
            ?->update(['status' => 'failed', 'completed_at' => now()]);

        // Audit log the failure
        AuditLog::create([
            'user_id'     => $this->submission->user_id,
            'action'      => 'submission.failed',
            'entity_type' => 'DocumentSubmission',
            'entity_id'   => $this->submission->id,
            'new_values'  => [
                'status'    => SubmissionStatus::FAILED->value,
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ],
            'ip_address'  => null,
            'user_agent'  => 'Queue Worker',
            'created_at'  => now(),
        ]);
    }

    /**
     * Extract average confidence from Azure result pages.
     */
    private function extractConfidence(array $azureResult): ?float
    {
        $pages = $azureResult['pages'] ?? [];
        if (empty($pages)) {
            return null;
        }

        $total = 0;
        $count = 0;

        foreach ($pages as $page) {
            foreach ($page['words'] ?? [] as $word) {
                if (isset($word['confidence'])) {
                    $total += (float) $word['confidence'];
                    $count++;
                }
            }
        }

        return $count > 0 ? round($total / $count, 4) : null;
    }
}
