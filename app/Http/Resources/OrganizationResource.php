<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\PunchDetail;
use App\Models\Task;
use App\Models\User;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $today = Carbon::today()->toDateString();
        $project = Project::get();
        $task = Task::get();
        $milestone = Milestone::get();
        $dt = Carbon::now()->startOfDay();
        $edt = Carbon::now()->endOfDay();
        $employeeRole = getRoleBySlug('employee');
        $adminRole = getRoleBySlug('admin');
        $employeeRoleId = $employeeRole ? $employeeRole->id : 0;
        $adminRoleId = $adminRole ? $adminRole->id : 0;
        $employee = User::whereIn('role_id', [$employeeRoleId, $adminRoleId])->get();

        return [
            "project" => [
                "current_projects" => $project->count(),
                "completed_projects" => $project->where('status', 'completed')->count(),
                "ongoing_projects" => $project->where('status', '!=', 'completed')->count(),
                "on-time" => $project->where('due_date', '>', $today)->count(),
                "delayed" => $project->where('due_date', '<', $today)->count(),
            ],
            "task" => [
                "current_task" => $task->count(),
                "completed_task" => $task->where('status', 'completed')->count(),
                "active_task" => $task->where('status', '!=', 'completed')->count(),
                "on-time" => $task->where('due_date', '>', $today)->count(),
                "delayed" => $task->where('due_date', '<', $today)->count(),
            ],
            "milestone" => [
                "total_milestone" => $milestone->count(),
                "completed_milestone" => $milestone->where('status', 'completed')->count(),
                "active_milestone" => $milestone->where('status', '!=', 'completed')->count(),
                "on-time" => $milestone->where('due_date', '>', $today)->count(),
                "delayed" => $milestone->where('due_date', '<', $today)->count(),
            ],
            "employee_attendance" => [
                "total_employee" => $employee->count(),
                "present_employee" => PunchDetail::whereBetween('created_at', [$dt, $edt])->groupBy('user_id')->get()->count(),
            ],
            "top_performance" => TopPerformanceResource::collection($employee),
        ];
    }
}
