<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    // 1. عرض موظفي فرع معين (للجدول داخل صفحة الفرع)
    public function index(Request $request, $branchId)
    {
        $query = User::where('branch_id', $branchId)
                     ->where('role', '!=', 'super_admin');

        if ($request->has('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            });
        }

        return response()->json(['status' => true, 'data' => $query->get()]);
    }

    // 2. (جديد) عرض كل الموظفين في النظام (لصفحة الموظفين العامة)
    public function getAllEmployees(Request $request)
    {
        $query = User::where('role', '!=', 'super_admin')->with('branch:id,name');
        
        if ($request->has('search')) {
            $s = $request->search;
            $query->where('name', 'like', "%{$s}%");
        }
        
        return response()->json(['status' => true, 'data' => $query->get()]);
    }

    // 3. إنشاء موظف جديد (التعديل الكبير هنا)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            
            // الحقول الجديدة حسب التصميم
            'job_title' => 'required|string|max:100', 
            'department' => 'required|string|max:100',
        ]);

        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'branch_id' => $request->branch_id,
            
            'role' => 'employee', // <--- جعلناها تلقائية (موظف)
            
            'job_title' => $request->job_title,   // تخزين الوظيفة
            'department' => $request->department, // تخزين القسم
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Employee created successfully',
            'data' => $employee
        ], 201);
    }

    // 4. عرض تفاصيل موظف
    public function show($id)
    {
        $employee = User::find($id);
        if (!$employee) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        return response()->json(['status' => true, 'data' => $employee]);
    }

    // 5. تعديل بيانات موظف (تحديث لتشمل الوظيفة والقسم)
    public function update(Request $request, $id)
    {
        $employee = User::find($id);
        if (!$employee) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        $request->validate([
            'name' => 'sometimes|string',
            'job_title' => 'sometimes|string',
            'department' => 'sometimes|string',
            'phone_number' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        $employee->update($request->except(['password', 'email'])); 

        return response()->json([
            'status' => true,
            'message' => 'Updated successfully',
            'data' => $employee
        ]);
    }

    // 6. حذف موظف
    public function destroy($id)
    {
        $employee = User::find($id);
        if (!$employee) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        
        if ($employee->role === 'super_admin') {
            return response()->json(['status' => false, 'message' => 'Cannot delete Super Admin'], 403);
        }

        $employee->delete();
        return response()->json(['status' => true, 'message' => 'Deleted successfully']);
    }
}