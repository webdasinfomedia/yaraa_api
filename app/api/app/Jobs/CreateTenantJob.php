<?php

namespace App\Jobs;

use App\Facades\CreateDPWithLetter;
use App\Models\LogLocation;
use App\Models\Tenant;
use App\Models\TenantSlaveUser;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class CreateTenantJob extends Job
{
    public $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //1.Create tenants table entry
        $dbName = strtolower(str_replace([" ", "."], ["_", "_"], $this->data["business_name"]));
        $tenantDatabase = env("TENANT_DB_PREFIX") . "{$dbName}" . "_" . getUniqueStamp();
        $tenant = Tenant::create([
            "account_user_id" => $this->data["account_user_id"],
            "domain" => $this->data["domain"],
            "business_name" => $this->data["business_name"],
            "database" => $tenantDatabase,
            "setup" => false,
            "user_limit" => intval($this->data["user_limit"]),
            "created_by" => $this->data["email"],
        ]);

        //2.Add user or update user in tenant slave user collection
        $slaveUser = TenantSlaveUser::whereEmail($this->data["email"])->first();
        if ($slaveUser) {
            $slaveUser->push('tenant_ids', $tenant->id);
            $slaveUser->default_tenant = $tenant->id;
            $slaveUser->save();
        } else {
            TenantSlaveUser::create([
                "email" => $this->data["email"],
                "tenant_ids" => [$tenant->id],
                "default_tenant" => $tenant->id,
                "disabled_tenant_ids" => [],
            ]);
        }

        //3.create database for tenant
        $client = new \MongoDB\Client("mongodb://admin:" . env('DB_PASSWORD') . "@127.0.0.1:27017");
        $db = $client->{$tenantDatabase};
        $db->createCollection('test');

        //4.run migration on new tenant database
        Artisan::call('tenants:migrate', [
            "tenant" => $tenant->id,
            "--seed" => true,
        ]);

        //5.create admin user record in tenants database
        $tenant->configure()->use();

        $imageName = 'user_images/' . getUniqueStamp() . '.png';
        $path = 'public/' . $imageName;
        $img = CreateDPWithLetter::create($this->data["email"]);
        Storage::put($path, $img->encode());

        $image_resize = Image::make(Storage::path($path));
        $image_resize->resize(48, 48); //before 60x60
        $fileFullName = $imageName;
        $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME)) . getUniqueStamp() . '_48x48.' . 'png';
        $image_resize->save(base_path('public/storage/user_images/' . $fileName), 60);

        $user = User::create([
            "name" => strtok($this->data["email"], '@'),
            "email" => $this->data["email"],
            "password" => $this->data["password"],
            "image" => $imageName,
            "image_48x48" => "user_images/{$fileName}",
            "is_verified" => true,
        ]);

        if (key_exists('lat', $this->data)) {
            LogLocation::create([
                "email" => $this->data["email"],
                "latitude" => $this->data["lat"] ?? null,
                "longitude" => $this->data["lon"] ?? null,
            ]);
        }

        if ($user) {
            if (key_exists('provider', $this->data)) {
                $tenant->provider = $this->data['provider'];
            }
            if (key_exists('subscription_id', $this->data)) {
                $tenant->subscription_id = $this->data['subscription_id']; //saved from stripewebhook onboarding
            }
            if (key_exists('cancelled_after_days', $this->data)) {
                $tenant->cancelled_at = Carbon::now()->add($this->data['cancelled_after_days'], 'day');
            }
            $tenant->setup = true;
            $tenant->save();
        }
    }
}
