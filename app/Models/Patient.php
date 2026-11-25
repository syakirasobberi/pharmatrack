<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Patient extends Model
{
    use HasFactory;

    // 2. Senaraikan kolum yang dibenarkan untuk diisi (Mass Assignment)
    protected $fillable = [
        'user_id',
        'pharmacist_id',
        'age',
        'gender',
        'weight',
        'height',
        'bmi',
        'ai_summary',
        'face_descriptor',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    /**
     * Archived patients are hidden from EVERY query by default (lists, dashboard, kiosk and
     * Quick Scan matching, reminders, admin counts). Use Patient::withArchived() or
     * ->onlyArchived() on the few screens that manage archived patients.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('notArchived', fn (Builder $query) => $query->whereNull('patients.archived_at'));
    }

    public function scopeWithArchived(Builder $query): Builder
    {
        return $query->withoutGlobalScope('notArchived');
    }

    public function scopeOnlyArchived(Builder $query): Builder
    {
        return $query->withoutGlobalScope('notArchived')->whereNotNull('patients.archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    // 3. Hubungan (Relationship): Seorang pesakit mempunyai satu akaun User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withDefault([
            'name' => 'Unnamed Patient',
            'email' => 'No email recorded',
        ]);
    }

    public function pharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id', 'id');
    }

    public function scopeAssignedTo(Builder $query, User|int $pharmacist): Builder
    {
        $pharmacistId = $pharmacist instanceof User ? $pharmacist->id : $pharmacist;

        return $query->where('pharmacist_id', $pharmacistId);
    }

    // Tambah ini di bawah fungsi user() yang sedia ada
    public function healthCheckups()
    {
        // Seorang pesakit boleh ada banyak rekod check-up
        return $this->hasMany(HealthCheckup::class, 'patient_id', 'id')->orderBy('checkup_date', 'desc');
    }

    /** The most recent check-up (by check-up date, then newest entry). */
    public function latestCheckup()
    {
        return $this->hasOne(HealthCheckup::class, 'patient_id', 'id')->ofMany([
            'checkup_date' => 'max',
            'id' => 'max',
        ]);
    }

    // Hubungan: 1 Pesakit ada 1 Sejarah Perubatan Utama
    public function medicalHistory()
    {
        return $this->hasOne(MedicalHistory::class);
    }

    // Hubungan: 1 Pesakit boleh ada Banyak Ubat-ubatan
    public function medications()
    {
        return $this->hasMany(Medication::class);
    }

    /** Medications the patient is taking now (not stopped, not past their end date). */
    public function currentMedications()
    {
        return $this->hasMany(Medication::class)->current();
    }
}
