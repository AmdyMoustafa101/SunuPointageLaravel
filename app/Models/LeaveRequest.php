<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'first_name', 
        'last_name', 
        'departement_id', 
        'type', 
        'start_date', 
        'end_date', 
        'reason', 
        'status'
    ];
}