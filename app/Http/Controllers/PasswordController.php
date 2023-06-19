<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Jobs\SendForgetPasswordEmail;
use App\Models\PasswordReset;
use App\Models\Tenant;
use App\Models\TenantSlaveUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $token = Str::random(60);
            $token = hash('sha256', $token);
            $user = TenantSlaveUser::where('email', $request->email)->firstOrFail();

            PasswordReset::create([
                'email' => $request->email,
                'token' => $token,
                'default_tenant' => $user->tenant_id
            ]);

            dispatch(new SendForgetPasswordEmail($user, $token));

            $this->setResponse(false, 'Reset password email sent successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, "No user found with a provided email.");
            return response()->json($this->_response, 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "token" => "required",
            "password" => "required|confirmed|min:8",
            "password_confirmation" => "required",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $user = PasswordReset::where('token', $request->token)->first();

            if ($user) {
                /** Update password for all organizations for this user **/
                $slaveUser = TenantSlaveUser::where('email', $user->email)->firstOrFail();
                foreach ($slaveUser->tenant_ids as $tenant_id) {
                    Tenant::find($tenant_id)->configure()->use();
                    $user = User::withTrashed()->whereEmail($user->email)->first();
                    $user->password = Hash::make($request->password);
                    $user->is_verified = true;
                    $user->save();
                }

                /** Update password on accounts portal **/
                //api to fire

                PasswordReset::where('email', $user->email)->delete();

                $this->setResponse(false, 'Password updated successfully.');
                return response()->json($this->_response, 200);
            }

            $this->setResponse(false, 'URL is expired or invalid.Please try again.');
            return response()->json($this->_response, 404);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
