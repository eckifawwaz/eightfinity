<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\PortalUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminBookingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {
        $this->booking->loadMissing('user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Eightfinity Booking {$this->booking->booking_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-booking-notification',
            with: [
                'adminBookingsUrl' => PortalUrl::to('admin', '/admin/bookings'),
            ],
        );
    }
}
