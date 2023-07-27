<?php

namespace App\Http\Controllers\V2;

use App\Http\Resources\ProjectListResource;
use App\Models\Project;

class AllProjectController extends Controller
{
    public function index()
    {
        try {
            if (auth()->user()->isAdmin()) {
                $project = Project::get()->sortByDesc('created_at');
                return ProjectListResource::collection($project)->additional(['error' => false, 'message' => null]);
            } else {
                return "You don't have admin access.";
            }
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
