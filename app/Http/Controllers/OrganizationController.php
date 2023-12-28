<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserBasicResource;
use App\Models\Tenant;
use App\Models\TenantSlaveUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrganizationController extends Controller
{
    public function getOrganizationList()
    {
        try {

            $slaveUser = TenantSlaveUser::whereEmail(auth()->user()->email)->first();

            $response = [];
            foreach ($slaveUser->tenant_ids as $tenantId) {
                $tenant = Tenant::find($tenantId);
                $response[] = [
                    'business_name' => $tenant->business_name,
                    'tenant_id' => $tenant->id,
                    'is_current' => app('tenant')->id == $tenant->id, //auth()->payload()->get('host') == $tenant->domain
                    'is_owner' => $tenant->created_by == $slaveUser->email ? true : false
                ];
            }
            $this->setResponse(false, null);
            $this->_response['data'] = $response;
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function switchOrganization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|exists:landlord.tenants,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            /** check current user is part of tenant **/
            $tenantExists = TenantSlaveUser::whereEmail(auth()->user()->email)
                ->where('tenant_ids', $request->tenant_id)
                ->count();
            if (!$tenantExists) {
                throw new \Exception('You dont belong to this organization.');
            }

            /** check user is not disabled in the tenant **/
            $isDisabled = TenantSlaveUser::whereEmail(auth()->user()->email)->where('disabled_tenant_ids', $request->tenant_id)->count();
            if ($isDisabled) {
                throw new \Exception('You are disabled in this organization.Please Contact Admin.');
            }


            /** generate access token **/
            $tenant = Tenant::find($request->tenant_id)->configure()->use();
            $user = User::whereEmail(auth()->user()->email)->first();

            /** Pass true to force the token to be blacklisted "forever" **/
            auth()->logout(true);

            /** login a user in new organization  **/
            $token = auth()->claims(['host' => $tenant->domain])->login($user);

            /** Update default organization **/
            TenantSlaveUser::whereEmail($user->email)->update(['default_tenant' => $request->tenant_id]);

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
