<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** One audit trail entry. Append-only: entries cannot be edited or deleted through the app. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'actor_name', 'actor_role', 'action', 'description', 'patient_id',
        'subject_type', 'subject_id', 'changes', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withoutGlobalScopes();
    }

    /** "medication.updated" → "Medication updated" */
    public function actionLabel(): string
    {
        return ucfirst(str_replace(['.', '_'], ' ', $this->action));
    }

    /** Group used for colouring/filtering: change, view, auth, security. */
    public function category(): string
    {
        return match (true) {
            str_starts_with($this->action, 'auth.') => 'auth',
            str_ends_with($this->action, '.viewed'), str_ends_with($this->action, '.downloaded'),
            str_ends_with($this->action, '.matched'), str_ends_with($this->action, '.emailed') => 'view',
            default => 'change',
        };
    }
}
