<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Models\DealFuelCode;
use App\Models\TenantSlaveUser;
use App\Rules\DealFuelCodeCheck;
use App\Facades\CreateDPWithLetter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserBasicResource;
use App\Mail\WelcomeProviderSignupMail;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\ImageManagerStatic as Image;

class DealFuelSignupController extends Controller
{
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|',
            'email' => 'exclude_if:is_invited,true|required|email|unique:users',
            'password' => 'required|min:6',
            'code' => ['required', new DealFuelCodeCheck],
            'phone' => 'required',
            'country' => 'required'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            //1.Create tenants table entry
            $firstName = trim(explode(" ", $request->name)[0]);
            $dbName = strtolower(str_replace([" ", "."], ["_", "_"], $firstName));
            $tenantDatabase = env("TENANT_DB_PREFIX") . "{$dbName}" . "_" . getUniqueStamp();

            $code = DealFuelCode::whereCode($request->code)->first();

            $tenant = Tenant::create([
                "account_user_id" => null,
                "domain" => $firstName . getUniqueStamp(),
                "business_name" => $firstName . "'s Company",
                "database" => $tenantDatabase,
                "setup" => false,
                "user_limit" => 5, //$code->user_limit,
                "created_by" => $request->email,
                "provider" => Tenant::PROVIDER_DF,
                "dealfuel_code_id" => null,
                "phone" => $request->phone,
                "country" => $request->country
            ]);

            //2.Add user or update user in tenant slave user collection
            $slaveUser = TenantSlaveUser::whereEmail($request->email)->first();
            if ($slaveUser) {
                $slaveUser->push('tenant_ids', $tenant->id);
                $slaveUser->default_tenant = $tenant->id;
                $slaveUser->save();
            } else {
                TenantSlaveUser::create([
                    "email" => $request->email,
                    "tenant_ids" => [$tenant->id],
                    "default_tenant" => $tenant->id,
                    "disabled_tenant_ids" => [],
                ]);
            }

            //3.create database for tenant
            $connectionString = "mongodb://" . env('DB_USERNAME') . ":" . env('DB_PASSWORD') . "@" . env('DB_HOST') . ":" . env('DB_PORT') . "";
            $client = new \MongoDB\Client($connectionString);
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
            $img = CreateDPWithLetter::create($request->email);
            Storage::put($path, $img->encode());

            $image_resize = Image::make(Storage::path($path));
            $image_resize->resize(48, 48); //before 60x60
            $fileFullName = $imageName;
            $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME)) . getUniqueStamp() . '_48x48.' . 'png';
            $image_resize->save(base_path('public/storage/user_images/' . $fileName), 60);

            $user = User::create([
                "name" => $request->name,
                "email" => $request->email,
                "password" => $request->password,
                "image" => $imageName,
                "image_48x48" => "user_images/{$fileName}",
                "is_verified" => true,
            ]);

            if ($user) {

                Mail::to($user->email)->send(new WelcomeProviderSignupMail($user->name));

                //redeem code
                $code->redeemed = "yes";
                $code->redeemed_at = Carbon::now();
                $code->save();

                //update tenant
                $tenant->setup = true;
                $tenant->dealfuel_code_id = $code->id;
                $tenant->save();
            }

            /** login a user **/
            $token = auth()->claims(['host' => $tenant->domain])->login($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'data' => new UserBasicResource(auth()->user()),
                'error' => false,
                'message' => null,
            ]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
