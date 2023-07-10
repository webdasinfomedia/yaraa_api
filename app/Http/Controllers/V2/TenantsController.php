<?php

namespace App\Http\Controllers\V2;

use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Http\Resources\tenantsResource;

class TenantsController extends Controller
{
    public function tenants()
    {
        try {
            $tenants = Tenant::get();
            return tenantsResource::collection($tenants)->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
