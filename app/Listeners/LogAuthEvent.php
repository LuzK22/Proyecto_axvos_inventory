<?php

namespace App\Listeners;

use App\Models\AccessLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthEvent
{
    public function handleLogin(Login $event): void
    {
        AccessLog::recordLogin($event->user, session()->getId());
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            AccessLog::recordLogout($event->user);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? ($event->credentials['username'] ?? 'unknown');
        AccessLog::recordFailedLogin($email);
    }
}
