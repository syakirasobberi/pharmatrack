<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthCheckup extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Senarai lajur yang dibenarkan untuk diisi berdasarkan Logical Database awak
    protected $fillable = [
        'patient_id',
        'pharmacist_id',
        'checkup_date',
        'report_source',
        'blood_pressure',
        'heart_rate',
        'haemoglobin',
        'blood_sugar',
        'hba1c',
        'albumin_globulin_ratio',
        'alkaline_phosphatase',
        'aspartate_transaminase',
        'alanine_transaminase',
        'gamma_glutamyl_transferase',
        'sodium',
        'renal_glucose',
        'cholesterol',
        'ldl',
        'hdl',
        'notes',
        'ai_suggestion',
    ];

    protected $casts = [
        'checkup_date' => 'date',
        'blood_sugar' => 'decimal:2',
        'cholesterol' => 'decimal:2',
        'haemoglobin' => 'decimal:2',
        'hba1c' => 'decimal:1',
        'albumin_globulin_ratio' => 'decimal:2',
        'alkaline_phosphatase' => 'decimal:2',
        'aspartate_transaminase' => 'decimal:2',
        'alanine_transaminase' => 'decimal:2',
        'gamma_glutamyl_transferase' => 'decimal:2',
        'sodium' => 'decimal:2',
        'renal_glucose' => 'decimal:2',
        'ldl' => 'decimal:2',
        'hdl' => 'decimal:2',
    ];

    // Hubungan: Setiap rekod pemeriksaan adalah milik seorang Pesakit
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    // Hubungan: Setiap rekod pemeriksaan dibuat oleh seorang Ahli Farmasi (User)
    public function pharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }
}
