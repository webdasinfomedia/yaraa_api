<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class PasswordReset extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'email',
        'token',
        'tenant_id',
    ];
    
}
