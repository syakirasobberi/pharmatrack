<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Pulls in your User model
use Illuminate\Support\Facades\Hash; // Used to encrypt passwords securely

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create the Admin Account
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@pharmatrack.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // 2. Create the Pharmacist Account
        User::create([
            'name' => 'Pharmacist Staff',
            'email' => 'pharmacist@pharmatrack.com',
            'password' => Hash::make('password123'),
            'role' => 'pharmacist',
        ]);

        // 3. Create the Patient Account
        User::create([
            'name' => 'Test Patient',
            'email' => 'patient@pharmatrack.com',
            'password' => Hash::make('password123'),
            'role' => 'patient',
        ]);
    }
}