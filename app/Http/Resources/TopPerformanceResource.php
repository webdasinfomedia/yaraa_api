<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Resources\Json\JsonResource;

class TopPerformanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:all,project,unassigned',
            'project_id' => 'required_if:type,project|exists:projects,_id',
            'start_date' => 'date',
            'end_date' => 'date'
        ]);

        if ($validator->fails()) {
            return [
                'error' => $validator->errors()->all(),
            ];
        }

        try {
            $data = [];
            if ($request->type == 'all') {
                $members = User::all();

                foreach ($members as $member) {
                    $totalTasks = $member->projectTasks();

                    if ($request->has('start_date') && $request->start_date != null) {
                        $totalTasks = $totalTasks->where('start_date', '>=', $request->start_date);
                    }

                    if ($request->has('end_date') && $request->end_date != null) {
                        $totalTasks = $totalTasks->where('end_date', '<=', $request->end_date);
                    }

                    $data[] = [
                        "name" => $member->name,
                        "email" => $member->email,
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

                    if ($request->has('start_date') && $request->start_date != null) {
                        $totalTasks = $totalTasks->where('start_date', '>=', $request->start_date);
                    }

                    if ($request->has('end_date') && $request->end_date != null) {
                        $totalTasks = $totalTasks->where('end_date', '<=', $request->end_date);
                    }

                    $data[] = [
                        "name" => $member->name,
                        "email" => $member->email,
                        "total_tasks" => $totalTasks->count(),
                        "total_completed_task" => $totalTasks->where('status', 'completed')->count(),
                    ];
                }
            }

            if ($request->type == 'unassigned') {
                $members = User::all();

                foreach ($members as $member) {
                    $totalTasks = $member->nonProjectTasks();

                    if ($request->has('start_date') && $request->start_date != null) {
                        $totalTasks = $totalTasks->where('start_date', '>=', $request->start_date);
                    }

                    if ($request->has('end_date') && $request->end_date != null) {
                        $totalTasks = $totalTasks->where('end_date', '<=', $request->end_date);
                    }

                    $data[] = [
                        "name" => $member->name,
                        "email" => $member->email,
                        "total_tasks" => $totalTasks->count(),
                        "total_completed_task" => $totalTasks->where('status', 'completed')->count()
                    ];
                }
            }

            return [
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
