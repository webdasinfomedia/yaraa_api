<?php

use App\Jobs\SyncZoomTokenAcrossTenantJob;
use App\Models\NotificationSetting;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantSlaveUser;
use App\Models\User;
use App\Models\UserApp;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

if (!function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param  string $path
     * @return string
     */
    function app_path($path = '')
    {
        return app('path') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

function getRoleBySlug($slug)
{
    $role = Role::where('slug', $slug)->first();
    if ($role) {
        return $role;
    }
    return false;
}

function getRoleById($id)
{
    $role = Role::find($id);
    if ($role) {
        return $role;
    }
    throw new \Exception('Role Not Found.');
}

function getUniqueStamp()
{
    return (int)round(microtime(true) * 1000 * rand(100, 999));
};

function getUserTimezone()
{
    return auth()->user()->timezone ?? 'UTC'; //Asia/Kolkata
}

function getUserDate(Carbon $date)
{
    $date->setTimezone(getUserTimezone());
    return Carbon::create($date->toDateTimeString());
}

function isValidTimezone($timezone = null)
{
    return in_array($timezone, timezone_identifiers_list());
}

function createTenantSlaveUser($email)
{
    // TenantSlaveUser::create([
    //     "email" => $email,
    //     "tenant_id" => app('tenant')->id
    // ]);

    $slaveUser = TenantSlaveUser::whereEmail($email)->first();
    if ($slaveUser) {
        $slaveUser->push('tenant_ids', app('tenant')->id);

        if ($slaveUser->default_tenant == null) {
            $slaveUser->default_tenant = app('tenant')->id;
            $slaveUser->save();
        }
    } else {
        TenantSlaveUser::create([
            "email" => $email,
            "tenant_ids" => [app('tenant')->id],
            "default_tenant" => app('tenant')->id,
            "disabled_tenant_ids" => [],
        ]);
    }
}

function isAlreadyTenantSlaveUser($email)
{
    return TenantSlaveUser::where('email', $email)->exists();
}

function globalRoutes()
{
    return [
        '/api/onboarding',
        '/api/user/login',
        '/api/password/email',
        '/api/sso_login',
        '/api/auth/linkedin/login',
        '/api/auth/login/verify',
        '/api/auth/apple/login',
        '/test',
    ];
}

function getNotificationSettings($key)
{
    return NotificationSetting::firstOrFail()->{$key};
}

function isUserLimitReached()
{
    $totalUsers = \App\Models\TenantSlaveUser::where('tenant_ids', app('tenant')->id)->count();

    if (app('tenant')->user_limit) {
        return $totalUsers >= app('tenant')->user_limit ? true : false;
    }

    return false;
}

function renewZoomAccessToken()
{
    // $zoomToken = Setting::where('type', 'zoom_token')->first();
    $zoomToken = UserApp::where("user_id", Auth::user()->id)->where('type', 'zoom_token')->first();

    if ($zoomToken && isAppEnabled('zoom')) {

        /** Renew access token if 50 minutes have passed or exit with true. Function used in middleware **/
        if ($zoomToken->updated_at->diffInMinutes(Carbon::now()) <= 50) {
            return true;
        }

        $token = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(config('services.zoom.client_id') . ':' . config('services.zoom.client_secret')),
            'content-type' => 'application/x-www-form-urlencoded',
        ])
            ->asForm()
            ->post(
                'https://zoom.us/oauth/token/',
                [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $zoomToken->refresh_token,
                ]
            );

        $token = json_decode($token->getBody()->getContents(), true);

        if (array_key_exists('error', $token)) {
            // $settings = Setting::where('type', 'apps')->first();
            $userApps = UserApp::where("user_id", auth()->id())->where('type', 'apps')->first();
            $userApps->pull('enabled_apps', 'zoom');

            throw new Exception('Error accessing zoom.');
        }

        // Setting::where('type', 'zoom_token')
        UserApp::where('user_id', auth()->id())->where('type', 'zoom_token')
            ->update([
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'],
                'scope' => $token['scope'],
            ]);

        dispatch(new SyncZoomTokenAcrossTenantJob($token));
    } else {
        throw new Exception('Zoom is disabled, Please enable from apps.');
    }
}

function deauthorizeZoomAccessToken($userId)
{
    return true;
    // $zoomToken = Setting::where('type', 'zoom_token')->first();
    $zoomToken = UserApp::where("user_id", $userId)->where('type', 'zoom_token')->first();

    if ($zoomToken) {

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(config('services.zoom.client_id') . ':' . config('services.zoom.client_secret')),
            'content-type' => 'application/x-www-form-urlencoded',
        ])
            ->asForm()
            ->post(
                'https://zoom.us/oauth/revoke',
                [
                    'token' => $zoomToken->access_token,
                ]
            );

        $response = json_decode($response->getBody()->getContents(), true);

        if (array_key_exists('error', $response)) {
            throw new Exception('Error accessing zoom.');
        }
    }
}

/** Check marketplace App is enabled */
function isAppEnabled($app)
{
    $userApps = UserApp::where('user_id', auth()->id())->where('type', 'apps');
    if ($userApps->exists()) {
        return in_array($app, $userApps->first()->enabled_apps) ? true : false;
    }

    return false;
}

function cloneUser($email, $cloneToTenant)
{
    /** Get user from another tenant/organization **/
    $slaveUser = TenantSlaveUser::whereEmail($email)->first();
    $slaveUser->push('tenant_ids', $cloneToTenant);
    $uniqueTenantIds = array_unique($slaveUser->tenant_ids);
    $slaveUser->tenant_ids = $uniqueTenantIds;
    $slaveUser->save();
    Tenant::find($slaveUser->default_tenant)->configure()->use();
    $user = User::whereEmail($email)->first();

    /** Clone user to new tenant/organization **/
    Tenant::find($cloneToTenant)->configure()->use();
    $cloneUser = $user->replicate([
        'role_id',
        "fav_projects",
        "archived_projects",
        'task_ids',
        'project_ids',
        'favourite_projects',
        'todos',
        'archived_projects',
        'conversation_ids',
        'task_ids',
    ]);

    $cloneUser->created_at = Carbon::now();
    $cloneUser->save();

    return $cloneUser;
}

function getTimezoneAbbreviation($timezone)
{
    $dateTime = new DateTime();
    $dateTime->setTimeZone(new DateTimeZone($timezone));
    return $dateTime->format('T');
}

function getAccountsUrl()
{
    $url = (app('env') == "production")
        ? "https://accounts.niftysol.com"
        : "https://test.accounts.niftysol.com";

    return $url;
}

function getDefaultUserModel()
{
    return [
        'id' => null,
        'name' => 'Deleted User',
        'email' => null,
        'image' => 'deleted-user.png'
    ];
}

function getLocationFromCoordinates($lat,$long){
    try{
        // https://nominatim.openstreetmap.org/reverse.php?lat=23.0607826&lon=72.5113202&zoom=18&format=jsonv2
        $url = "https://nominatim.openstreetmap.org/reverse.php?lat=23.0607826&lon=72.5113202&format=jsonv2";
        $client = new Client();
        $result = $client->get($url,[
            "lat" => $lat,
            "lon" => $long,
            "format" => "jsonv2"
        ]);

        $result = json_decode($result->getBody()->getContents(),true);
        return $result['address'];
    } catch(\Exception $e) {
        return null;
    }
}