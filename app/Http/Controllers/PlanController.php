<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    public function getPlanDetails()
    {
        //get account user id
        $accountUserId = app('tenant')->account_user_id ?? null;

        if ($accountUserId != null) {
            //fetch active plan details from accounts
            $url = getAccountsUrl();
            $url .= "/api/apifunction";

            $params = [
                'account_user_id' => $accountUserId,
                'type' => 'getactiveplan',
                'product_slug' => 'yaraa_manager',
            ];
            $response = Http::withOptions(["verify" => false])->post($url, $params)->throw()->json();

            //set response with plan details
            if (key_exists('status', $response) && filter_var($response['status'], FILTER_VALIDATE_BOOLEAN) == true) {
                $this->setResponse(false, null);
                $this->_response['data'] = $response;
                return response()->json($this->_response, 200);
            }
        }

        $this->setResponse(true, "No plan found");
        return response()->json($this->_response, 400);
    }

    public function getPlanUpgradeLink()
    {
        try {
            $accountUserId = app('tenant')->account_user_id ?? null;

            $url = getAccountsUrl();
            $apiUrl = $url . "/api/apifunction";

            $params = [
                'account_user_id' => $accountUserId,
                'type' => 'generateaccesstoken',
                'product_slug' => 'niftyyaraamanager',
                'action' => 'planupgrade',
            ];

            $response = Http::withOptions(["verify" => false])->post($apiUrl, $params)->throw()->json();

            if (key_exists('accessToken', $response)) {

                $redirectUrl  = $url . '/token/' . $response['accessToken'];

                $this->setResponse(false, null);
                $this->_response['data'] = ["url" => $redirectUrl];
                return response()->json($this->_response, 200);
            }

            throw new \Exception('User not found');
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function planUpdated(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_user_id' => 'required',
            'user_limit' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            Tenant::where('account_user_id', $request->account_user_id)->update(['user_limit' => $request->user_limit]);
            return response()->json(["status" => true, "message" => "Plan updated successfully"]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
