<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        Log::warning('Failed login attempt', [
            'user' => $event->credentials['email'] ?? 'unknown',
            'ip' => request()->ip(),
        ]);
    }
}
