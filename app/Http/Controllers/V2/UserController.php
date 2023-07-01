<?php

namespace App\Http\Controllers\V2;

use App\Http\Resources\OrganizationResource;

class UserController extends Controller
{
    public function index()
    {
        try {
            return (new OrganizationResource(auth()->user()))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
