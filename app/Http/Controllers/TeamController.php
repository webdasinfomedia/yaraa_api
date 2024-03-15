<?php

namespace App\Http\Controllers;

use App\Events\UserDeleteEvent;
use App\Jobs\CreateMemberInviteAcknowledgeMail;
use App\Jobs\CreateMemberInviteRegisterMail;
use App\Models\Team;
use App\Models\TenantSlaveUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:100",
            "members" => "required",
            "members.*" => "exists:users,email",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $team = Team::create($request->except('members'));

            foreach ($request->members as $member) {
                $user = User::where('email', $member)->first();
                if ($user) {
                    $team->members()->attach($user);
                }
            }

            $team->save();

            $this->setResponse(false, 'Team Created Successfully.');
            return response()->json($this->_response, 201);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function inviteMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'note' => 'present|max:100'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $user = $this->registerUser($request->email);
            $token = Crypt::encryptString(app('tenant')->id);
            $user->push('invite_member_token', $token);

            $teamName = "Team";
            $notes = $request->note;

            if (!$user->is_verified) {
                // dispatch(new CreateMemberInviteRegisterMail($user, Auth::user(), 'Project', $this->name, $token));
                dispatch(new CreateMemberInviteRegisterMail($user, auth()->user(), 'Team', $teamName, $token, $notes));
            } else {
                if (getNotificationSettings('email') || getNotificationSettings('email') == "true") {
                    dispatch(new CreateMemberInviteAcknowledgeMail($user, auth()->user(), 'Team', $teamName, null, null, $notes));
                }
            }

            $this->setResponse(false, "Invitation Send Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function disableUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email,deleted_at,NULL',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            /** Delete user from Customer Table **/
            $user = User::whereEmail($request->email)->first();
            $user->delete();

            \Log::debug(auth()->user()->email . ' disabled ' . $request->email);

            /** Delete user from Parent slave users Table to stop login **/
            $tenantSlaveUser = TenantSlaveUser::whereEmail($request->email)->first();
            // $tenantSlaveUser->delete();
            $tenantSlaveUser->push('disabled_tenant_ids', app('tenant')->id);

            /** If user have multiple organizations then make 2nd organization as default for login **/
            if (sizeof($tenantSlaveUser->tenant_ids) > 1) {
                $tenants = $tenantSlaveUser->tenant_ids;
                $key = array_search(app('tenant')->id, $tenants);
                unset($tenants[$key]);
                $tenants = array_values($tenants);
                $tenantSlaveUser->default_tenant = $tenants[0];
                $tenantSlaveUser->save();
            }

            $this->setResponse(false, "User Disabled Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function enableUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            /** Delete user from Customer Table **/
            $user = User::withTrashed()->whereEmail($request->email)->first();
            $user->restore();

            /** Delete user from Parent slave users Table to stop login **/
            // $tenantSlaveUser = TenantSlaveUser::withTrashed()->whereEmail($request->email)->first();
            // $tenantSlaveUser->restore();
            $tenantSlaveUser = TenantSlaveUser::whereEmail($request->email)->first();
            $tenantSlaveUser->pull('disabled_tenant_ids', app('tenant')->id);

            $this->setResponse(false, "User Enabled Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function deleteUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email|exists:users,email',
        ]);

        $email = $request->email;

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $user = User::withTrashed()->whereEmail($email)->first();
            event(new UserDeleteEvent($user));

            $this->setResponse(false, "User Deleted successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
