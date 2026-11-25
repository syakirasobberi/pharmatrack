<?php

namespace App\Observers;

use App\Models\HealthCheckup;
use App\Models\MedicalHistory;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;

/**
 * Records every create / update / delete of clinical records and staff accounts,
 * whichever screen (pharmacist, patient portal, kiosk, admin) or job made it.
 */
class AuditObserver
{
    /**
     * Fields whose changes alone are routine bookkeeping, not worth an entry
     * (e.g. last_taken moves on every "I took it" tap; reminder markers move daily).
     */
    private const QUIET_FIELDS = [
        Medication::class => ['last_taken', 'refill_reminder_sent_for'],
        User::class => ['remember_token', 'email_verified_at'],
        Patient::class => [],
        HealthCheckup::class => [],
        MedicalHistory::class => [],
    ];

    public function created(Model $model): void
    {
        Audit::record(
            $this->key($model) . '.created',
            $this->label($model) . ' created',
            $this->patientId($model),
            $model,
            $this->createdValues($model),
        );
    }

    public function updated(Model $model): void
    {
        // getDirty() is empty after save; use getChanges() via a fresh diff of original vs current.
        $changes = [];
        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, array_merge(Audit::SECRET_FIELDS, ['updated_at']), true)) {
                continue;
            }
            $from = $this->printable($model->getRawOriginal($field));
            $to = $this->printable($new);
            if ($from === $to) {
                continue; // e.g. a field set to empty that was already empty
            }
            $changes[$field] = ['from' => $from, 'to' => $to];
        }

        $quiet = self::QUIET_FIELDS[$model::class] ?? [];
        if ($changes === [] || array_diff(array_keys($changes), $quiet) === []) {
            return;
        }

        Audit::record(
            $this->key($model) . '.' . $this->updateVerb($model, $changes),
            $this->label($model) . ' ' . str_replace('_', ' ', $this->updateVerb($model, $changes)),
            $this->patientId($model),
            $model,
            $changes,
        );
    }

    public function deleted(Model $model): void
    {
        Audit::record($this->key($model) . '.deleted', $this->label($model) . ' deleted', $this->patientId($model), $model);
    }

    private function key(Model $model): string
    {
        return match ($model::class) {
            HealthCheckup::class => 'checkup',
            MedicalHistory::class => 'medical_history',
            User::class => 'account',
            default => strtolower(class_basename($model)),
        };
    }

    private function label(Model $model): string
    {
        return match ($model::class) {
            Medication::class => "Medication \"{$model->name}\"",
            HealthCheckup::class => 'Check-up of ' . optional($model->checkup_date)->format('d M Y'),
            MedicalHistory::class => 'Medical history',
            Patient::class => 'Patient record' . ($model->user?->name ? " ({$model->user->name})" : ''),
            User::class => ucfirst((string) $model->role) . " account \"{$model->name}\"",
            default => class_basename($model) . " #{$model->getKey()}",
        };
    }

    /** More specific verbs for the changes people search for. */
    private function updateVerb(Model $model, array $changes): string
    {
        return match (true) {
            $model instanceof Medication && isset($changes['stopped_at']) => $model->stopped_at ? 'stopped' : 'resumed',
            $model instanceof Medication && isset($changes['supply_until']) && count($changes) <= 3 => 'refilled',
            $model instanceof Medication && isset($changes['refill_requested_at']) && count($changes) === 1 => $model->refill_requested_at ? 'refill_requested' : 'updated',
            $model instanceof Patient && isset($changes['archived_at']) => $model->archived_at ? 'archived' : 'restored',
            $model instanceof Patient && isset($changes['pharmacist_id']) => 'reassigned',
            $model instanceof User && isset($changes['deactivated_at']) => $model->deactivated_at ? 'deactivated' : 'reactivated',
            default => 'updated',
        };
    }

    private function patientId(Model $model): ?int
    {
        return match (true) {
            $model instanceof Patient => $model->id,
            $model instanceof User => $model->role === 'patient' ? Patient::withoutGlobalScopes()->where('user_id', $model->id)->value('id') : null,
            default => $model->patient_id ?? null,
        };
    }

    /** Values of a new record (without secrets, ids and empty fields). */
    private function createdValues(Model $model): array
    {
        return collect($model->getAttributes())
            ->except(array_merge(Audit::SECRET_FIELDS, ['id', 'created_at', 'updated_at']))
            ->reject(fn ($v) => $v === null || $v === '')
            ->map(fn ($v) => ['from' => null, 'to' => $this->printable($v)])
            ->all();
    }

    private function printable(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return is_string($value) ? \Illuminate\Support\Str::limit($value, 200) : $value;
    }
}
