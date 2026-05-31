<?php

namespace App\Http\Controllers\Api;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\DocumentSubmissionResource;
use App\Jobs\ProcessDocumentJob;
use App\Models\AuditLog;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\ValidationJob;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentSubmissionController extends Controller
{
    public function __construct(
        private readonly GoogleDriveService $driveService,
    ) {}

    /**
     * List document submissions for the authenticated coordinator (paginated, filterable).
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user  = auth()->user();
        $query = DocumentSubmission::with(['documentType', 'subject', 'period', 'validationResult']);

        // Admin sees all; coordinator sees only their own
        if ($user->role?->name !== 'Administrador') {
            $query->byCoordinator($user->id);
        }

        // Optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('period_id')) {
            $query->where('period_id', $request->period_id);
        }
        if ($request->filled('document_type_id')) {
            $query->where('document_type_id', $request->document_type_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $submissions = $query->latest()->paginate(15);

        return response()->json([
            'data'    => DocumentSubmissionResource::collection($submissions),
            'meta'    => [
                'current_page' => $submissions->currentPage(),
                'last_page'    => $submissions->lastPage(),
                'per_page'     => $submissions->perPage(),
                'total'        => $submissions->total(),
            ],
            'message' => 'Lista de envíos obtenida correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Upload and queue a new document submission for processing.
     *
     * @param  StoreSubmissionRequest $request
     * @return JsonResponse
     */
    public function store(StoreSubmissionRequest $request): JsonResponse
    {
        $user = auth()->user();

        // ── Pre-validation 1: Only one active review per coordinator ──────────
        if (DocumentSubmission::hasActiveReview($user->id)) {
            return response()->json([
                'error'   => 'active_review_exists',
                'message' => 'Ya tienes una revisión en proceso. Espera a que finalice antes de enviar un nuevo documento.',
                'status'  => 409,
            ], 409);
        }

        // ── Pre-validation 2: Extension allowed by document type ──────────────
        $documentType = DocumentType::findOrFail($request->document_type_id);
        $file         = $request->file('file');
        $extension    = strtolower($file->getClientOriginalExtension());

        if (!$documentType->allowsExtension($extension)) {
            return response()->json([
                'error'   => 'extension_not_allowed',
                'message' => "Tipo de archivo '.{$extension}' no permitido para el tipo de documento '{$documentType->name}'. Permitidos: " . implode(', ', $documentType->allowed_extensions),
                'status'  => 422,
            ], 422);
        }

        // ── Pre-validation 3: File size check ─────────────────────────────────
        $maxBytes = $documentType->max_size_mb * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            return response()->json([
                'error'   => 'file_too_large',
                'message' => "El archivo supera el tamaño máximo permitido de {$documentType->max_size_mb} MB.",
                'status'  => 422,
            ], 422);
        }

        // ── Pre-validation 4: Duplicate SHA256 check ──────────────────────────
        $fileHash = hash_file('sha256', $file->getRealPath());
        //if (DocumentSubmission::where('file_hash', $fileHash)->exists()) {
        //    return response()->json([
        //        'error'   => 'duplicate_document',
        //        'message' => 'Este documento ya fue enviado anteriormente. El contenido es idéntico al de una entrega previa.',
        //        'status'  => 409,
        //    ], 409);
        //}

        // ── Transactional creation ─────────────────────────────────────────────
        $submission = DB::transaction(function () use ($request, $user, $documentType, $file, $fileHash, $extension) {
            $uuid = (string) Str::uuid();

            // 1. Store the file
            $storedFilename = $uuid . '.' . $extension;
            $storedPath     = "submissions/{$user->id}/{$uuid}/{$storedFilename}";
            Storage::putFileAs("submissions/{$user->id}/{$uuid}", $file, $storedFilename);

            // 2. Create the submission record
            $submission = DocumentSubmission::create([
                'uuid'              => $uuid,
                'user_id'           => $user->id,
                'document_type_id'  => $documentType->id,
                'subject_id'        => $request->subject_id,
                'period_id'         => $request->period_id,
                'original_filename' => $file->getClientOriginalName(),
                'stored_filename'   => $storedFilename,
                'file_path'         => $storedPath,
                'file_size_bytes'   => $file->getSize(),
                'mime_type'         => $file->getMimeType(),
                'file_hash'         => $fileHash,
                'status'            => SubmissionStatus::PENDING,
            ]);

            // 3. Create validation job record
            ValidationJob::create([
                'document_submission_id' => $submission->id,
                'status'                 => 'queued',
                'attempts'               => 0,
            ]);

            // 4. Dispatch processing job
            $dispatchedJob = ProcessDocumentJob::dispatch($submission)->onQueue('documents');

            // 5. Audit log
            AuditLog::log(
                'submission.created',
                'DocumentSubmission',
                $submission->id,
                null,
                [
                    'uuid'             => $uuid,
                    'document_type'    => $documentType->slug,
                    'original_filename' => $file->getClientOriginalName(),
                ]
            );

            return $submission;
        });

        $submission->loadMissing(['documentType', 'subject', 'period', 'validationJob']);

        return response()->json([
            'data'    => new DocumentSubmissionResource($submission),
            'message' => 'Documento enviado correctamente. El análisis comenzará en breve.',
            'status'  => 201,
        ], 201);
    }

    /**
     * Get full details of a single submission.
     *
     * @param  string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $user       = auth()->user();
        $submission = DocumentSubmission::where('uuid', $uuid)
            ->with([
                'documentType',
                'subject',
                'period',
                'user',
                'ocrResult',
                'aiAnalysis',
                'validationResult',
                'validationJob',
                'driveStorage',
            ])
            ->firstOrFail();

        // Coordinators can only view their own submissions; admins see all
        if ($user->role?->name !== 'Administrador' && $submission->user_id !== $user->id) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => 'No tienes permiso para ver este recurso.',
                'status'  => 403,
            ], 403);
        }

        return response()->json([
            'data'    => new DocumentSubmissionResource($submission),
            'message' => 'Detalle de envío obtenido correctamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Confirm/approve a submission that passed validation, triggering Google Drive upload.
     *
     * @param  string $uuid
     * @return JsonResponse
     */
    public function confirm(string $uuid): JsonResponse
    {
        $user       = auth()->user();
        $submission = DocumentSubmission::where('uuid', $uuid)->firstOrFail();

        // Only the submission owner (or admin) can confirm
        if ($user->role?->name !== 'Administrador' && $submission->user_id !== $user->id) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => 'No tienes permiso para confirmar este envío.',
                'status'  => 403,
            ], 403);
        }

        // Must be in pending_approval status
        if ($submission->status !== SubmissionStatus::PENDING_APPROVAL) {
            return response()->json([
                'error'   => 'invalid_status',
                'message' => 'Solo se pueden aprobar envíos en estado "pending_approval". Estado actual: ' . ($submission->status instanceof SubmissionStatus ? $submission->status->value : $submission->status),
                'status'  => 422,
            ], 422);
        }

        $updatedSubmission = DB::transaction(function () use ($submission, $user) {
            // 1. Upload to Google Drive
            $driveData = $this->driveService->upload($submission);

            // 2. Create drive_storage record
            \App\Models\DriveStorage::create(array_merge(
                ['document_submission_id' => $submission->id],
                $driveData,
            ));

            // 3. Update submission status to approved
            $submission->update([
                'status'      => SubmissionStatus::APPROVED,
                'reviewer_id' => $user->id,
                'reviewed_at' => now(),
            ]);

            // 4. Audit log
            AuditLog::log(
                'submission.approved',
                'DocumentSubmission',
                $submission->id,
                ['status' => SubmissionStatus::PENDING_APPROVAL->value],
                ['status' => SubmissionStatus::APPROVED->value, 'drive_url' => $driveData['drive_url']]
            );

            return $submission->fresh([
                'documentType', 'subject', 'period', 'user',
                'validationResult', 'validationJob', 'driveStorage',
            ]);
        });

        return response()->json([
            'data'    => new DocumentSubmissionResource($updatedSubmission),
            'message' => 'Documento aprobado y subido a Google Drive exitosamente.',
            'status'  => 200,
        ]);
    }

    /**
     * Cancel and soft-delete a pending submission.
     *
     * @param  string $uuid
     * @return JsonResponse
     */
    public function destroy(string $uuid): JsonResponse
    {
        $user       = auth()->user();
        $submission = DocumentSubmission::where('uuid', $uuid)->firstOrFail();

        // Only the submission owner (or admin) can cancel
        if ($user->role?->name !== 'Administrador' && $submission->user_id !== $user->id) {
            return response()->json([
                'error'   => 'forbidden',
                'message' => 'No tienes permiso para cancelar este envío.',
                'status'  => 403,
            ], 403);
        }

        // Only 'pending' submissions can be cancelled
        if ($submission->status !== SubmissionStatus::PENDING) {
            return response()->json([
                'error'   => 'invalid_status',
                'message' => 'Solo se pueden cancelar envíos en estado "pending".',
                'status'  => 422,
            ], 422);
        }

        // Audit log before deletion
        AuditLog::log(
            'submission.cancelled',
            'DocumentSubmission',
            $submission->id,
            ['status' => $submission->status instanceof SubmissionStatus ? $submission->status->value : $submission->status],
            ['status' => 'cancelled']
        );

        // Remove the physical file from storage
        if (Storage::exists($submission->file_path)) {
            Storage::deleteDirectory(dirname($submission->file_path));
        }

        // Soft delete
        $submission->delete();

        return response()->json([
            'message' => 'Envío cancelado correctamente.',
            'status'  => 200,
        ]);
    }
}
