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
