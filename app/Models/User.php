<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property ?string $encrypted_login
 * @property mixed $encrypted_password
 */
class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'user_id',
        'username',
        'language',
        'encrypted_login',
        'encrypted_password',
    ];
}