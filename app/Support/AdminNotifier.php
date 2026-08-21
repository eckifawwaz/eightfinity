<?php

namespace App\Support;

use App\Mail\AdminSystemNotificationMail;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class AdminNotifier
{
    public function send(string $preferenceKey, Mailable $mail): void
    {
        $this->recipients($preferenceKey)
            ->each(fn (User $admin) => Mail::to($admin)->send(clone $mail));
    }

    public function system(string $preferenceKey, string $title, string $message, ?string $actionUrl = null, ?string $actionLabel = null): void
    {
        $this->send($preferenceKey, new AdminSystemNotificationMail($title, $message, $actionUrl, $actionLabel));
    }

    private function recipients(string $preferenceKey)
    {
        return User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->get()
            ->filter(fn (User $admin) => $admin->adminNotificationEnabled($preferenceKey));
    }
}
