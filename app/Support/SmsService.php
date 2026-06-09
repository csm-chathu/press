<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS via Notify.lk (Sri Lankan SMS gateway).
     * Configure NOTIFY_LK_USER_ID, NOTIFY_LK_API_KEY, NOTIFY_LK_SERVICE_ID in .env.
     * If credentials are missing, the call is silently skipped.
     */
    public static function send(string $to, string $message): void
    {
        $userId    = config('services.notify_lk.user_id');
        $apiKey    = config('services.notify_lk.api_key');
        $serviceId = config('services.notify_lk.service_id');

        if (!$userId || !$apiKey) {
            return; // SMS not configured, skip silently
        }

        // Normalize Sri Lankan phone number to international format
        $to = preg_replace('/[^0-9]/', '', $to);
        if (str_starts_with($to, '0')) {
            $to = '94' . substr($to, 1);
        } elseif (!str_starts_with($to, '94')) {
            $to = '94' . $to;
        }

        try {
            Http::get('https://app.notify.lk/api/v1/send', [
                'user_id'    => $userId,
                'api_key'    => $apiKey,
                'service_id' => $serviceId,
                'to'         => $to,
                'message'    => $message,
            ]);
        } catch (\Throwable $e) {
            Log::warning('SMS send failed: ' . $e->getMessage());
        }
    }
}
