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
        $project = $this->getProjects('all');
        $projectIds = $project->pluck('id');
        $task = $this->getTasks('all');
        $milestone = Milestone::whereIn('project_id', $projectIds)->get();
        $dt = Carbon::now()->startOfDay();
        $edt = Carbon::now()->endOfDay();
        $employeeRole = getRoleBySlug('employee');
        $adminRole = getRoleBySlug('admin');
        $employeeRoleId = $employeeRole ? $employeeRole->id : 0;
        $adminRoleId = $adminRole ? $adminRole->id : 0;
        $employee = User::whereIn('role_id', [$employeeRoleId, $adminRoleId])->get();

        $top_performance = $employee->sortByDesc(function ($employee) {
            return $employee->projectTasks()->where('status', 'completed')->filter(function ($task) {
                if (!is_null($task->end_date) && $task->end_date <= $task->due_date) {
                    return true;
                }
                return false;
            })->count();
        })->take(5);

        return [
            "project" => [
                "total_projects" => $project->count(),
                "completed_projects" => $project->where('status', 'completed')->count(),
                "ongoing_projects" => $project->where('status', '!=', 'completed')->count(),
                "on-time" => $project->where('due_date', '>', $today)->count(),
                "delayed" => $project->where('due_date', '<', $today)->count(),
            ],
            "task" => [
                "total_task" => $task->count(),
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
            "top_performance" => TopPerformanceResource::collection($top_performance),
            // "top_performance" => TopPerformanceResource::collection($employee)->sortByDesc('ontime_completed_task')->take(5),
        ];
    }
}
