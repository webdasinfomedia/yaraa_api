<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use MongoDB\BSON\UTCDateTime;
use App\Http\Resources\UserBasicResource;
use App\Models\PunchDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jenssegers\Mongodb\Eloquent\Builder;

class ReportsController extends Controller
{
    public function getTaskCompletionReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:all,project,unassigned',
            'project_id' => 'required_if:type,project|exists:projects,_id',
            'start_date' => 'date',
            'end_date' => 'date'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            if ($request->type == 'all') {
                $members = User::whereHas('role', function (Builder $query) {
                    $query->where('slug', 'employee')->orWhere('slug', 'admin');
                })->get();

                foreach ($members as $member) {
                    $totalTasks = $member->projectTasks();
                    // $totalCompletedTasks = $member->projectTasks();

                    // if ($request->has('start_date') && $request->start_date != null) {
                    //     $totalTasks = $totalTasks->where('start_date', '>=', $request->start_date);
                    //     // $totalCompletedTasks = $totalCompletedTasks->where('start_date', '>=', $request->start_date);
                    // }

                    // if ($request->has('end_date') && $request->end_date != null) {
                    //     $totalTasks = $totalTasks->where('end_date', '<=', $request->end_date);
                    //     // $totalCompletedTasks = $totalCompletedTasks->where('end_date', '<=', $request->end_date);
                    // }

                    $data[] = [
                        "name" => $member->name,
                        "email" => $member->email,
                        "image" =>  url('storage/' . $member->image_48x48),
                        "total_tasks" => $totalTasks->count(),
                        "total_completed_task" => $totalTasks->where('status', 'completed')->count()
                    ];
                }
            }

            if ($request->type == 'project') {
                $project = Project::find($request->project_id);
                $projectMembers = $project->members;
                foreach ($projectMembers as $member) {
                    $totalTasks = $project->tasks()->where('assignee', $member->id);
                    // $totalCompletedTasks = $project->tasks()->where('assignee', $member->id);

                    // if ($request->has('start_date') && $request->start_date != null) {
                    //     $totalTasks = $totalTasks->where('start_date', '>=', $request->start_date);
                    //     // $totalCompletedTasks = $totalCompletedTasks->where('start_date', '>=', $request->start_date);
                    // }

                    // if ($request->has('end_date') && $request->end_date != null) {
                    //     $totalTasks = $totalTasks->where('end_date', '<=', $request->end_date);
                    //     // $totalCompletedTasks = $totalCompletedTasks->where('end_date', '<=', $request->end_date);
                    // }

                    $data[] = [
                        "name" => $member->name,
                        "email" => $member->email,
                        "image" =>  url('storage/' . $member->image_48x48),
                        "total_tasks" => $totalTasks->count(),
                        "total_completed_task" => $totalTasks->where('status', 'completed')->count(),
                    ];
                }
            }

            if ($request->type == 'unassigned') {
                $members = User::all();

                foreach ($members as $member) {
                    $totalTasks = $member->nonProjectTasks();
                    // $totalCompletedTasks = $member->nonProjectTasks();

                    // if ($request->has('start_date') && $request->start_date != null) {
                    //     $totalTasks = $totalTasks->where('start_date', '>=', $request->start_date);
                    //     // $totalCompletedTasks = $totalCompletedTasks->where('start_date', '>=', $request->start_date);
                    // }

                    // if ($request->has('end_date') && $request->end_date != null) {
                    //     $totalTasks = $totalTasks->where('end_date', '<=', $request->end_date);
                    //     // $totalCompletedTasks = $totalCompletedTasks->where('end_date', '<=', $request->end_date);
                    // }

                    $data[] = [
                        "name" => $member->name,
                        "email" => $member->email,
                        "image" =>  url('storage/' . $member->image_48x48),
                        "total_tasks" => $totalTasks->count(),
                        "total_completed_task" => $totalTasks->where('status', 'completed')->count()
                    ];
                }
            }

            $this->_response['data'] = $data;
            $this->setResponse(false, null);

            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getTimesheetReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'project' => 'required',
            'start_date' => 'date',
            'end_date' => 'date',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $projects = $request->project != 'all'
                ? Project::where("_id", $request->project)->get()
                : Project::all();
            $projectCounter = 0;
            foreach ($projects as $project) {
                $response["projects"][$projectCounter] = [
                    "name" => $project->name,
                    "image" => $project->image ? url('storage/' . $project->image) : null
                ];

                $users = $request->email != 'all'
                    ? $project->members()->where('email', $request->email)->get()
                    : $project->members;

                $userCounter = 0;
                $response["projects"][$projectCounter]["tasks"] = [];
                foreach ($users as $user) {

                    if ($request->has('start_date') && $request->start_date != null && $request->has('end_date') && $request->end_date != null) {
                        $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
                        $startDate = $startDate->startOfDay();

                        $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
                        $endDate = $endDate->endOfDay();

                        $tasks = $project->tasks()->whereBetween('due_date', [$startDate, $endDate])->where('assignee', $user->id)->get();
                    } elseif ($request->has('start_date') && $request->start_date != null && !$request->has('end_date')) {
                        $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date);
                        $startDate = $startDate->startOfDay();
                        $tasks = $project->tasks()->where('due_date', '>=', $startDate)->where('assignee', $user->id)->get();
                    } elseif (!$request->has('start_date') && $request->has('end_date') && $request->end_date != null) {
                        $endDate = Carbon::createFromFormat('Y-m-d', $request->end_date);
                        $endDate = $endDate->startOfDay();
                        $tasks = $project->tasks()->where('due_date', '<=', $endDate)->where('assignee', $user->id)->get();
                    } else {
                        $tasks = $project->tasks()->where('assignee', $user->id)->get();
                    }

                    foreach ($tasks as $task) {
                        $response["projects"][$projectCounter]["tasks"][] = [
                            "user_name" => $user->name,
                            "user_email" => $user->email,
                            'user_profile' => $user->image_48x48 ? url('storage/' . $user->image_48x48) : null,
                            "task" => $task->name,
                            "due_date" => $task->due_date,
                            "my_total_work_hours" => $task->getMyTotalWorkHours(),
                            "progress_percent" => $task->getProgress()
                        ];
                    }
                    $userCounter++;
                }
                $projectCounter++;
            }

            $this->_response['data'] = $response;
            $this->setResponse(false, "");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getAttendanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'email|exists:users,email',
            'start_date' => 'date|date_format:Y-m-d',
            'end_date' => 'date|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            // $startDate = $request->has('start_date') ? date('Y-m-d', $request->get('start_date')) : date('Y-m-d');
            // $enfDate = $request->has('end_date') ? date('Y-m-d', $request->get('end_date')) : date('Y-m-d');
            if ($request->has('email')) {
                $users = User::where('email', $request->email)->select('id')->first();
                $users = [$users->id];
            } else {
                $users = User::select('id')->get()->pluck('id');
            }

            // DB::enableQueryLog();
            // $punchDetails = PunchDetail::whereIn('user_id', $users)->groupBy('user_id')->get();
            // $queries = DB::getQueryLog();
            // dd($queries);
            // collectionName.aggregate([{$group:{_id:"$user_id",minVal: {$min: "$created_at"},maxVal: {$max: "$created_at"},}}])
            // $punchDetails = PunchDetail::whereRaw(

            // $punchDetails = PunchDetail::raw(function ($collection) {
            //     return $collection->aggregate([
            //         [
            //             '$match' => [
            //                 'user_id' => [
            //                     '$in' => ['60f667984a2b00008a00307g', '60f667984a2b00008a00307e']
            //                 ]
            //             ]
            //         ],
            //         [
            //             '$group' => [
            //                 '_id' => '$user_id',
            //                 'minVal' => [
            //                     '$min' => '$created_at'
            //                 ],
            //                 'data' => [
            //                     '$push' => '$$ROOT'
            //                 ],
            //                 'maxVal' => [
            //                     '$max' => '$updated_at'
            //                 ]
            //                 // { $unwind: "$data" },
            //             ]
            //         ],
            //         // [
            //         //     '$unwind' => '$data'
            //         // ]
            //     ]);
            // });
            // dd($punchDetail->minVal->toDateTime()->format('Y-m-d H:i:s'));

            $startDate = date('Y-m-d H:i:s', strtotime("$request->start_date" . " 00:00:00"));
            $endDate = date('Y-m-d H:i:s', strtotime("$request->end_date" . " 23:59:59"));

            $startDate = new UTCDateTime(new \DateTime($startDate));
            $endDate = new UTCDateTime(new \DateTime($endDate));

            $punchDetails = PunchDetail::with('user:name,email,image_48x48')
                ->whereIn('user_id', $users)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->get();

            // $punchDetails = $punchDetails->groupBy('user_id');
            $punchDetails = $punchDetails->groupBy(function ($item) {
                return (new \DateTime($item->created_at))->format('Y-m-d');
            });

            $user = [];

            foreach ($punchDetails as $key => $punchDetail) {

                $userPunchDetails = $punchDetail->groupBy('user_id');
                foreach ($userPunchDetails as $userPunchDetail) {

                    $firstPunchIn = $userPunchDetail->first()->punch_in;
                    $lastPunchOut = $userPunchDetail->last()->punch_out;
                    $totalWorkMinutes = $userPunchDetail->sum('total_work_hour');

                    /** get last punch out to check break hours if last punch out is not set then set last punch in as punch out **/
                    $lastPunchIn = $userPunchDetail->last()->punch_in;
                    $lastPunchOut = $lastPunchOut != null ?  $lastPunchOut : $lastPunchIn;
                    if (!is_null($lastPunchOut)) {
                        $totalMinutes = $firstPunchIn->floatDiffInMinutes($lastPunchOut);
                        $totalMinutes = floatval(number_format($totalMinutes, 2));
                        $breakMinutes = $totalMinutes - $totalWorkMinutes;
                    }

                    $user[] = [
                        "punch_in" =>  $userPunchDetail->first()->punch_in_usertimezone, //$firstPunchIn,
                        "punch_out" =>  $userPunchDetail->last()->punch_out_usertimezone, //$lastPunchOut,
                        "total_work_hour" => gmdate("H:i", ($totalWorkMinutes * 60)) . " Hours",
                        "total_break_hours" => gmdate("H:i", ($breakMinutes * 60)) . " Hours",
                        "user_details" => new UserBasicResource($userPunchDetail->first()->user),
                    ];
                }
            }

            $this->_response['data'] = $user;
            $this->setResponse(false, "");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
