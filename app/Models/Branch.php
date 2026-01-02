<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'branch_code',
        'name',
        'country',
        'city',
        'street',
        'manager_id',
        'status',
        'notes', 
    ];
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
