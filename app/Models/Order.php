<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'name',
        'amount',
        'khqr_payload',
        'khqr_md5',
        'khqr_status',
        'khqr_checked_at',
    ];

    
}
