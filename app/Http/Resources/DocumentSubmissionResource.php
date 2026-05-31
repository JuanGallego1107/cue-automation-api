<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'status'            => $this->status instanceof \App\Enums\SubmissionStatus
                ? $this->status->value
                : $this->status,
            'original_filename' => $this->original_filename,
            'stored_filename'   => $this->stored_filename,
            'file_size_bytes'   => $this->file_size_bytes,
            'mime_type'         => $this->mime_type,
            'retry_count'       => $this->retry_count,
            'reviewer_notes'    => $this->reviewer_notes,
            'rejection_reason'  => $this->rejection_reason,
            'reviewed_at'       => $this->reviewed_at?->toISOString(),
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),

            // Loaded relations (only included when eager-loaded)
            'document_type' => $this->whenLoaded('documentType', fn() => [
                'id'   => $this->documentType->id,
                'name' => $this->documentType->name,
                'slug' => $this->documentType->slug,
            ]),

            'subject' => $this->whenLoaded('subject', fn() => $this->subject ? [
                'id'   => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ] : null),

            'period' => $this->whenLoaded('period', fn() => $this->period ? [
                'id'         => $this->period->id,
                'name'       => $this->period->name,
                'start_date' => $this->period->start_date?->toDateString(),
                'end_date'   => $this->period->end_date?->toDateString(),
            ] : null),

            'coordinator' => $this->whenLoaded('user', fn() => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),

            'ocr_result' => $this->whenLoaded(
                'ocrResult',
                fn() => new OcrResultResource($this->ocrResult)
            ),

            'ai_analysis' => $this->whenLoaded(
                'aiAnalysis',
                fn() => $this->aiAnalysis ? new AiAnalysisResource($this->aiAnalysis) : null
            ),

            'validation_result' => $this->whenLoaded(
                'validationResult',
                fn() => $this->validationResult ? new ValidationResultResource($this->validationResult) : null
            ),

            'validation_job' => $this->whenLoaded(
                'validationJob',
                fn() => $this->validationJob ? new ValidationJobResource($this->validationJob) : null
            ),

            'drive_storage' => $this->whenLoaded(
                'driveStorage',
                fn() => $this->driveStorage ? new DriveStorageResource($this->driveStorage) : null
            ),
        ];
    }
}
