<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    // 2. Senaraikan kolum yang dibenarkan untuk diisi (Mass Assignment)
    protected $fillable = [
        'user_id',
        'age',
        'gender',
        'weight',
        'height',
        'bmi',
        'face_descriptor',
    ];

    // 3. Hubungan (Relationship): Seorang pesakit mempunyai satu akaun User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withDefault([
            'name' => 'Unnamed Patient',
            'email' => 'No email recorded',
        ]);
    }

    // Tambah ini di bawah fungsi user() yang sedia ada
    public function healthCheckups()
    {
        // Seorang pesakit boleh ada banyak rekod check-up
        return $this->hasMany(HealthCheckup::class, 'patient_id', 'id')->orderBy('checkup_date', 'desc');
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
}
