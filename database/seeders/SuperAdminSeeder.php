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
        $email = 'admin@smart-restaurant.com';
        $password = '123456789'; // الباسورد الذي تريدينه

        $user = User::where('email', $email)->first();

        if (!$user) {
            // إنشاء جديد
            User::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'phone_number' => '0599999999',
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $this->command->info("Super Admin created! Email: $email | Password: $password");
        } else {
            // تحديث البيانات الموجودة (لضمان أن الباسورد والرول صحيحين)
            $user->update([
                'role' => 'super_admin',
                'password' => Hash::make($password),
                'is_active' => true
            ]);
            $this->command->info("Super Admin updated! Email: $email | Password: $password");
        }
    }
    }

