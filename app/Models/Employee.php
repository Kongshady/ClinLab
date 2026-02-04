<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employee';
    protected $primaryKey = 'employee_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'section_id',
        'firstname',
        'middlename',
        'lastname',
        'username',
        'password',
        'position',
        'role_id',
        'role',
        'status_code',
    ];

    protected $hidden = [
        'password',
        'password_hash',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'section_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getFullNameAttribute()
    {
        return trim("{$this->firstname} {$this->middlename} {$this->lastname}");
    }

    public function softDelete($employeeId = null)
    {
        $this->is_deleted = 1;
        $this->deleted_at = now();
        $this->deleted_by = $employeeId;
        return $this->save();
    }
}
