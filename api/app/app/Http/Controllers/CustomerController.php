<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerListResource;
use App\Http\Resources\CustomerProjectListResource;
use App\Jobs\CreateActivityJob;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Builder;

class CustomerController extends Controller
{
    public function index()
    {
        try {
            $customers = User::whereHas('role', function (Builder $query) {
                $query->where('slug', 'customer');
            })->get();
            return (CustomerListResource::collection($customers))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:customers,email',
            // 'project_id' => 'required|exists:projects,_id',
            "name" => "nullable|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:100",
            "mobile_no" => "nullable|numeric",
            "additional_details" => "nullable|max:255"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $randomPassword = Str::random(8);

            $customer = User::create([
                "email" => $request->email,
                "password" => $randomPassword,
                "role_id" => getRoleBySlug('customer')->id,
                "project_id" => $request->project_id,
                "name" => $request->name,
                "mobile_no" => $request->mobile_no,
                "additional_details" => $request->additional_details,
                "is_verified" => true,
            ]);

            if ($customer) {
                createTenantSlaveUser($request->email);
            }
            $this->setResponse(false, "Customer created successfully.");

            /** Create activity log and create chat group, notification & FCM notification */
            $customerName = $request->name != null ? $request->name : $request->email;
            $activityData = [
                "activity" => "New Customer {$customerName} Added. ",
                "activity_by" => Auth::id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["name" => $customerName, "email" => $request->email, "assigned_by" => Auth::user()->name, 'password' => $randomPassword]),
                "activity" => "customer_created",
            ];
            $log = [
                "email" => $request->email,
                "password" => $request->password,
            ];

            \Log::debug(json_encode($log));

            dispatch(new CreateActivityJob($activityData));

            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getCurrentProjects()
    {
        try {
            $projects = auth()->user()->AsCustomerProjects()->whereNull('end_date')->get();
            return (CustomerProjectListResource::collection($projects))->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getPastProjects()
    {
        try {
            $projects = auth()->user()->AsCustomerProjects()->where('status', 'completed')->get();
            return (CustomerProjectListResource::collection($projects))->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
