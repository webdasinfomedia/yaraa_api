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
        try {
            $data = [];
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
