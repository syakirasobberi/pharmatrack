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

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
