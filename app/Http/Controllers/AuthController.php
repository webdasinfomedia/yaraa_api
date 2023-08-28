<?php

namespace App\Http\Controllers;

use App\Exceptions\ZoomCustomException;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\SendVerificationEmail;
use App\Facades\CreateDPWithLetter;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserBasicResource;
use App\Http\Resources\AdminBasicResource;
use App\Jobs\EnableAppJob;
use App\Jobs\SyncZoomTokenAcrossTenantJob;
use App\Jobs\UpdateUserFcmTokenJob;
use App\Models\Setting;
use App\Models\Settings;
use App\Models\Tenant;
use App\Models\TenantSlaveUser;
use App\Models\UserApp;
use App\Models\SuperAdmin;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManagerStatic as Image;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /** 
     * @param $request
     * validate params and create user 
     *  @return user resource
     */

    private $_successMessage;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:6',
            'email' => 'exclude_if:is_invited,true|required|email|unique:users',
            'verify_token' => 'exclude_unless:is_invited,true|required',
            'name' => 'required|',
            "image" => "filled|mimes:jpg,png|max:512",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $tenant = (Crypt::decryptString(urldecode($request->verify_token)));
            $tenant = Tenant::find($tenant)->configure()->use();
            $img = $request->image;

            if (!$request->has('is_invited')) {
                $user = User::create($request->all());
            } else {
                $user =  User::where('email', $request->email)->first();

                if (!is_array($user->invite_member_token) && !in_array($request->verify_token, $user->invite_member_token)) {
                    $this->setResponse(true, 'The selected verify token is invalid.');
                    return response()->json($this->_response, 200);
                }
            }

            if (!empty($img)) {
                $picName = getUniqueStamp() . '.' . $request->image->extension();
                $imageName = 'user_images/' . $picName;
                $img->storeAs('public', $imageName);
                // $image_resize = Image::make($request->image->getRealPath())->resize(60, 60, function ($constraint) {$constraint->aspectRatio();});
                $image_resize = Image::make($request->image->getRealPath());
                $image_resize->resize(48, 48); //before 60x60
                $filename =  getUniqueStamp() . '_48x48.png';
                $image_resize->save(base_path('public/storage/user_images/' . $filename), 60);
                $user->image_48x48 = "user_images/{$filename}";
            } else {
                $imageName = 'user_images/' . getUniqueStamp() . '.png';
                $path = 'public/' . $imageName;
                $img = CreateDPWithLetter::create($request->name);
                Storage::put($path, $img->encode());
            }

            $user->image = $imageName;

            if (!$request->has('is_invited')) {
                $user->email = $request->email;
                $user->name = $request->name;
                $user->designation = $request->designation;
                $user->password = Hash::make($request->password);
                $user->is_verified = false;
                $token = Str::random(64);
                $user->verify_token = [$token];
                $this->_successMessage = "Verification Link Sent On Email.Please Verify To Complete Registration.";
                Mail::to($user)->queue(new SendVerificationEmail($user, $token));
            } else {
                $user->name = $request->name;
                $user->designation = $request->designation;
                $user->password = Hash::make($request->password);
                $user->is_verified = true;
                $user->verified_at = Carbon::now();
                $user->pull('invite_member_token', $request->verify_token);
                $this->_successMessage = "Registered Successfully.";
            }

            /** Add user timezone  **/
            if ($request->has('timezone')) {
                $user->timezone = isValidTimezone($request->timezone) ? $request->timezone : 'Asia/Kolkata';
            } else {
                $user->timezone = 'Asia/Kolkata';
            }

            $user->save();

            $user->invite_member_token = [];
            $user->save();

            return $this->login($request);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);
        try {

            $admin = SuperAdmin::where('email', $credentials['email'])->first();

            if ($admin && Hash::check($credentials['password'], $admin->password)) {
                $token = Auth::guard('admin-api')->claims(['role' => 'super_admin'])->attempt($credentials);
                auth()->login($admin);
                return $this->adminWithToken($token);
            } else {
                $tenant = Tenant::findTenant($credentials['email'])->configure()->use();

                // if (!$token = auth()->setTTL(86400)->attempt($credentials)) {
                // if (!$token = auth()->attempt($credentials)) {

                if (!$token = JWTAuth::customClaims(['host' => $tenant->domain])->attempt($credentials)) {
                    $this->setResponse(true, 'Invalid credentials.');
                    return response()->json($this->_response, 401);
                }

                if (!auth()->user()->is_verified) {
                    return response()->json(['error' => true, 'message' => 'Your have not verified your email.Please verify your account from the invite email.'], 401);
                }

                //check if plan is cancelled on 3rd party provider e.g: AppSumo
                if (!is_null(app('app')->tenant->cancelled_at) && app('app')->tenant->cancelled_at < Carbon::now()) {
                    return response()->json(['error' => true, 'message' => 'Your account has been disabled.'], 401);
                }
                return $this->respondWithToken($token);
            }
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                // "message" => "Invalid credentials or user is disabled.",
                "message" => $e->getMessage(),
                "data" => null
            ]);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return  new UserResource(auth()->user());
        // return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        $this->setResponse(false, 'Successfully logged out.');
        return response()->json($this->_response, 200);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = Auth::user();

        dispatch(new UpdateUserFcmTokenJob(request()->only(['fcm_token', 'is_web']), $user->id, app('tenant')->id));

        if (request()->has('timezone')) {
            $user->timezone = isValidTimezone(request()->get('timezone')) ? request()->get('timezone') : 'Asia/Kolkata';
        } else {
            $user->timezone = 'Asia/Kolkata';
        }

        $user->save();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => intval(auth()->factory()->getTTL()),
            'data' => new UserBasicResource(auth()->user()),
            'error' => false,
            'message' => $this->_successMessage,
        ]);
    }

    public function verifySocialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:google,linkedin,apple',
            'email' => 'required',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $providerUser = Socialite::driver($request->provider)->userFromToken($request->token);

            if ($request->email != $providerUser->getEmail()) throw new \Exception('User email is incorrect.');

            /* disabled register with social account
            $user = User::query()->firstOrNew(['email' => $providerUser->getEmail()]);
            
            if (!$user->exists) {
                $user->name = $providerUser->getName();
                // $user->role_id = getRoleBySlug('admin')->id;
                $user->provider = $request->provider;
                $extension = $request->has('image') ? $request->image->extension() : 'png';
                $image = time() . '.' . $extension;
                $imageName = 'user_images/' . $image;
                $path = 'public/' . $imageName;
                $img = $providerUser->getAvatar() != null ? file_get_contents($providerUser->getAvatar()) : CreateDPWithLetter::create($providerUser->getName())->encode();
                Storage::put($path,$img);

                $user->image = $imageName;
                $user->save();
            } */

            // else{  currently user will grant to login if the email is already registered against any provider.
            //     if ($user->provider != $request->provider) {
            //         throw new \Exception("Email-id already registered.");
            //     }
            // }

            /**Login with tenant */
            $tenant = Tenant::findTenant($request->email)->configure()->use();

            $token = Auth::login(User::where('email', $providerUser->getEmail())->first());
            $token = JWTAuth::customClaims(['host' => $tenant->domain])->fromUser(auth()->user());
            $user = Auth::user();
            if (!$user->is_verified) {
                $user->is_verified = true;
                $user->save();
            }
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getLinkedinAccessToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "code" => "required"
        ]);

        return response()->json(['ok'], 200);
        exit;
        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            // $providerUser = Socialite::driver('linkedin')->stateless()->scopes(['email', 'profile', 'openid'])->user();
            $providerUser = Socialite::driver('linkedin')->getAccessTokenResponse($request->code);
            \Log::debug($providerUser);

            $data = [
                "provider" => 'linkedin',
                "email" => $providerUser->email,
                "token" => $providerUser->token
            ];
            $request = Request::create('api/auth/login/verify', 'post', $data);

            return app()->dispatch($request);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getZoomAccessToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "code" => "required"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            if (isAppEnabled('zoom')) {
                throw new \Exception('Zoom App Already Installed.');
            }

            $token = Socialite::driver('zoom')->getAccessTokenResponse($request->code);
            // $settings = Setting::where('type', 'zoom_token')->exists();
            $userZoomApp = UserApp::where("user_id", Auth::user()->id)->where('type', 'zoom_token')->exists();
            if (!$userZoomApp) {
                UserApp::create([
                    'user_id' => Auth::user()->id,
                    'type' => 'zoom_token',
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'],
                    'scope' => $token['scope'],
                ]);
            } else {
                UserApp::where("user_id", Auth::user()->id)->where('type', 'zoom_token')
                    ->update([
                        'access_token' => $token['access_token'],
                        'refresh_token' => $token['refresh_token'],
                        'scope' => $token['scope'],
                    ]);
            }

            dispatch(new EnableAppJob('zoom', Auth::user()->id));
            dispatch(new SyncZoomTokenAcrossTenantJob($token));

            $this->setResponse(false, 'Zoom Enabled successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getAppleAccessToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "code" => "required"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $token = Socialite::driver('apple')->getAccessTokenResponse($request->code);
            // $providerUser = Socialite::driver('linkedin')->stateless()->user();
            // $providerUser = Socialite::driver($request->provider)->userFromToken($request->token);
            return response()->json($token);
            // return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
    public function getAppleCode()
    {
        return Socialite::driver('apple')->redirect();
    }

    // public function generateAppleSecret()
    // {
    /** uncomment in composer.json "web-token/jwt-framework": "^2.2"  to use this function*/
    //     # Save your private key from Apple in a file called `key.txt`
    //     $key_file = url(Storage::url('key.txt'));
    //     // dd($key_file);
    //     # Your 10-character Team ID
    //     $team_id = 'W67624MHZP';

    //     # Your Services ID, e.g. com.aaronparecki.services
    //     $client_id = 'com.yaraa.project.task.manager';

    //     # Find the 10-char Key ID value from the portal
    //     $key_id = '2HVCY5BW3A';

    //     $algorithmManager = new AlgorithmManager([new ES256()]);
    //     $jwsBuilder = new JWSBuilder($algorithmManager);

    //     $jws = $jwsBuilder
    //         ->create()
    //         ->withPayload(json_encode([
    //             'iat' => time(),
    //             'exp' => time() + 86400 * 180,
    //             'iss' => $team_id,
    //             'aud' => 'https://appleid.apple.com',
    //             'sub' => $client_id
    //         ]))
    //         ->addSignature(JWKFactory::createFromKeyFile($key_file), [
    //             'alg' => 'ES256',
    //             'kid' => $key_id
    //         ])
    //         ->build();

    //     $serializer = new CompactSerializer();
    //     $token = $serializer->serialize($jws, 0);

    //     dd($token);
    // }

    protected function adminWithToken($token)
    {
        $user = Auth::user();
        // dd($user);
        if (request()->has('timezone')) {
            $user->timezone = isValidTimezone(request()->get('timezone')) ? request()->get('timezone') : 'Asia/Kolkata';
        } else {
            $user->timezone = 'Asia/Kolkata';
        }

        $user->save();

        return response()->json([
            // 'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => intval(auth()->factory()->getTTL()),
            'data' => new AdminBasicResource(auth()->user()),
            'error' => false,
            'message' => $this->_successMessage,
        ]);
    }
}
