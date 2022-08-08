<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip', 'keyword', 'url', 'device_type', 'device_name', 'os', 'browser'
    ];
}
