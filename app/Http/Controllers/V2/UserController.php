<?php

namespace App\Http\Controllers\V2;

use App\Http\Resources\OrganizationResource;
use App\Http\Resources\AdminDashboardResource;

class UserController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            $userResource = new OrganizationResource($user);

            $response = $userResource;

            $adminRole = getRoleBySlug('admin');
            $adminRoleId = $adminRole ? $adminRole->id : 0;

            if ($user->role_id === $adminRoleId) {
                $adminResource = new AdminDashboardResource($user);
                $response = $adminResource;
            }

            return $response->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
