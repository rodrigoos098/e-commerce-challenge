<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogsActivity
{
    /**
     * Log a structured activity message to a specific channel.
     *
     * @param array<string, mixed> $context
     */
    protected function logActivity(string $channel, string $message, array $context = []): void
    {
        Log::channel($channel)->info($message, array_merge($context, [
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log an error to a specific channel.
     *
     * @param array<string, mixed> $context
     */
    protected function logError(string $channel, string $message, array $context = []): void
    {
        Log::channel($channel)->error($message, array_merge($context, [
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log a warning to a specific channel.
     *
     * @param array<string, mixed> $context
     */
    protected function logWarning(string $channel, string $message, array $context = []): void
    {
        Log::channel($channel)->warning($message, array_merge($context, [
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}
