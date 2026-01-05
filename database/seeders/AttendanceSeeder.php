<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // نختار موظف عشوائي (ليس سوبر أدمن)
        $employee = User::where('role', 'employee')->first();

        if ($employee) {
            // ننشئ له سجل حضور لآخر 7 أيام
            for ($i = 0; $i < 7; $i++) {
                Attendance::create([
                    'user_id' => $employee->id,
                    'date' => Carbon::now()->subDays($i)->format('Y-m-d'),
                    'check_in' => '08:00:00',
                    'check_out' => '16:00:00',
                    'status' => 'present',
                    'working_hours' => 8
                ]);
            }
            $this->command->info("تمت إضافة سجلات حضور للموظف: " . $employee->name);
        }
    }
}