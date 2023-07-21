<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Http\Resources\AdminResource;

class AdminLoginController extends Controller
{
    public function index()
    {
        try {
            $tenants = Tenant::get();
            if ($credentials['email'] == "priyal@dasinfomedia.com" && $credentials['password'] == "12345678") {
                return AdminResource::collection($tenants)->additional(["error" => false, "message" => null]);
            }
            else{
                return "You dont have admin access.";
            }
        } catch (\Exception $e) {
            return response()->json([
                "error" => true,
                "message" => $e->getMessage(),
                "data" => null
            ]);
        }
    }
}
