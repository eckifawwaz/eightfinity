<?php

namespace App\Mail;

use App\Models\User;
use App\Support\PortalUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserTwoFactorCodeMail extends Mailable
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
            subject: 'Eightfinity Two-Factor Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-two-factor-code',
            with: [
                'verifyUrl' => PortalUrl::to('user', '/two-factor'),
            ],
        );
    }
}
