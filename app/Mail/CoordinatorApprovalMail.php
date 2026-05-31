<?php

namespace App\Mail;

use App\Models\DocumentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CoordinatorApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  DocumentSubmission $submission  The submission ready for coordinator approval
     */
    public function __construct(
        public readonly DocumentSubmission $submission,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Documento listo para aprobación: {$this->submission->original_filename}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.coordinator-approval',
            with: [
                'submission'    => $this->submission,
                'approvalUrl'   => config('app.frontend_url') . '/submissions/' . $this->submission->uuid,
                'coordinatorName' => $this->submission->user?->name ?? 'Coordinador',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
