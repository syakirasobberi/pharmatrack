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
