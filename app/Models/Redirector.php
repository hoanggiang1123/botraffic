<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class Redirector extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'url'
    ];

    public function setNameAttribute ($name) {
        $this->attributes['name'] = $name;
        $this->attributes['slug'] = Str::slug($name);
    }
}
