<?php

namespace App\Http\Controllers\V2;

use App\Http\Resources\OrganizationResource;
use App\Http\Resources\AdminDashboardResource;

class UserController extends Controller
{
    public function index()
    {
        try {

            if (auth()->user()->isAdmin()) {
                $response = new AdminDashboardResource(auth()->user());
            } else {
                $response = new OrganizationResource(auth()->user());
            }
            return $response->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
