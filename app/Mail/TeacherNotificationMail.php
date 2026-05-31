<?php

namespace App\Mail;

use App\Models\DocumentSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  DocumentSubmission $submission      The submission that was analyzed
     * @param  array              $validationResult The structured AI validation result
     */
    public function __construct(
        public readonly DocumentSubmission $submission,
        public readonly array $validationResult,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Revisión de documento: {$this->submission->original_filename} — Se requieren correcciones",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.teacher-notification',
            with: [
                'submission'       => $this->submission,
                'validationResult' => $this->validationResult,
                'issues'           => $this->groupIssuesBySeverity($this->validationResult['issues'] ?? []),
                'analyzedAt'       => now()->format('d/m/Y H:i'),
            ],
        );
    }

    /**
     * Group the issues array by severity (critico → advertencia → informativo).
     *
     * @param  array $issues
     * @return array{critico: array, advertencia: array, informativo: array}
     */
    private function groupIssuesBySeverity(array $issues): array
    {
        $grouped = ['critico' => [], 'advertencia' => [], 'informativo' => []];

        foreach ($issues as $issue) {
            $severity = $issue['severity'] ?? 'informativo';
            if (isset($grouped[$severity])) {
                $grouped[$severity][] = $issue;
            } else {
                $grouped['informativo'][] = $issue;
            }
        }

        return $grouped;
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
