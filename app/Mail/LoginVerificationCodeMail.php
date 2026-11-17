<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $email;
    public string $name;

    /**
     * Create a new message instance.
     */
    public function __construct(string $code, string $email, ?string $name = null)
    {
        $this->code = $code;
        $this->email = $email;
        $this->name = $name ?: ucwords(preg_replace('/[._-]+/', ' ', explode('@', $email)[0]));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[AIRIS Security] {$this->code} is your 6-digit login verification code",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.login_verification',
            with: [
                'code' => $this->code,
                'email' => $this->email,
                'name' => $this->name,
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
