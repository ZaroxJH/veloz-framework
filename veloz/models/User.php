<?php

namespace Veloz\Models;

use Veloz\Core\Model;
use Veloz\Permission\HasRoles;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

}