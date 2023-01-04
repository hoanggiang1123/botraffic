<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BadIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip', 'count', 'user_id'
    ];
}
