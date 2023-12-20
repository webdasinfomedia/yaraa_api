<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\PunchDetail;
use App\Models\Task;
use App\Models\User;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminDashboardResource extends JsonResource
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
        $dayStart = Carbon::now()->startOfDay();
        $dayEnd = Carbon::now()->endOfDay();
        $employeeRole = getRoleBySlug('employee');
        $adminRole = getRoleBySlug('admin');
        $employeeRoleId = $employeeRole ? $employeeRole->id : 0;
        $adminRoleId = $adminRole ? $adminRole->id : 0;
        $employee = User::whereIn('role_id', [$employeeRoleId, $adminRoleId])->get();

        //top performance
        $top_performance = $employee->sortByDesc(function ($employee) {
            return $employee->projectTasks()->where('status', 'completed')->filter(function ($task) {
                if (!is_null($task->end_date) && $task->end_date <= $task->due_date) {
                    return true;
                }
                return false;
            })->count();
        })->take(5);

        //completed projects
        $completedProjects = $project->where('status', 'completed');

        $ontimeCompletedProjects = $completedProjects->filter(function ($project) {
            return strtotime($project->end_date) < strtotime($project->due_date);
        });

        $delayCompletedProjects = $completedProjects->filter(function ($project) {
            return strtotime($project->end_date) > strtotime($project->due_date);
        });

        //completed tasks
        $completedTasks = $task->where('status', 'completed');
        $delayCompletedTasks = $completedTasks->filter(function ($task) {
            return strtotime($task->end_date) > strtotime($task->due_date);
        });

        $ontimeCompletedTasks = $completedTasks->filter(function ($task) {
            return strtotime($task->end_date) < strtotime($task->due_date);
        });


        return [
            // "total_projects" => $project->count(),
            "active_projects" => [
                "total_active_projects" => $project->where('status', '!=', 'completed')->count(),
                "onTime_projects" => $project->where('status', '!=', 'completed')->where('due_date', '>=', $today)->count(),
                "delay_projects" => $project->where('status', '!=', 'completed')->where('due_date', '<', $today)->count(),
            ],
            "completed_projects" => [
                "total_completed_projects" => $completedProjects->count(),
                "onTime_completed_projects" =>  $ontimeCompletedProjects->count(),
                "delay_completed_projects" =>  $delayCompletedProjects->count(),
            ],
            "milestone" => [
                "total_milestone" => $milestone->count(),
                "completed_milestone" => $milestone->where('status', 'completed')->count(),
                "active_milestone" => $milestone->where('status', '!=', 'completed')->count(),
                "on-time" => $milestone->where('due_date', '>', $today)->count(),
                "delayed" => $milestone->where('due_date', '<', $today)->count(),
            ],
            // "total_tasks" => $task->count(),
            "active_tasks" => [
                "total_active_tasks" => $task->where('status', '!=', 'completed')->count(),
                "onTime_tasks" => $task->where('status', '!=', 'completed')->where('due_date', '>=', $today)->count(),
                "delay_tasks" => $task->where('status', '!=', 'completed')->where('due_date', '<', $today)->count(),
            ],
            "completed_tasks" => [
                "total_completed_tasks" => $completedTasks->count(),
                "onTime_completed_tasks" => $ontimeCompletedTasks->count(),
                "delay_completed_tasks" => $delayCompletedTasks->count(),
            ],
            "employee_attendance" => [
                "total_employee" => $employee->count(),
                "present_employee" => PunchDetail::whereBetween('created_at', [$dayStart, $dayEnd])->groupBy('user_id')->get()->count(),
            ],
            "top_performance" => TopPerformanceResource::collection($top_performance),
        ];
    }
}
