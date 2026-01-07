<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    // 1. عرض موظفي فرع معين (للجدول)
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

        // نضيف رابط الصورة الكامل للبيانات
        $employees = $query->get()->map(function($user) {
            $user->photo_url = $user->photo ? asset('storage/' . $user->photo) : null;
            return $user;
        });

        return response()->json(['status' => true, 'data' => $employees]);
    }

    // 2. عرض كل الموظفين في النظام
   public function getAllEmployees(Request $request)
    {
        // 1. استثناء السوبر أدمن
        $query = User::where('role', '!=', 'super_admin')->with('branch:id,name');
        
        // 2. البحث (Search)
        if ($request->has('search') && !empty($request->search)) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%");
            });
        }

        // 3. فلتر المدراء فقط (Active Admins Link)
        if ($request->has('role') && $request->role == 'admin') {
            $query->where('job_title', 'Manager'); // أو حسب ما تسمين المدير
        }

        $employees = $query->get();

        // 4. تنسيق البيانات لتطابق الجدول في الصورة
        $data = $employees->map(function($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->name, // Admin Name
                'restaurant' => $employee->branch ? $employee->branch->name : 'N/A', // Restaurant Name
                'phone' => $employee->phone_number,
                'email' => $employee->email,
                'department' => $employee->department, // Department (United States?? في الصورة مكتوب دولة، غريب!)
                'status' => $employee->is_active ? 'Active' : 'Inactive',
                'photo' => $employee->photo ? asset('storage/' . $employee->photo) : null,
            ];
        });
        
        return response()->json(['status' => true, 'data' => $data]);
    }

    // 3. إنشاء موظف جديد (التعديل الشامل)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone_number' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'job_title' => 'required|string|max:100', 
            'department' => 'required|string|max:100',
            // الحقول الجديدة والاختيارية
            'address' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'date_of_hire' => 'nullable|date',
            'photo' => 'nullable|image|max:2048' // صورة
        ]);

        // رفع الصورة
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('employees', 'public');
        }

        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'branch_id' => $request->branch_id,
            'role' => 'employee', // تلقائي
            'job_title' => $request->job_title,
            'department' => $request->department,
            'address' => $request->address,
            'salary' => $request->salary,
            'date_of_hire' => $request->date_of_hire ?? now(),
            'photo' => $photoPath, // حفظ المسار
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Employee created successfully',
            'data' => $employee
        ], 201);
    }

    // 4. عرض تفاصيل موظف (زر العين - More Details)
    public function show($id)
    {
        // نجيب الموظف مع بيانات الفرع
        $employee = User::with('branch')->find($id);

        if (!$employee) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        // تنسيق البيانات لتطابق التصميم 100%
        $data = [
            'id' => $employee->id,
            'name' => $employee->name,
            'photo' => $employee->photo ? asset('storage/' . $employee->photo) : null,
            'email' => $employee->email,
            'phone' => $employee->phone_number,
            'address' => $employee->address ?? 'N/A', // العنوان
            'restaurant' => $employee->branch ? $employee->branch->name : 'N/A', // اسم المطعم
            'job_title' => $employee->job_title,
            'department' => $employee->department,
            'date_of_hire' => $employee->date_of_hire ? \Carbon\Carbon::parse($employee->date_of_hire)->format('d/m/Y') : 'N/A',
            'salary' => $employee->salary ? $employee->salary . '$ For Hour' : 'N/A',
            'attendance_hours' => 0, // ثابت حالياً
            'status' => $employee->is_active ? 'Active' : 'Inactive',
        ];

        return response()->json(['status' => true, 'data' => $data]);
    }

    // 5. تعديل بيانات موظف (مع الصورة)
    public function update(Request $request, $id)
    {
        $employee = User::find($id);
        if (!$employee) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        $request->validate([
            'name' => 'sometimes|string',
            'job_title' => 'sometimes|string',
            'department' => 'sometimes|string',
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

        // تحديث الباسورد لو انبعت
        if ($request->has('password') && !empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

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