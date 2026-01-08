<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    // عرض قائمة المدراء
    public function index(Request $request)
    {
        $query = User::where('role', 'admin')->with('branch:id,name');

        if ($request->has('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        // تنسيق البيانات وإضافة رابط الصورة
        $admins = $query->get()->map(function($admin) {
            $admin->photo_url = $admin->photo ? asset('storage/' . $admin->photo) : null;
            return $admin;
        });

        return response()->json(['status' => true, 'data' => $admins]);
    }

    // إنشاء مدير جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'phone_number' => 'nullable',
            'branch_id' => 'required|exists:branches,id',
            'address' => 'nullable|string',
            'date_of_hire' => 'nullable|date',
            
            // الصورة اختيارية (Nullable)
            'photo' => 'nullable|image|max:2048',

            // التحقق من الصلاحيات (يجب أن تكون مصفوفة، وكل قيمة فيها موجودة في القائمة المعتمدة)
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', User::AVAILABLE_PERMISSIONS),
        ]);

        // معالجة الصورة (إذا رفعت نحفظها، إذا لا تبقى null)
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('admins', 'public');
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'branch_id' => $request->branch_id,
            'role' => 'admin', // رتبة مدير
            'address' => $request->address,
            'date_of_hire' => $request->date_of_hire ?? now(),
            'photo' => $photoPath,
            'permissions' => $request->permissions, // حفظ المصفوفة كما هي (Laravel يلقائياً يحولها JSON)
            'is_active' => true
        ]);

        // إرجاع البيانات مع رابط الصورة
        $admin->photo_url = $admin->photo ? asset('storage/' . $admin->photo) : null;

        return response()->json(['status' => true, 'message' => 'Admin created successfully', 'data' => $admin], 201);
    }

    // التفاصيل
    public function show($id)
    {
        $admin = User::where('role', 'admin')->with('branch')->find($id);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        
        // تحويل الموديل لمصفوفة لإضافة الرابط
        $data = $admin->toArray();
        $data['photo_url'] = $admin->photo ? asset('storage/' . $admin->photo) : null;
        
        return response()->json(['status' => true, 'data' => $data]);
    }

    // التعديل
    public function update(Request $request, $id)
    {
        $admin = User::where('role', 'admin')->find($id);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        $request->validate([
            'name' => 'sometimes|string',
            'branch_id' => 'sometimes|exists:branches,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:' . implode(',', User::AVAILABLE_PERMISSIONS),
            'photo' => 'nullable|image|max:2048'
        ]);

        $data = $request->except(['password', 'email']);

        // تحديث الصورة
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('admins', 'public');
        }
        
        // تحديث الباسورد
        if ($request->has('password') && !empty($request->password)) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);
        
        // تحديث الكائن لإرجاع الرابط الجديد
        $admin->refresh();
        $admin->photo_url = $admin->photo ? asset('storage/' . $admin->photo) : null;

        return response()->json(['status' => true, 'message' => 'Updated successfully', 'data' => $admin]);
    }

    // الحذف
    public function destroy($id)
    {
        $admin = User::where('role', 'admin')->find($id);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        $admin->delete();
        return response()->json(['status' => true, 'message' => 'Deleted successfully']);
    }
}