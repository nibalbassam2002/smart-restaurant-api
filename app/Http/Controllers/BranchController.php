<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    // عرض القائمة
    public function index(Request $request)
    {
        $query = Branch::query();
        if ($request->has('search') && !empty($request->search)) {
            $s = $request->search;
            $query->where('name', 'like', "%{$s}%")->orWhere('city', 'like', "%{$s}%");
        }
        $branches = $query->with('manager:id,name')->withCount('users')->get();

        $data = $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'location' => "{$branch->street}, {$branch->city}, {$branch->country}",
                'admin_status' => $branch->manager ? $branch->manager->name : 'Not Selected',
                'is_admin_selected' => !is_null($branch->manager_id),
                'employees_count' => $branch->users_count,
                'logo' => $branch->logo ? asset('storage/' . $branch->logo) : null,
            ];
        });
        return response()->json(['status' => true, 'data' => $data]);
    }

    // تهيئة الإنشاء
    public function create()
    {
        $potentialAdmins = User::where('role', '!=', 'super_admin')->select('id', 'name')->get();
        return response()->json(['status' => true, 'data' => ['potential_admins' => $potentialAdmins]]);
    }

    // الإنشاء (مع الصورة)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'country' => 'required|string',
            'city' => 'required|string',
            'street' => 'required|string',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // معالجة رفع الصورة
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('branches', 'public');
        }

        $code = 'BR-' . mt_rand(100000, 999999);

        $branch = Branch::create([
            'branch_code' => $code,
            'name' => $request->name,
            'logo' => $logoPath,
            'country' => $request->country,
            'city' => $request->city,
            'street' => $request->street,
            'manager_id' => $request->manager_id,
            'status' => $request->status,
            'notes' => $request->notes
        ]);

        return response()->json(['status' => true, 'message' => 'Restaurant created', 'data' => $branch], 201);
    }

    // التفاصيل
    public function show($id)
    {
        $branch = Branch::with('manager:id,name')->withCount('users')->find($id);
        if (!$branch) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        $data = [
            'id' => $branch->id,
            'name' => $branch->name,
            'branch_code' => $branch->branch_code ?? 'N/A', 
            'created_date' => $branch->created_at->format('d/m/Y'), 
            
            // --- الإضافة المهمة هنا (الصورة) ---
            'logo' => $branch->logo ? asset('storage/' . $branch->logo) : null,
            // ----------------------------------

            'location_display' => "{$branch->street}, {$branch->city}, {$branch->country}",
            'country' => $branch->country,
            'city' => $branch->city,
            'street' => $branch->street,
            'manager_id' => $branch->manager_id,
            'manager_name' => $branch->manager ? $branch->manager->name : 'Select Admin',
            'status' => $branch->status,
            'employees_count' => $branch->users_count,
        ];
        return response()->json(['status' => true, 'data' => $data]);
    }

    // التعديل
    public function update(Request $request, $id)
    {
        $branch = Branch::find($id);
        if (!$branch) return response()->json(['status' => false, 'message' => 'Not found'], 404);

        // هنا أضفت لكِ باقي التحقق لكي يعمل التعديل بشكل صحيح
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'country' => 'sometimes|string',
            'city' => 'sometimes|string',
            'street' => 'sometimes|string',
            'manager_id' => 'sometimes|nullable|exists:users,id',
            'status' => 'sometimes|in:active,inactive',
            'notes' => 'sometimes|nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $request->all();

        // تحديث الصورة إذا تم رفع صورة جديدة
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('branches', 'public');
        }

        $branch->update($data);

        return response()->json(['status' => true, 'message' => 'Updated Successfully', 'data' => $branch]);
    }

    // الحذف
    public function destroy($id)
    {
        $branch = Branch::find($id);
        if (!$branch) return response()->json(['status' => false, 'message' => 'Not found'], 404);
        $branch->delete();
        return response()->json(['status' => true, 'message' => 'Deleted Successfully']);
    }
}