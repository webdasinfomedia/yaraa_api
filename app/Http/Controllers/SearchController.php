<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArchiveList;
use App\Http\Resources\MilestoneResource;
use App\Http\Resources\ProjectListResource;
use App\Http\Resources\TaskListResource;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:all,projects,milestones,tasks,archive',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $allItems = new \Illuminate\Database\Eloquent\Collection;

            if ($request->type == 'all' || $request->type == 'projects') {
                $projects = auth()->user()->projects()->where('name', 'like', "%{$request->content}%")->get();
                $projects->each(function ($project) {
                    $project->type = "project";
                });
                $projects = ProjectListResource::collection($projects);
                $allItems = $allItems->merge($projects);
            }

            $projectIds = auth()->user()->projects->pluck('id');
            if ($request->type == 'all' || $request->type == 'archive') {
                $trashedProject = Project::onlyTrashed()->where('created_by', auth()->id())->where('name', 'like', "%{$request->content}%")->get();
                $archivedProject =  auth()->user()->projects()->onlyArchived();
                //$archivedProjectIds = $archivedProject->pluck('_id')->toArray(); //search in my archived only.
                $archivedProject = $archivedProject->where('name', 'like', "%{$request->content}%")->get();
                $allTrashedProjects = $trashedProject->merge($archivedProject);
                $allTrashedProjects->each(function ($allTrashedProjects) {
                    $allTrashedProjects->type = "trashed_project";
                });
                $allTrashedProjects = ArchiveList::collection($allTrashedProjects);
                $allItems = $allItems->merge($allTrashedProjects);
            }

            if ($request->type == 'all' || $request->type == 'milestones') {
                $milestones = Milestone::where('title', 'like', "%{$request->content}%")->whereIn('project_id', $projectIds)->get();
                $milestones->each(function ($milestones) {
                    $milestones->type = "milestone";
                });
                $milestones = MilestoneResource::collection($milestones);
                $allItems = $allItems->merge($milestones);
            }

            if ($request->type == 'all' || $request->type == 'tasks') {
                $tasks = auth()->user()->userTasks($request->content);
                $tasks->each(function ($tasks) {
                    $tasks->type = "task";
                });
                $tasks = TaskListResource::collection($tasks);
                $allItems = $allItems->merge($tasks);
            }

            if ($request->type == 'all' || $request->type == 'archive') {
                $trashedTask = Task::onlyTrashed()->where('name', 'like', "%{$request->content}%")->get();
                $archivedTask = auth()->user()->tasks()->onlyArchived()->where('name', 'like', "%{$request->content}%")->get();
                $allTrashedTasks = $trashedTask->merge($archivedTask);
                $allTrashedTasks->each(function ($allTrashedTasks) {
                    $allTrashedTasks->type = "trashed_tasks";
                });
                $allTrashedTasks = ArchiveList::collection($allTrashedTasks);
                $allItems = $allItems->merge($allTrashedTasks);
            }

            $allItems = $allItems->shuffle();

            return response()->json([
                "error" => false,
                "data" => $allItems,
                "message" => null
            ]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
