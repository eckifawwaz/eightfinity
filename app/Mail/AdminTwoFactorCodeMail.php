<?php

namespace App\Mail;

use App\Models\User;
use App\Support\PortalUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminTwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Eightfinity Admin Two-Factor Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-two-factor-code',
            with: [
                'verifyUrl' => PortalUrl::to('admin', '/admin/two-factor'),
            ],
        );
    }
}
