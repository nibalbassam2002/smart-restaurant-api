<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         if (!User::where('email', 'superadmin@example.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'), // كلمة مرور قوية في الإنتاج
                'phone_number' => '1234567890',
                'date_of_birth' => '1990-01-01',
                'role' => 'super_admin', // الدور الأهم
                'is_active' => true,
            ]);
            $this->command->info('Super Admin created!');
        } else {
            $this->command->info('Super Admin already exists!');
        }
    }
    }

