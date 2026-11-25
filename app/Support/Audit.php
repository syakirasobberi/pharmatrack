<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes audit trail entries. Never throws: a logging problem must not break the action
 * being logged (it is reported to laravel.log instead).
 */
class Audit
{
    /** Never stored, even in change diffs. */
    public const SECRET_FIELDS = ['password', 'remember_token', 'kiosk_pin', 'face_descriptor', 'two_factor_secret'];

    public static function record(
        string $action,
        string $description,
        ?int $patientId = null,
        ?Model $subject = null,
        ?array $changes = null,
        ?User $actor = null,
    ): void {
        try {
            $actor ??= auth()->user();
            $request = app()->runningInConsole() ? null : request();

            AuditLog::create([
                'user_id' => $actor?->id,
                'actor_name' => $actor?->name ?? self::anonymousActorName($request),
                'actor_role' => $actor?->role ?? self::anonymousActorRole($request),
                'action' => Str::limit($action, 60, ''),
                'description' => Str::limit($description, 500),
                'patient_id' => $patientId,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'changes' => $changes ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 255, '') : null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function anonymousActorRole($request): string
    {
        return match (true) {
            $request === null => 'system',
            $request->routeIs('kiosk.*') => 'kiosk',
            default => 'guest',
        };
    }

    private static function anonymousActorName($request): string
    {
        return match (self::anonymousActorRole($request)) {
            'system' => 'System (scheduled task)',
            'kiosk' => 'Kiosk (face-verified patient)',
            default => 'Not logged in',
        };
    }
}
