<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * عرض سجل الحضور لموظف معين (للسوبر أدمن - مشاهدة فقط)
     */
    public function getEmployeeAttendance(Request $request, $employeeId)
    {
        // 1. جلب سجلات هذا الموظف
        $query = Attendance::where('user_id', $employeeId);

        // فلترة بالشهر والسنة (لأن السوبر أدمن قد يختار شهراً معيناً من الـ UI)
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year);
        }

        $records = $query->orderBy('date', 'desc')->get();

        // 2. حساب الملخص (Cards Summary)
        $summary = [
            'total_hours' => $records->sum('working_hours'),
            'attendance_hours' => $records->where('status', 'present')->sum('working_hours'),
            'absent_days' => $records->where('status', 'absent')->count(),
        ];

        // 3. تنسيق الجدول
        $formattedRecords = $records->map(function ($record) {
            return [
                'id' => $record->id,
                'date' => $record->date, 
                'day_name' => date('l', strtotime($record->date)), 
                'check_in' => $record->check_in ? date('H:i', strtotime($record->check_in)) : '-',
                'check_out' => $record->check_out ? date('H:i', strtotime($record->check_out)) : '-',
                'status' => $record->status,
                'hours' => $record->working_hours
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'summary' => $summary,
                'records' => $formattedRecords
            ]
        ]);
    }
}