<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'bookingId',
        'bookingSlot',
        'customerId',
        'bookingFromDate',
        'bookingEndDate',
        'created_at',
        'updated_at'
    ];
    
}
