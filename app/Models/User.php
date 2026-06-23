<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

#[Fillable([
    'name',
    'email',
    'password',
    'api_token',
    'avatar',
    'is_admin',
    'is_hr',
    'role',
    'is_active',
    'department',
    'position'
])]
#[Hidden([
    'password',
    'remember_token',
    'api_token'
])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_hr' => 'boolean',
        'is_active' => 'boolean',
    ];
}