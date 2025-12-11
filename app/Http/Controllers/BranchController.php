<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function create()
    {
        // نجيب كل اليوزرز ما عدا السوبر أدمن عشان نختار منهم مدير
        $potentialAdmins = User::where('role', '!=', 'super_admin')
                                ->select('id', 'name')
                                ->get();

        return response()->json([
            'status' => true,
            'message' => 'Data fetched successfully',
            'data' => [
                'potential_admins' => $potentialAdmins
            ]
        ]);
    }

    // 2. دالة حفظ المطعم الجديد
    public function store(Request $request)
    {
        // التحقق من البيانات (Validation)
        $request->validate([
            'name' => 'required|string|max:100',
            'country' => 'required|string',
            'city' => 'required|string',
            'street' => 'required|string',
            'manager_id' => 'nullable|exists:users,id', 
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        // الإنشاء والحفظ
        $branch = Branch::create([
            'name' => $request->name,
            'country' => $request->country,
            'city' => $request->city,
            'street' => $request->street,
            'manager_id' => $request->manager_id,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Restaurant created successfully',
            'data' => $branch
        ], 201);
    }
}
