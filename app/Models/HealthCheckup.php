<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCheckup extends Model
{
    use HasFactory;

    // Senarai lajur yang dibenarkan untuk diisi berdasarkan Logical Database awak
    protected $fillable = [
        'patient_id',
        'pharmacist_id',
        'blood_pressure',
        'blood_sugar',
        'cholesterol',
        'checkup_date',
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