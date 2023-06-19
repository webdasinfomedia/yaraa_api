<?php

namespace App\Http\Controllers;

use App\Events\TaskArchived;
use App\Events\TaskUnarchived;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Resources\ArchiveList;
use App\Jobs\CreateActivityJob;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Validator;

class ArchiveController extends Controller
{
    public function archiveProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:projects,_id,deleted_at,NULL',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $project = Project::find($request->id);
            if ($project == null) {
                throw new \exception('Project Not Found.');
            }
            if ($project->createdBy(auth()->user()->id)) {
                $project->delete(); //globally archive project if created by same user

                /** Create activity log and create chat group, notification & FCM notification */
                $activityData = [
                    "activity" => "Project {$project->name} Deleted",
                    "activity_by" => auth()->id(),
                    "activity_time" => Carbon::now(),
                    "activity_data" => json_encode(["project_id" => $project->id, "author_name" => auth()->user()->name]),
                    "activity" => "project_deleted",
                ];

                dispatch(new CreateActivityJob($activityData));
            } else {
                $project->archive();
                $this->createArchiveDate('project', $project->id);

                // auth()->user()->localArchiveProject()->attach($project);
                // if(auth()->user()->localArchiveProject()->exists($project))
                // {
                //     auth()->user()->pull('project_ids', $project->id);
                //     $project->members()->detach(auth()->user());
                //     $this->createArchiveDate('project', $project->id);
                // }
            }


            $this->setResponse(false, 'Project Moved to Archive.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function unArchiveProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:projects,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::withTrashed()->withArchived()->find($request->id);
            if ($project->createdBy(auth()->user()->id)) {
                $project->restore();

                /** Create activity log and create chat group, notification & FCM notification */
                $activityData = [
                    "activity" => "Project {$project->name} Restored",
                    "activity_by" => auth()->id(),
                    "activity_time" => Carbon::now(),
                    "activity_data" => json_encode(["author_name" => auth()->user()->name, 'project_id' => $project->id]),
                    "activity" => "project_restore",
                ];

                dispatch(new CreateActivityJob($activityData));
            } else {
                $project->unArchive();
                $this->deleteArchiveDate('project', $project->id);

                // auth()->user()->localArchiveProject()->detach($project);
                // // !in_array($project->id,auth()->user()->project_ids)
                // //     ? auth()->user()->push('project_ids', $project->id)
                // //     : null ;
                // $project->members()->attach(auth()->user());
                // $this->deleteArchiveDate('project',$project->id);

            }
            $this->setResponse(false, 'Project Restored Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function archiveTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tasks,_id,deleted_at,NULL',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->id);
            if ($task == null) {
                throw new \exception('Task Not Found.');
            }
            if ($task->createdBy(auth()->user()->id)) {
                $task->delete(); //globally archive project if created by same user

                /** Create activity log and create chat group, notification & FCM notification */
                $activityData = [
                    "activity" => "Task {$task->name} Deleted",
                    "activity_by" => auth()->id(),
                    "activity_time" => Carbon::now(),
                    "activity_data" => json_encode(["task_name" => $task->name, "author_name" => auth()->user()->name, 'task_id' => $task->id, 'recipients' => $task->assignedTo->pluck('id')->toArray()]),
                    "activity" => "task_deleted",
                ];

                dispatch(new CreateActivityJob($activityData));
            } else {
                $task->archive();
                $this->createArchiveDate('task', $task->id);
            }

            event(new TaskArchived($task));

            $this->setResponse(false, 'Task Moved to Archive.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function unArchiveTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::withTrashed()->withArchived()->find($request->id);
            if ($task->createdBy(auth()->user()->id)) {
                $task->restore();

                /** Create activity log and create chat group, notification & FCM notification */
                $activityData = [
                    "activity" => "Task {$task->name} Restored",
                    "activity_by" => auth()->id(),
                    "activity_time" => Carbon::now(),
                    "activity_data" => json_encode(["author_name" => auth()->user()->name, 'task_id' => $task->id]),
                    "activity" => "task_restore",
                ];

                dispatch(new CreateActivityJob($activityData));
            } else {
                $task->unArchive();
                $this->deleteArchiveDate('task', $task->id);
            }

            event(new TaskUnarchived($task));

            $this->setResponse(false, 'Task Restored Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function itemsList()
    {
        try {
            $globalArchiveProjects = Project::onlyTrashed()->where('created_by', auth()->id())->get();
            $globalArchiveProjects->each(function ($project) {
                $project->archived_at = $project->deleted_at;
                $project->archived_timestamp = $project->archived_at ? $project->archived_at->getTimestamp() : null;
                $project->type = 'project';
            });

            $localArchiveProjects = auth()->user()->projects()->onlyArchived()->get();
            $localArchiveProjects->each(function ($project) {
                $project->archived_at = auth()->user()->archives()->where('module', 'project')->where('module_id', $project->id)->first()->created_at ?? null;
                $project->archived_timestamp = $project->archived_at ? $project->archived_at->getTimestamp() : null;
                $project->type = 'project';
            });

            $globalArchiveTasks = Task::onlyTrashed()->where('created_by', auth()->id())->get();
            $globalArchiveTasks->each(function ($task) {
                $task->archived_at = $task->deleted_at;
                $task->archived_timestamp = $task->archived_at ? $task->archived_at->getTimestamp() : null;
                $task->type = 'task';
            });

            // $localArchiveTasks = auth()->user()->localArchiveTask;
            $localArchiveTasks = auth()->user()->tasks()->onlyArchived()->get();
            $localArchiveTasks->each(function ($task) {
                $task->archived_at = auth()->user()->archives()->where('module', 'task')->where('module_id', $task->id)->first()->created_at ?? null;
                $task->archived_timestamp = $task->archived_at ? $task->archived_at->getTimestamp() : null;
                $task->type = 'task';
            });

            $archivedProjects = $globalArchiveProjects->merge($localArchiveProjects);
            $archivedTasks = $globalArchiveTasks->merge($localArchiveTasks);

            $allArchived = $archivedProjects->merge($archivedTasks);
            $allArchived = $allArchived->sortByDesc('archived_timestamp');

            return (ArchiveList::collection($allArchived))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    private function createArchiveDate($module, $moduleId)
    {
        if (auth()->user()->archives()->where('module', $module)->where('module_id', $moduleId)->count() === 0) {
            auth()->user()->archives()->create([
                'user_id' => auth()->id(),
                "module" => strtolower($module),
                "module_id" => strtolower($moduleId),
            ]);
        }
    }

    private function deleteArchiveDate($module, $moduleId)
    {
        auth()->user()->archives()->where('module', $module)->where('module_id', $moduleId)->delete();
    }

    public function archiveAllTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:tasks,_id,deleted_at,NULL',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $tasks = Task::whereIn('_id', $request->ids)->get();

            if (empty($tasks)) {
                throw new \exception('Task Not Found.');
            }

            $tasks->each(function ($task) {
                if ($task->createdBy(auth()->user()->id)) {
                    $task->delete(); //globally archive project if created by same user

                    /** Create activity log and create chat group, notification & FCM notification */
                    $activityData = [
                        "activity" => "Task {$task->name} Deleted",
                        "activity_by" => auth()->id(),
                        "activity_time" => Carbon::now(),
                        "activity_data" => json_encode(["task_name" => $task->name, "author_name" => auth()->user()->name, 'task_id' => $task->id, 'recipients' => $task->assignedTo->pluck('id')->toArray()]),
                        "activity" => "task_deleted",
                    ];

                    dispatch(new CreateActivityJob($activityData));
                } else {
                    $task->archive();
                    $this->createArchiveDate('task', $task->id);
                }

                event(new TaskArchived($task));
            });

            $this->setResponse(false, 'Tasks Moved to Archive.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function unArchiveAllTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $tasks = Task::withTrashed()->withArchived()->whereIn('_id', $request->ids)->get();

            $tasks->each(function ($task) {

                if ($task->createdBy(auth()->user()->id)) {
                    $task->restore();

                    /** Create activity log and create chat group, notification & FCM notification */
                    $activityData = [
                        "activity" => "Task {$task->name} Restored",
                        "activity_by" => auth()->id(),
                        "activity_time" => Carbon::now(),
                        "activity_data" => json_encode(["author_name" => auth()->user()->name, 'task_id' => $task->id]),
                        "activity" => "task_restore",
                    ];

                    dispatch(new CreateActivityJob($activityData));
                } else {
                    $task->unArchive();
                    $this->deleteArchiveDate('task', $task->id);
                }

                event(new TaskUnarchived($task));
            });


            $this->setResponse(false, 'Task Restored Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function unArchiveAllProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $projects = Project::withTrashed()->withArchived()->whereIn('_id', $request->ids)->get();

            $projects->each(function ($project) {

                if ($project->createdBy(auth()->user()->id)) {
                    $project->restore();

                    /** Create activity log and create chat group, notification & FCM notification */
                    $activityData = [
                        "activity" => "Project {$project->name} Restored",
                        "activity_by" => auth()->id(),
                        "activity_time" => Carbon::now(),
                        "activity_data" => json_encode(["author_name" => auth()->user()->name, 'project_id' => $project->id]),
                        "activity" => "project_restore",
                    ];

                    dispatch(new CreateActivityJob($activityData));
                } else {
                    $project->unArchive();
                    $this->deleteArchiveDate('project', $project->id);
                }
            });

            $this->setResponse(false, 'Project Restored Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
