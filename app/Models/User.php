<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'requires_password_change',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'kiosk_pin',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'kiosk_pin' => 'hashed',
            'requires_password_change' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    public function assignedPatients()
    {
        return $this->hasMany(Patient::class, 'pharmacist_id');
    }

    /** Deactivated accounts cannot log in, are signed out, and cannot approve kiosk updates. */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deactivated_at');
    }
}
