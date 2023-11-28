<?php

namespace App\Http\Controllers;

use App\Events\UserDeleteEvent;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\MemberResource;
use App\Http\Resources\TaskListResource;
use App\Http\Resources\DashboardResource;
use App\Http\Resources\UserBasicResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MemberTaskListSectionResource;
use App\Http\Resources\UserProfile;
use App\Models\Tenant;
use App\Services\Niftysol;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManagerStatic as Image;
use Jenssegers\Mongodb\Eloquent\Builder;

class UserController extends Controller
{

    public function index()
    {
        try {
            return (new DashboardResource(auth()->user()))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "filled|required",
            "designation" => "nullable",
            "image" => "filled|mimes:jpg,png"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            Auth::user()->update($request->except('image'));

            if ($request->has('image')) {
                $uploadedLogo = $this->uploadFile($request->image, 'user_images');
                if ($uploadedLogo != false) {
                    $this->removeFile(Auth::user()->image);
                    Auth::user()->update(["image" => $uploadedLogo]);

                    /** store 48 x 48 thumb image **/
                    $image_resize = Image::make($request->image->getRealPath());
                    $image_resize->resize(48, 48); //before 60x60
                    $fileFullName = $request->image->getClientOriginalName();
                    $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME)) .  getUniqueStamp() . '_48x48.' .  $request->image->extension();
                    $image_resize->save(base_path('public/storage/user_images/' . $fileName), 60);
                    Auth::user()->update(["image_48x48" => "user_images/{$fileName}"]);
                }
                // $img = $request->image;
                // $image = time() . '.' .$request->image->extension();;
                // $imageName = 'user_images/' . $image;
                // $img->storeAs('public', $imageName);                
                // Auth::user()->update(["image" => $imageName]);
            }
            $this->_response['data'] = new UserBasicResource(auth()->user());
            $this->setResponse(false, 'Profile updated successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function memberList()
    {
        $users = User::whereHas('role', function (Builder $query) {
            $query->where('slug', 'employee')->orWhere('slug', 'admin');
        })->get();

        return MemberResource::collection($users)->additional(['error' => false, 'message' => null]);
    }

    public function inviteMember($email)
    {
        try {
            $userExists = User::where('email', $email)->exists();
            if (!$userExists) {
                // $user = User::create(["email" => $email]);       
                // dispatch(new InviteMember($user,auth()->user()));
                // $this->setResponse(false, 'Invite email sent successfully.');
            }

            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function verifyEmail($token)
    {
        $fields = ["token" => $token];

        $validator = Validator::make($fields, [
            "token" => "alpha_num|exists:users,verify_token"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $user = User::where('verify_token', $token)->first();
            $user->is_verified = true;
            $user->verified_at = Carbon::now(); //new \dateTime(); new \MongoDB\BSON\UTCDateTime(new \DateTime('now'));         
            $user->verify_token = null;
            $user->save();

            $this->setResponse(false, 'Account verified successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function showMemberTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            // $user = User::where('email', $request->email)->first();
            $user = User::withTrashed()->where('email', $request->email)->first();
            $publicTask = auth()->user()->isAdmin() ? $user->userTasks() : $user->userPublicTasks();

            // return (MemberTaskListSectionResource::collection($publicTask))->additional(['error' => false,'message' => null]);
            return (TaskListResource::collection($publicTask))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function savePreferences(Request $request)
    {
        try {
            if (auth()->user()->preferences != null) {
                auth()->user()->preferences->update($request->all());
            } else {
                auth()->user()->preferences()->create($request->all());
            }

            $this->setResponse(false, "User Preference Saved.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getProfile()
    {
        try {
            return (new UserProfile(auth()->user()))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function ssoLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $data = [
                "type" => "verifytoken",
                "token" => urldecode($request->token)
            ];

            $niftysol = new Niftysol;
            $response = $niftysol->call($data);
            $response = json_decode($response, true);

            if (empty($response)) {
                $this->setResponse(true, ["invalid token or user not found"]);
                return response()->json($this->_response, 400);
            }

            $email = $response['user_email'];
            if(key_exists('provider',$response)){
                $tenant = Tenant::where('created_by', $email)->where('provider',$response['provider'])->firstOrFail()->configure()->use();
            }else{
                $user_id = $response['account_user_id'];
                $tenant = Tenant::where('account_user_id', strval($user_id))->firstOrFail()->configure()->use();
            }

            $user = User::where('email', $email)->firstOrFail();

            /** Login user and add custom claim to access token to find tenant later on other apis **/
            $token = Auth::claims(['host' => $tenant->domain])->login($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'data' => new UserBasicResource(auth()->user()),
                'error' => false,
            ]);
        } catch (\Exception $e) {
            $this->setResponse(true, "invalid token or user not found.");
            return response()->json($this->_response, 500);
        }
    }

    public function deleteUser()
    {
        $email = auth()->user()->email;

        try {
            $user = User::whereEmail($email)->first();
            event(new UserDeleteEvent($user));

            \Log::debug($user->email . 'deleted him self');

            $this->setResponse(false, "User Deleted successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
