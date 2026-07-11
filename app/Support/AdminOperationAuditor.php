<?php

namespace App\Support;

use App\Models\AdminOperationAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminOperationAuditor
{
    public static function record(
        string $action,
        ?Model $subject = null,
        array $context = [],
        string $status = 'success',
        ?string $summary = null,
    ): void {
        try {
            if (! Schema::hasTable('admin_operation_audits')) {
                return;
            }

            AdminOperationAudit::query()->create([
                'user_id' => auth()->id(),
                'action' => $action,
                'status' => $status,
                'auditable_type' => $subject ? $subject::class : null,
                'auditable_id' => $subject?->getKey(),
                'summary' => $summary ? Str::limit($summary, 240, '') : null,
                'ip_address' => request()?->ip(),
                'path' => request()?->path(),
                'context' => self::sanitize($context),
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Admin operation audit could not be recorded.', [
                'action' => $action,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private static function sanitize(array $context): array
    {
        return collect($context)
            ->mapWithKeys(function (mixed $value, string|int $key): array {
                $keyString = (string) $key;

                if (preg_match('/password|secret|token|key|authorization|credential/i', $keyString)) {
                    return [$keyString => '[redacted]'];
                }

                if (is_array($value)) {
                    return [$keyString => self::sanitize($value)];
                }

                if (is_string($value)) {
                    return [$keyString => Str::limit($value, 500, '')];
                }

                return [$keyString => $value];
            })
            ->all();
    }
}
