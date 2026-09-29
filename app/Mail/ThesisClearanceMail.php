<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ThesisClearanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Document $document;
    public string $type;
    public ?string $similarity;
    public ?string $notes;
    public string $subjectTitle;

    /**
     * Create a new message instance.
     */
    public function __construct(Document $document, string $type = 'passed', ?string $similarity = null, ?string $notes = null)
    {
        $this->document = $document;
        $this->type = strtolower($type);
        $this->similarity = $similarity ?: $document->turnitin_similarity;
        $this->notes = $notes ?: $document->admin_notes;

        $shortTitle = Str::limit($document->title, 45);

        if ($this->type === 'passed') {
            $this->subjectTitle = "🎉 [SAC Clearance Passed] Turnitin & Grammarly Review: {$shortTitle}";
        } elseif ($this->type === 'resubmit') {
            $this->subjectTitle = "⚠️ [SAC Action Required] Turnitin Revisions Needed: {$shortTitle}";
        } else {
            $this->subjectTitle = "📄 [SAC Clearance] Manuscript Queued for Screening: {$shortTitle}";
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectTitle,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.thesis_clearance',
            with: [
                'document' => $this->document,
                'type' => $this->type,
                'similarity' => $this->similarity,
                'notes' => $this->notes,
                'subjectTitle' => $this->subjectTitle,
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
