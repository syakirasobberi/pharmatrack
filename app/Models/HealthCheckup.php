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
        'spo2',
        'weight',
        'height',
        'bmi',
        'blood_sugar',
        'hba1c',
        'cholesterol',
        'ldl',
        'hdl',
        'triglycerides',
        'notes',
    ];

    protected $casts = [
        'checkup_date' => 'date',
        'blood_sugar' => 'decimal:2',
        'cholesterol' => 'decimal:2',
        'hba1c' => 'decimal:1',
        'ldl' => 'decimal:2',
        'hdl' => 'decimal:2',
        'triglycerides' => 'decimal:2',
        'weight' => 'decimal:1',
        'height' => 'decimal:1',
        'bmi' => 'decimal:2',
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
