<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicationDose extends Model
{
    public const SOURCES = ['patient', 'pharmacist', 'kiosk'];

    protected $fillable = ['medication_id', 'taken_on', 'source', 'recorded_by'];

    protected $casts = [
        'taken_on' => 'date',
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }
}
