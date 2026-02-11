<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';
    public $timestamps = false;

    protected $fillable = [
        'role_name',
        'display_name',
        'description',
        'status_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'role_id', 'role_id');
    }
}
