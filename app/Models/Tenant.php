<?php

namespace App\Models;

use App\Exceptions\UserDisabledException;
use Exception;
use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Tenant extends Model
{
    // php artisan migrate --path=database/migrations/landlord --database=landlord

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'landlord';

    public const PROVIDER_AS = "appsumo";
    public const PROVIDER_PG = "pitchground";
    public const PROVIDER_DF = "dealfuel";
    public const PROVIDER_ST = "stpi";
    public const PROVIDER_CC = "free_coupon_code";

    protected $fillable = [
        'account_user_id',
        'domain',
        'business_name',
        'business_logo',
        'database',
        'setup',
        'user_limit',
        'created_by',
        'provider',
        'app_sumo_code_id',
        'pitchground_code_id',
        'dealfuel_code_id',
        'stpi_code_id',
        'coupon_code_id',
        'cancelled_at',
        'phone',
        'country',
        'subscription_id'
    ];

    protected $dates = [
        'cancelled_at',
    ];

    /**
     *
     */
    public function configure()
    {
        config([
            'database.connections.tenant.database' => $this->database,
        ]);

        DB::purge('tenant');

        DB::reconnect('tenant');

        Schema::connection('tenant')->getConnection()->reconnect();

        return $this;
    }

    /**
     *
     */
    public function use()
    {
        app()->forgetInstance('tenant');

        app()->instance('tenant', $this);

        return $this;
    }

    public function slave_users()
    {
        return $this->hasOne(TenantSlaveUser::class);
    }

    public static function findTenant($email)
    {
        try {
            // $slaveUser = TenantSlaveUser::withTrashed()->whereEmail($email)->firstOrFail();
            $slaveUser = TenantSlaveUser::whereEmail($email)->firstOrFail();
            // if ($slaveUser->trashed()) {
            if (in_array($slaveUser->default_tenant, $slaveUser->disabled_tenant_ids)) {
                throw new UserDisabledException('User is disabled, Please contact admin');
            }
            return $slaveUser->defaultTenant;
        } catch (UserDisabledException $e) {
            throw new Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new Exception('User account not found.');
        }
    }
}
