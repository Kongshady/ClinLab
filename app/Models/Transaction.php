<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transaction';
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'or_number',
        'client_designation',
        'datetime_added',
        'status_code',
    ];

    protected $casts = [
        'datetime_added' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'client_id', 'patient_id');
    }
}
