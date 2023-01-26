<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'access_token',
        'refresh_token',
        'expires_at'
    ];

    use HasFactory;
}
