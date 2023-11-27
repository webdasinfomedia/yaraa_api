<?php

namespace App\Http\Controllers;

use App\Jobs\CreateTenantJob;
use App\Jobs\TestJob;
use App\Mail\SendStripeOnboardingSuccessfulMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class OnboardingController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_user_id' => 'required',
            'business_name' => 'required',
            'domain' => 'required',
            'email' => 'required',
            'password' => 'required',
            'provider' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try{
            // config([
            //     'database.connections.tenant.database' => 'yaraa_master',
            // ]);
            // DB::purge('tenant');
            // DB::reconnect('tenant');
            // Schema::connection('tenant')->getConnection()->reconnect();

            // app()->instance('master_job', 'yes'); //setting variable to change db to master when executing job

            dispatch(new CreateTenantJob($request->all()));
            
            $this->_response = ["error" => false, "message" => "Account Setup is in process, it will take couple of minutes."];
            return response()->json($this->_response, 200);
            
        } catch(\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
        
    }

    public function freeGoogleMarketPlaceSignup(Request $request){
        $validator = Validator::make($request->all(), [
            'business_name' => 'required',
            'name' => 'required',
            'email' => 'required',
            'provider' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try{
            $randomPassword = Str::random(10);

            $data["account_user_id"] = null;
            $data["domain"] = str_replace(" ","_",$request->name) . getUniqueStamp();
            $data["business_name"] = $request->bussiness_name;
            $data["user_limit"] = 10;
            $data["email"] = $request->email;
            $data["password"] = $randomPassword;
            $data["name"] = $request->name;
            $data["provider"] = $request->provider;
            $data["lat"] = $request->lat;
            $data["lon"] = $request->lon;
            dispatch(new CreateTenantJob($data));

            $mailData = [
                "name" => $request->name,
                "email" => $request->email,
                "password" => $randomPassword
            ];

            /** queue an email to send to customer **/
            Mail::to($request->email)->send(new SendStripeOnboardingSuccessfulMail($mailData));
            
            $this->_response = ["error" => false, "message" => "Account Setup is in process, it will take couple of minutes."];
            return response()->json($this->_response, 200);
            
        } catch(\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
