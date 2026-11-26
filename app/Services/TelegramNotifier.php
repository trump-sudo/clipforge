<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Best-effort notifications via the Telegram Bot API.
 * A failing third-party API must never fail video processing, so errors are logged, not thrown.
 */
class TelegramNotifier
{
    public function enabled(): bool
    {
        return filled(config('clipforge.telegram.bot_token')) && filled(config('clipforge.telegram.chat_id'));
    }

    public function send(string $text): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 300, throw: false)
                ->post(sprintf('https://api.telegram.org/bot%s/sendMessage', config('clipforge.telegram.bot_token')), [
                    'chat_id' => config('clipforge.telegram.chat_id'),
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]);

            if ($response->failed()) {
                Log::warning('Telegram notification failed', ['status' => $response->status()]);
            }

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('Telegram notification error', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
