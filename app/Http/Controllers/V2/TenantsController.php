<?php

namespace App\Http\Controllers\V2;

use App\Models\Tenant;
use App\Http\Resources\AdminResource;

class TenantsController extends Controller
{
    public function tenants()
    {
        try {
            if (auth()->user()->isSuperAdmin()) {
                $tenants = Tenant::get();
                return AdminResource::collection($tenants)->additional(["error" => false, "message" => null]);
            }
            return "You dont have super admin access.";
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
