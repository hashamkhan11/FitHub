<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Sends via Twilio once TWILIO_SID/TWILIO_AUTH_TOKEN/TWILIO_FROM_NUMBER are set
 * in .env (the company's paid account isn't plugged in yet). Until then, falls
 * back to logging what would have been sent so callers work unchanged either way.
 */
class SmsService
{
    public function send(string $to, string $message): void
    {
        $sid = config('services.twilio.sid');
        $authToken = config('services.twilio.auth_token');
        $from = config('services.twilio.from');

        if (! $sid || ! $authToken || ! $from) {
            Log::info("[SMS stub] would send to {$to}: {$message}");

            return;
        }

        (new Client($sid, $authToken))->messages->create($to, [
            'from' => $from,
            'body' => $message,
        ]);
    }
}
