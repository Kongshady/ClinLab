<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UicDirectoryPerson extends Model
{
    protected $table = 'uic_directory_people';

    protected $fillable = [
        'external_ref_id',
        'type',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'birth_date',
        'home_address',
        'email',
        'department_or_course',
        'raw_json',
        'last_synced_at',
    ];

    protected $casts = [
        'raw_json' => 'array',
        'birth_date' => 'date',
        'last_synced_at' => 'datetime',
    ];

    /* ── Accessors ── */

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    /* ── Scopes ── */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        $term = '%' . $term . '%';
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', $term)
              ->orWhere('last_name', 'like', $term)
              ->orWhere('email', 'like', $term)
              ->orWhere('external_ref_id', 'like', $term);
        });
    }

    /* ── Relationships ── */

    public function patient()
    {
        return $this->hasOne(Patient::class, 'external_ref_id', 'external_ref_id');
    }
}
