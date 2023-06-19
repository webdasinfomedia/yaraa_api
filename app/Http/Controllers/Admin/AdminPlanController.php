<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminPlanController extends Controller
{
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
}
