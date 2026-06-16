<?php

namespace App\Models;

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
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
