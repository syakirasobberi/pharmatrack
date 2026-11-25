<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable = [
        'patient_id',
        'name',
        'dosage',
        'frequency',
        'notes',
        'start_date',
        'end_date',
        'last_taken',
        'supply_until',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'last_taken' => 'date',
        'stopped_at' => 'datetime',
        'supply_until' => 'date',
        'refill_reminder_sent_for' => 'date',
        'refill_requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /** Recorded by the patient and not yet looked at by a pharmacist. */
    public function needsReview(): bool
    {
        return $this->reviewed_at === null;
    }

    /** Set (not saved) the pharmacist review stamp. */
    public function markReviewed(User $pharmacist): static
    {
        return $this->forceFill(['reviewed_at' => now(), 'reviewed_by' => $pharmacist->id]);
    }

    /** Remind this many days before the supply runs out. */
    public const REFILL_NOTICE_DAYS = 5;

    /**
     * Refill state for a current medication with a known supply:
     * null (no supply date / not current), 'ok', 'due' (runs out within REFILL_NOTICE_DAYS) or 'overdue' (ran out).
     */
    public function refillStatus(): ?string
    {
        if ($this->supply_until === null || ! $this->isCurrent()) {
            return null;
        }
        // No refill needed if the treatment ends before (or when) the supply runs out.
        if ($this->end_date !== null && $this->end_date->lte($this->supply_until)) {
            return null;
        }
        if ($this->supply_until->lt(today())) {
            return 'overdue';
        }

        return $this->supply_until->lte(today()->addDays(self::REFILL_NOTICE_DAYS)) ? 'due' : 'ok';
    }

    /** Days of supply left (negative = days since it ran out), or null when unknown. */
    public function supplyDaysLeft(): ?int
    {
        return $this->supply_until ? (int) today()->diffInDays($this->supply_until, false) : null;
    }

    /**
     * Record a refill dispensed today for $days days. Tablets left over from the current
     * supply carry forward, so the new supply starts from the later of today and supply_until.
     */
    public function refill(int $days): void
    {
        $from = $this->supply_until && $this->supply_until->gt(today()) ? $this->supply_until->copy() : today();

        $this->forceFill([
            'supply_until' => $from->addDays($days),
            'refill_reminder_sent_for' => null,
            'refill_requested_at' => null,
        ])->save();
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doses()
    {
        return $this->hasMany(MedicationDose::class);
    }

    /**
     * Log that this medication was taken on $date (one entry per day; repeats are ignored)
     * and move last_taken forward if this is the newest dose.
     */
    public function recordDose(\DateTimeInterface|string $date, string $source, ?User $by = null): MedicationDose
    {
        $day = \Carbon\Carbon::parse($date)->startOfDay();

        $dose = $this->doses()->firstOrCreate(
            ['taken_on' => $day->toDateString()],
            ['source' => $source, 'recorded_by' => $by?->id],
        );

        if ($this->last_taken === null || $this->last_taken->lt($day)) {
            $this->forceFill(['last_taken' => $day])->save();
        }

        return $dose;
    }

    /** Remove the dose logged for $date; last_taken falls back to the latest remaining dose. */
    public function removeDose(\DateTimeInterface|string $date): void
    {
        $day = \Carbon\Carbon::parse($date)->toDateString();
        $this->doses()->whereDate('taken_on', $day)->delete();

        if ($this->last_taken?->toDateString() === $day) {
            $this->forceFill(['last_taken' => $this->doses()->max('taken_on')])->save();
        }
    }

    /**
     * How often it should be taken, read from the free-text frequency/dosage:
     * "as_needed" (PRN – not counted for adherence), "weekly", or "daily" (default).
     */
    public function schedule(): string
    {
        $text = strtolower($this->frequency . ' ' . $this->dosage);

        if (preg_match('/\b(prn|as needed|as required|when needed|if needed|when required|bila perlu|jika perlu)\b/', $text)) {
            return 'as_needed';
        }
        if (preg_match('/\b(weekly|once a week|once weekly|every week|per week|seminggu|mingguan)\b/', $text)) {
            return 'weekly';
        }

        return 'daily';
    }

    /** Current = not stopped by a pharmacist and not past its end date. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('stopped_at')
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()));
    }

    public function isCurrent(): bool
    {
        return $this->stopped_at === null && ($this->end_date === null || ! $this->end_date->isBefore(today()));
    }

    /** "Active", "Stopped" or "Completed" (end date passed). */
    public function status(): string
    {
        if ($this->stopped_at !== null) {
            return 'Stopped';
        }

        return $this->isCurrent() ? 'Active' : 'Completed';
    }
}
