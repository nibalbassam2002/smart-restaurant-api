<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * عرض قائمة الفروع (لصفحة الداشبورد)
     */
    public function index(Request $request)
    {
        // 1. نبدأ الاستعلام
        $query = Branch::query();

        // 2. تفعيل البحث (إذا أرسل المستخدم كلمة بحث في الرابط)
        // مثال: ?search=gaza
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('city', 'like', "%{$searchTerm}%");
            });
        }

        // 3. جلب البيانات مع العلاقات
        // with('manager'): لجلب اسم المدير
        // withCount('users'): لعد الموظفين في هذا الفرع أوتوماتيكياً
        $branches = $query->with('manager:id,name')
                          ->withCount('users') 
                          ->get();

        // 4. تنسيق البيانات لتطابق تصميم Figma
        $data = $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                // تجميع العنوان في سطر واحد كما في التصميم
                'location' => "{$branch->street}, {$branch->city}, {$branch->country}",
                // حالة المدير: إذا موجود نكتب اسمه، غير موجود نكتب Not Selected
                'admin_status' => $branch->manager ? $branch->manager->name : 'Not Selected',
                'is_admin_selected' => !is_null($branch->manager_id), // عشان الرياكت يعرف يلون النص
                // عدد الموظفين
                'employees_count' => $branch->users_count, 
                // صورة الشعار (سنرسل null حالياً حتى نبرمج رفع الصور)
                'logo' => null, 
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'All branches fetched successfully',
            'data' => $data
        ]);
    }
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
    /**
     * عرض تفاصيل فرع معين (عند الضغط على More Details)
     */
    public function show($id)
    {
        // 1. البحث عن الفرع مع بيانات المدير وعد الموظفين
        $branch = Branch::with('manager:id,name')
                        ->withCount('users')
                        ->find($id);

        // 2. إذا الفرع غير موجود (مثلاً كتب id خطأ)
        if (!$branch) {
            return response()->json([
                'status' => false,
                'message' => 'Restaurant not found',
            ], 404);
        }

        // 3. تنسيق البيانات (مهم جداً لصفحة التعديل)
        // سنرسل العنوان كقطعة واحدة للعرض، وكقطع منفصلة للتعديل
        $data = [
            'id' => $branch->id,
            'name' => $branch->name,
            
            // للعرض في الواجهة (Display)
            'location_display' => "{$branch->street}, {$branch->city}, {$branch->country}",
            
            // للتعديل (عشان لما يضغط على القلم، الخانات تكون معبأة)
            'country' => $branch->country,
            'city' => $branch->city,
            'street' => $branch->street,
            
            // بيانات المدير
            'manager_id' => $branch->manager_id,
            'manager_name' => $branch->manager ? $branch->manager->name : 'Not Selected',
            
            // الحالة والملاحظات
            'status' => $branch->status,
            'notes' => $branch->notes,
            
            // عدد الموظفين (للعرض بجانب أيقونة العين)
            'employees_count' => $branch->users_count,
            
            // الشعار (مؤقتاً)
            'logo' => null, 
        ];

        return response()->json([
            'status' => true,
            'message' => 'Branch details fetched successfully',
            'data' => $data
        ]);
    }
}
