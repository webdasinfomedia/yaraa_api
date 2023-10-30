<?php

namespace App\Http\Controllers;

use App\Jobs\CreateTenantJob;
use App\Jobs\TestJob;
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
}
