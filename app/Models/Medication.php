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
        'last_taken'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'last_taken' => 'date',
        'stopped_at' => 'datetime',
    ];

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
