<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Eloquent\Model;

class TenantSlaveUser extends Model
{
    use SoftDeletes;

    protected $connection = 'landlord';

    protected $fillable = [
        'email',
        'tenant_ids',
        'deleted_at',
        'default_tenant',
        'disabled_tenant_ids'
    ];

    protected $dates = ['deleted_at'];

    public function defaultTenant()
    {
        return $this->belongsTo(Tenant::class, 'default_tenant');
    }
}
