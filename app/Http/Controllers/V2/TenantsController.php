<?php

namespace App\Http\Controllers\V2;

use App\Models\Tenant;
use App\Models\TenantSlaveUser;
use App\Http\Resources\AdminResource;

class TenantsController extends Controller
{
    public function tenants()
    {
        try {
            $tenants = Tenant::get();
            return AdminResource::collection($tenants)->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
