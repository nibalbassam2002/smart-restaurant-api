<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * 1. عرض موظفي فرع معين (مع بحث)
     */
    public function index(Request $request, $branchId)
    {
        $query = User::where('branch_id', $branchId)
                     ->where('role', '!=', 'super_admin'); // نستثني السوبر أدمن

        // إضافة ميزة البحث (Search)
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $employees = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Employees fetched successfully',
            'data' => $employees
        ]);
    }

    /**
     * 2. إنشاء موظف جديد داخل الفرع
     */
    public function store(Request $request, $branchId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string',
            'role' => 'required|in:admin,employee', // مدير فرع أو موظف عادي
            // يمكن إضافة 'department' هنا لو أضفناها للداتابيز لاحقاً
        ]);

        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'role' => $request->role,
            'branch_id' => $branchId, // نربطه بالفرع القادم من الرابط
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Employee created successfully',
            'data' => $employee
        ], 201);
    }

    /**
     * 3. عرض تفاصيل موظف معين
     */
    public function show($id)
    {
        $employee = User::find($id);

        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Employee not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $employee
        ]);
    }

    /**
     * 4. تعديل بيانات الموظف (الاسم، الحالة، الباسورد...)
     */
    public function update(Request $request, $id)
    {
        $employee = User::find($id);

        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Employee not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($employee->id)],
            'phone_number' => 'nullable|string',
            'is_active' => 'sometimes|boolean', // لتغيير الحالة (Active/Inactive)
            'password' => 'nullable|string|min:8' // في حال أراد تغيير الباسورد
        ]);

        // تحديث البيانات
        $data = $request->except('password'); // نأخذ كل شيء ما عدا الباسورد

        // نعالج الباسورد لوحده إذا تم إرساله
        if ($request->has('password') && !empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Employee updated successfully',
            'data' => $employee
        ]);
    }

    /**
     * 5. حذف موظف نهائياً
     */
    public function destroy($id)
    {
        $employee = User::find($id);

        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Employee not found'], 404);
        }

        // لا نسمح بحذف السوبر أدمن بالخطأ
        if ($employee->role === 'super_admin') {
            return response()->json(['status' => false, 'message' => 'Cannot delete Super Admin'], 403);
        }

        $employee->delete();

        return response()->json([
            'status' => true,
            'message' => 'Employee deleted successfully'
        ]);
    }
}