<?php

namespace App\Http\Controllers\V2;

use App\Events\UserDeleteEvent;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\MemberResource;
use App\Http\Resources\TaskListResource;
use App\Http\Resources\DashboardResource;
use App\Http\Resources\UserBasicResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MemberTaskListSectionResource;
use App\Http\Resources\UserProfile;
use App\Models\Tenant;
use App\Services\Niftysol;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManagerStatic as Image;
use Jenssegers\Mongodb\Eloquent\Builder;

class UserController extends Controller
{
    public function index()
    {
        try {
            return response()->json('V2 Dashboard API Response.');
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
