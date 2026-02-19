<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestRequestItem extends Model
{
    protected $table = 'test_request_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'test_id',
        'datetime_added',
    ];

    protected $casts = [
        'datetime_added' => 'datetime',
    ];

    /**
     * Get the parent test request.
     */
    public function testRequest()
    {
        return $this->belongsTo(TestRequest::class, 'request_id', 'id');
    }

    /**
     * Get the test associated with this item.
     */
    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id', 'test_id');
    }
}
