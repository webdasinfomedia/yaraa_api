<?php

namespace App\Http\Resources;

use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $todayStart = new DateTime(date('Y-m-d'));
        $todayEnd = new DateTime('+1 day');

          
        $todayDueTasks = $this->tasks()
                       ->whereBetween('due_date',[$todayStart, $todayEnd])
                        ->whereNull('end_date')
                        ->get();
        
        $previousPendingTasks = $this->tasks()
                                ->whereDay('due_date','<>',date('d'))
                                ->whereNotNull('start_date')
                                ->where('start_date', '<', $todayEnd)
                                ->whereNull('end_date')
                                ->get();                         

        $todaysTask = $todayDueTasks->merge($previousPendingTasks);
        $todays_task = TaskResource::collection($todaysTask);


        return [
            'name' => $this->name,
            'email' => $this->email,
            'about_me' => $this->about_me,
            'designation' => $this->designation,
            'role' => $this->role->slug,
            'image' => url('storage/'.auth()->user()->image),
            'recently_assigned_task' => TaskResource::collection($this->tasks()->orderBy('created_at', 'desc')->limit(3)->get()),
            'todays_task' => $todays_task,
            'projects' => ProjectResource::collection($this->projects),
        ];
    }
}
