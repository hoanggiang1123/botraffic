<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip', 'keyword_id', 'status', 'code'
    ];

    public function keyword () {
        return $this->belongsTo(Keyword::class, 'keyword_id');
    }
}
