<?php

namespace App\Http\Controllers\Admin; 

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    // 1. عرض موظفي فرعي فقط (القائمة)
    public function index(Request $request)
    {
        $myBranchId = $request->user()->branch_id;

        $query = User::where('branch_id', $myBranchId)
                     ->where('role', 'employee'); 

        // البحث
        if ($request->has('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            });
        }

        $employees = $query->get()->map(function($user) {
            $user->photo_url = $user->photo ? asset('storage/' . $user->photo) : null;
            return $user;
        });

        return response()->json(['status' => true, 'data' => $employees]);
    }

    // 2. إنشاء موظف في فرعي (Create)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'phone_number' => 'nullable',
            'job_title' => 'required|string',
            'department' => 'required|string',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'date_of_hire' => 'nullable|date',
            'photo' => 'nullable|image|max:2048'
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('employees', 'public');
        }

        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'branch_id' => $request->user()->branch_id, // يضاف تلقائياً لفرع المدير
            'role' => 'employee',
            'job_title' => $request->job_title,
            'department' => $request->department,
            'description' => $request->description,
            'address' => $request->address,
            'salary' => $request->salary,
            'date_of_hire' => $request->date_of_hire ?? now(),
            'photo' => $photoPath,
            'is_active' => true,
        ]);

        return response()->json(['status' => true, 'message' => 'Employee created in your branch', 'data' => $employee], 201);
    }

    // 3. عرض تفاصيل موظف (Show - زر العين)
    // ⚠️ الحماية: يجب أن يكون الموظف تابعاً لنفس فرع المدير
    public function show(Request $request, $id)
    {
        $myBranchId = $request->user()->branch_id;

        $employee = User::where('id', $id)
                        ->where('branch_id', $myBranchId) // شرط الأمان
                        ->first();

        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Employee not found or access denied'], 404);
        }

        // تجهيز البيانات
        $data = $employee->toArray();
        $data['photo_url'] = $employee->photo ? asset('storage/' . $employee->photo) : null;
        $data['restaurant'] = $employee->branch ? $employee->branch->name : 'N/A';

        return response()->json(['status' => true, 'data' => $data]);
    }

    // 4. تعديل بيانات موظف (Update - زر القلم)
    public function update(Request $request, $id)
    {
        $myBranchId = $request->user()->branch_id;

        // شرط الأمان: لا نعدل إلا موظفينا
        $employee = User::where('id', $id)
                        ->where('branch_id', $myBranchId)
                        ->first();

        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Employee not found or access denied'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string',
            'job_title' => 'sometimes|string',
            'department' => 'sometimes|string',
            'description' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'address' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'date_of_hire' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean'
        ]);

        $data = $request->except(['password', 'email']);

        // تحديث الصورة
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('employees', 'public');
        }

        // تحديث الباسورد
        if ($request->has('password') && !empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        return response()->json(['status' => true, 'message' => 'Updated successfully', 'data' => $employee]);
    }

    // 5. حذف موظف (Delete - زر الحذف)
    public function destroy(Request $request, $id)
    {
        $myBranchId = $request->user()->branch_id;

        // شرط الأمان
        $employee = User::where('id', $id)
                        ->where('branch_id', $myBranchId)
                        ->first();

        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Employee not found or access denied'], 404);
        }

        $employee->delete();

        return response()->json(['status' => true, 'message' => 'Deleted successfully']);
    }
}