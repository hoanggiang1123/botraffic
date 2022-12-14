<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Http\Resources\UserCollection;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'picture',
        'role',
        'api',
        'assignable'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setPasswordAttribute($password) {
        $this->attributes['password'] = bcrypt($password);
    }

    public function members () {
        return $this->hasMany(User::class, 'refer_id');
    }

    public function banks () {
        return $this->hasMany(Bank::class, 'user_id');
    }

    public function listItems ($params) {
        $result = [];

        $perPage = isset($params['per_page']) ? (int) $params['per_page'] : 10000;

        $orderBy = isset($params['order_by']) ? $params['order_by'] : 'created_at';

        $order = isset($params['order']) ? $params['order'] : 'desc';

        $assignable = isset($params['assignable']) ? $params['assignable'] : '';

        $resp = self::query()
                ->when($assignable, function($query) use ($assignable) {
                    return $query->where('assignable', $assignable);
                })
                ->orderBy($orderBy, $order)->paginate($perPage);

        if ($resp) $result = new UserCollection($resp);

        return $result;
    }
}
