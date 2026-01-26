<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',     
        'date_of_birth',    
        'role',              
        'is_active',
        'branch_id',   // لربط الموظف بالفرع
        'job_title', 
        'department',
        'photo',       // الصورة
        'address',     // العنوان
        'salary',      // الراتب
        'date_of_hire', // تاريخ التعيين
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    // علاقة الموظف بالفرع (عشان نجيب اسم المطعم في صفحة التفاصيل)
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    // قائمة الصلاحيات المتاحة في النظام
    const AVAILABLE_PERMISSIONS = [
        // إدارة الموظفين
        'view_employees', 'create_employees', 'edit_employees', 'delete_employees', 'manage_roles',
        
        // إدارة الطلبات والمبيعات
        'view_orders', 'update_order_status', 'cancel_order', 'manage_tables', 'view_sales_reports',
        
        // إدارة المخزون
        'view_inventory', 'update_stock', 'add_product', 'mark_sold_out', 'request_restock',
        
        // إدارة الفروع
        'view_branch_details', 'update_working_hours', 'update_location', 'update_branch_settings',
        
        // صلاحيات مالية
        'view_financial_reports', 'request_funds', 'manage_discounts',
        
        // صلاحيات إدارية
        'send_reports', 'manage_staff_permissions',
        
        // صلاحيات النظام والإضافية
        'access_dashboard', 'manage_notifications', 'manage_customers', 'manage_loyalty', 'manage_delivery', 'manage_reviews'
    ];
        public function workSchedules() {
            
        return $this->hasMany(WorkSchedule::class);
    }
    public function attendances() {
        return $this->hasMany(Attendance::class);
    }
}