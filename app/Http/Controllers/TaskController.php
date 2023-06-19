<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Tag;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use App\Models\Milestone;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Events\TaskCompletedEvent;
use App\Events\StartedProjectItem;
use App\Http\Resources\TaskResource;
use App\Events\ReopenProjectItemEvent;
use App\Events\SyncMilestoneStatusEvent;
use App\Exceptions\TaskActivityException;
use Illuminate\Support\Facades\Validator;
use App\Jobs\CreateMemberInviteRegisterMail;
use App\Http\Resources\TaskListSectionResource;
use App\Jobs\CreateActivityJob;
use App\Jobs\TaskDeleteJob;
use App\Models\TaskComment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "order_by" => "required|in:new_first,old_first,az,za",
            "task_by" => "required|in:todays,thisweeks,all"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 200);
        }

        try {
            return (new TaskListSectionResource(auth()->user()))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // "name" => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:100",
            "name" => "required|max:100",
            "assignee" => "filled",
            "description" => "nullable|max:255",
            "milestones" => "filled|json",
            "attachments" => "filled|array",
            "attachments.*" => "filled|mimes:doc,jpg,jpeg,png,docx,csv,pdf,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
            "visibility" => "required|in:private,public,todo",
            "priority" => "required",
            "tags" => "filled",
            "due_date" => "required|filled|date",
            "project_id" => "filled|exists:projects,_id",
            "reminder" => "nullable|in:true,false",
            "start_date" => "filled|date|required_if:reminder,true",
            "recurrence" => "in:daily,weekly,monthly,yearly,no"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            if ($request->has('project_id')) {
                $this->authorize('store', [Task::class, $request->project_id]);
            }

            $task = Task::create($request->except('assignee', 'milestones', 'attachments', 'start_date', 'due_date', 'reminder'));

            if ($task) {
                $task->assigned_by = Auth::user()->email;

                /** convert start_date from user TZ to UTC TZ and save **/
                if ($request->has('start_date')) {
                    $date = Carbon::createFromFormat('Y-m-d H:i A', $request->start_date, getUserTimezone());
                    $date->setTimezone('UTC');
                    $task->start_date = $date;
                    // $task->start_date = $request->start_date ? new DateTime($request->start_date) : null;
                } else {
                    $task->start_date = null;
                }

                /** convert due_date from user TZ to UTC TZ and save **/
                if ($request->has('due_date')) {
                    $date = Carbon::createFromFormat('Y-m-d H:i A', $request->due_date, getUserTimezone());
                    $date->setTimezone('UTC');
                    $task->due_date = $date;
                    // $task->due_date = $request->due_date ? new DateTime($request->due_date) : null;
                } else {
                    $task->due_date = null;
                }


                //add assign members to task
                // $request->assignee .= "," . auth()->user()->email; // assign creator as member of task
                // $request->assignee = trim($request->assignee,',');
                // $this->attachMembers($request->assignee,$task);

                $assignee = array_filter(explode(',', $request->assignee));
                $assignee[] = auth()->user()->email; //keep task owner as member
                $task->sync($assignee, [], 'assignedTo')->syncProjectMember();
                $task->skipUser(auth()->id())->sendTaskMail(false);

                //attach milestones
                $this->attachMilestones($request->milestones, $task);

                //upload attachments
                $files = $this->addFileAttachments($request->attachments, 'task/attachments/');
                $task->attachments = $files;

                //attach tags
                $this->attachTags($request->tags, $task);

                if ($request->has('reminder')) {
                    $task->reminder = filter_var($request->reminder, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }

                $task->status = "pending";

                if ($request->has('recurrence')) {
                    $task->recurrence = $request->recurrence;
                }

                $task->save();

                event(new ReopenProjectItemEvent($task)); // fire event to reopen milestone,project.

                /** Create activity log and create chat group, notification & FCM notification */
                $activityData = [
                    "activity" => "Task {$task->name} Created",
                    "activity_by" => auth()->id(),
                    "activity_time" => Carbon::now(),
                    "activity_data" => json_encode(["author_name" => auth()->user()->name, 'task_id' => $task->id]),
                    "activity" => "task_created",
                ];

                dispatch(new CreateActivityJob($activityData));

                $this->setResponse(false, 'Task created successfully.');
                return response()->json($this->_response, 201);
            }
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    // protected function addAttachments($attachments, $task)
    // {
    //     if (!empty($attachments)) {
    //         foreach ($attachments as $key => $attachment) {
    //             if (!empty($attachment)) {
    //                 $filePath = 'task/attachments/' . getUniqueStamp() . $key . '.' . $attachment->extension();
    //                 $attachment->storeAs('public',$filePath);
    //                 $task->push('attachments', $filePath);
    //             }
    //         }
    //     }
    // }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => "required|exists:tasks,_id",
            // "name" => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:100",
            "name" => "required|max:100",
            "visibility" => "required|in:private,public,todo",
            "priority" => "required",
            "assignee" => "filled",
            "description" => "nullable|max:255",
            "milestones" => "filled|json",
            "attachments" => "filled|array",
            "removed_attachments" => "filled|json",
            "attachments.*" => "filled|mimes:doc,jpg,jpeg,png,docx,csv,pdf,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
            "tags" => "filled",
            "due_date" => "required|filled|date",
            "reminder" => "nullable|in:true,false",
            "start_date" => "filled|date|required_if:reminder,true",
            "recurrence" => "in:daily,weekly,monthly,yearly,no"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $task = Task::find($request->id);

            $this->authorize('canEdit', [Task::class, $task]);

            if ($task->update($request->except(['assignee', 'milestones', 'attachments', 'start_date', 'due_date', 'project_id', 'reminder']))) {

                /** convert start_date from user TZ to UTC TZ and save **/
                if ($request->has('start_date')) {
                    $date = Carbon::createFromFormat('Y-m-d H:i A', $request->start_date, getUserTimezone());
                    $date->setTimezone('UTC');
                    $task->start_date = $date;
                    // $task->start_date = $request->start_date ? new DateTime($request->start_date) : null;
                } else {
                    $task->start_date = null;
                }

                /** convert due_date from user TZ to UTC TZ and save **/
                if ($request->has('due_date')) {
                    $date = Carbon::createFromFormat('Y-m-d H:i A', $request->due_date, getUserTimezone());
                    $date->setTimezone('UTC');
                    $task->due_date = $date;
                    // $task->due_date = $request->due_date ? new DateTime($request->due_date) : null;
                } else {
                    $task->due_date = null;
                }

                $task->project_id = trim($request->project_id) == '' ? null : $request->project_id;

                // remove old assignee and add new assignee to task
                // $oldAssignee = $task->assignedTo->pluck('id')->toArray();
                // if(!empty($oldAssignee)){
                //     $task->assignedTo()->detach($oldAssignee);
                // }
                // $request->assignee .= "," .  $task->owner->email;
                // $this->attachMembers($request->assignee,$task);

                $assignee = array_filter(explode(',', $request->assignee));
                $assignee[] = $task->owner->email; //keep task owner as member
                $task->sync($assignee, $task->assignedTo()->pluck('email')->toArray(), 'assignedTo')->syncProjectMember();
                $task->sendTaskMail();

                //attach new milestones and remove old milestone to task
                if ($task->project_id != null) {
                    $milestones = json_decode(trim($request->milestones), true);
                    if (!empty($milestones)) {
                        $task->milestones()->sync($milestones);
                    } else {
                        $task->milestones()->sync([]); // remove all milestones
                        event(new SyncMilestoneStatusEvent($task->project)); // to complete milestones if needs to be.
                    }
                }

                //remove deleted attachments
                if ($request->has('removed_attachments')) {
                    $remove_attachments = json_decode($request->removed_attachments, true);
                    if (!empty($remove_attachments)) {
                        $filesToDeleteAsString = implode(',', $remove_attachments);
                        $deletedFiles = $this->removeFileAttachment($filesToDeleteAsString);
                        $task->pull('attachments', $deletedFiles);
                    }
                }

                //upload attachments
                $newFiles = $this->addFileAttachments($request->attachments, 'task/attachments/');
                if (!empty($newFiles)) {
                    $task->push('attachments', $newFiles);
                }

                //detach tags and attach it back for sync
                $oldTags = $task->tags->pluck('id')->toArray();
                if (!empty($oldTags)) {
                    $task->tags()->detach($oldTags);
                }

                if ($request->has('tags')) {
                    $this->attachTags($request->tags, $task);
                }

                $task->push('activity_logs', Auth::user()->name . " updated details");

                if ($request->has('reminder')) {
                    $task->reminder = filter_var($request->reminder, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }

                if ($request->has('recurrence')) {
                    $task->recurrence = $request->recurrence;
                    if ($task->parentTask()->exists()) {
                        $task->parentTask->recurrence = $request->recurrence;
                        $task->parentTask->save();
                    }
                }

                $task->save();

                // Log::debug($task->milestones->pluck('title'));
                event(new ReopenProjectItemEvent($task));  // fire event to reopen milestone & project.
            }
            $this->setResponse(false, 'Task updated successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getTask($task_id)
    {
        $fields = ["task_id" => $task_id];
        $validator = Validator::make($fields, [
            'task_id' => 'required|exists:tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            return (new TaskResource(Task::find($task_id)))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    private function attachTags($tags, $task)
    {
        $tags = array_filter(explode(',', $tags));
        if (!empty($tags)) {
            $tags = array_map("trim", $tags);
            foreach ($tags as $tag) {
                $tag = Tag::updateOrCreate(['name' => trim($tag)]);
                if ($tag) {
                    $task->tags()->attach($tag);
                }
            }
        }
    }

    private function attachMilestones($milestonesData, $task)
    {
        $milestones = json_decode(trim($milestonesData), true);
        if (!empty($milestones)) {
            foreach ($milestones as $milestone) {
                $milestone = Milestone::find($milestone);
                if ($milestone) {
                    $task->milestones()->attach($milestone);
                }
            }
        }
    }

    private function attachMembers($assignees, $task)
    {
        $assignees = explode(',', $assignees);
        if (!empty(array_filter($assignees))) {
            $assignees = array_map("trim", $assignees);
            foreach ($assignees as $assignee) {
                $user = User::where('email', $assignee)->first();
                $token = hash('sha256', Str::random(60));
                if ($user) {
                    $task->assignedTo()->attach($user);
                    $assignedTo[] = $user->name;
                } else {
                    if (filter_var($assignee, FILTER_VALIDATE_EMAIL) != false) {
                        $user = $this->registerUser($assignee); //method defined in base controller
                        $task->assignedTo()->attach($user);
                        dispatch(new CreateMemberInviteRegisterMail($user, Auth::user(), 'Task', $task->name, $token));
                        $assignedTo[] = $assignee;
                    }
                }
                if ($task->project()->exists() && !$task->project->members->contains($user->id)) {
                    $task->project->members()->attach($user);
                }
            }

            if (!empty($assignedTo)) {
                $memberNames = implode(',', $assignedTo);
                $task->push('activity_logs', Auth::user()->name . " assigned to " . $memberNames);
            }
        }
    }

    public function markAsComplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "task_id" => "required|alpha_num|exists:tasks,_id"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $task->markAsComplete();
            $task->subTasks->each->markAsComplete();

            event(new TaskCompletedEvent($task));

            /** auto complete milestones ****/
            // $completeMilestones = $task->milestones->filter(function($milestone){               
            //     return $milestone->tasks->whereNull('end_date')->isEmpty();
            // });
            // $completeMilestones->each->markAsComplete();

            $this->setResponse(false, 'Task Completed Successfully.');
            return response()->json($this->_response, 200);
        } catch (TaskActivityException $e) {
            $this->setResponse(false, $e->getMessage());
            return response()->json($this->_response, 500);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function startTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "task_id" => "required|alpha_num|exists:tasks,_id"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $task->start();

            event(new StartedProjectItem($task));

            $this->setResponse(false, 'Task Started Successfully.');
            return response()->json($this->_response, 200);
        } catch (TaskActivityException $e) {
            $this->setResponse(false, $e->getMessage());
            return response()->json($this->_response, 500);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function inviteMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
            'email' => 'required|email',
            'notes' => "filled|regex:/^[\pL\pN\s\-\_\']+$/u|max:250",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $token = Crypt::encryptString(app('tenant')->id);
            $user = User::where('email', $request->email)->first();
            $task = Task::find($request->task_id);
            if ($user) {
                $user->push('invite_member_token', $token);
                $task->push('activity_logs', Auth::user()->name . " assigned to " . $user->name);
            } else {
                $user = $this->registerUser($request->email);
                $user->push('invite_member_token', $token);
                $task->push('activity_logs', Auth::user()->name . " assigned to " . $request->email);
            }

            if ($task->project()->exists() && !$task->project->members->contains($user->id)) {
                $task->project->members()->attach($user);
            }

            $task->assignedTo()->attach($user);
            $user->save();

            dispatch(new CreateMemberInviteRegisterMail($user, Auth::user(), 'Task', $task->name, $token));

            $this->setResponse(false, 'Invitation Send Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function reOpen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "task_id" => "required|alpha_num|exists:tasks,_id"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $task->markAsReopen();

            event(new ReopenProjectItemEvent($task)); // fire event to reopen milestone,project.

            $this->setResponse(false, 'Re-open Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function pause(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $task->pause();

            $this->setResponse(false, 'Paused Successfully.');
            return response()->json($this->_response, 200);
        } catch (TaskActivityException $e) {
            $this->setResponse(false, $e->getMessage());
            return response()->json($this->_response, 500);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function resume(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $task->resume();

            $this->setResponse(false, 'Resumed Successfully.');
            return response()->json($this->_response, 200);
        } catch (TaskActivityException $e) {
            $this->setResponse(false, $e->getMessage());
            return response()->json($this->_response, 500);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    /** Permanently Delete Singal Task ***/
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::withTrashed()->withArchived()->find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            if ($task->created_by == auth()->id()) {
                $task->is_deleting = true;
                $task->save();
                dispatch(new TaskDeleteJob($task));
                $this->setResponse(false, "Task is Deleting.");
            } else {
                $task->unArchive();

                $task->assignedTo()->detach(auth()->id());

                // $task->subTasks()->where('assign_to', auth()->id())->delete();

                auth()->user()->archives()->where('module', 'task')->where('module_id', $task->id)->delete();
            }
            $this->setResponse(false, "Task removed from archive.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    /** Permanently Delete multiple Task ***/
    public function deleteMultipleTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $tasks = Task::withTrashed()->withArchived()->whereIn('_id', $request->task_ids)->get();
            $tasks->each(function ($task) {

                // $this->authorize('canEdit', [Task::class, $task]);

                if ($task->created_by == auth()->id()) {
                    $task->is_deleting = true;
                    $task->save();
                    dispatch(new TaskDeleteJob($task));
                    $this->setResponse(false, "Task is Deleting.");
                } else {
                    $task->unArchive();

                    $task->assignedTo()->detach(auth()->id());

                    // $task->subTasks()->where('assign_to', auth()->id())->delete();

                    auth()->user()->archives()->where('module', 'task')->where('module_id', $task->id)->delete();
                }
            });

            $this->setResponse(false, "Task removed from archive.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function moveToProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
            // 'project_id' => 'required|exists:projects,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $oldProject = $task->project;
            $task->project()->dissociate();


            $task->project_id = $request->project_id;
            $task->assignedTo()->sync([$task->created_by]); //remove all members except creator.
            //subtask - delete All ?
            //task comments - remove all comments with daily pause & resume logs(details) ?

            $task->milestones()->sync([]);
            $task->save();

            if ($oldProject) {
                event(new SyncMilestoneStatusEvent($oldProject)); // to complete milestones of previous project if needs to be.
            }

            $this->setResponse(false, 'Task Moved Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function copyTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $task->load('assignedTo');
            $task->load('milestones');
            $task->load('tags');

            $task->unsetRelation('project');
            $newTask = $task->replicate();
            $newTask->push();

            foreach ($task->getRelations() as $relation => $items) {
                foreach ($items as $item) {
                    $newTask->{$relation}()->attach($item);
                }
            }

            if (!empty($newTask->attachments)) {
                $filesToAttach = [];
                foreach ($newTask->attachments as $file) {
                    $parts = explode("/", $file);
                    $search = '.';
                    $replace = '-' . getUniqueStamp() . '-copy.';
                    $target = end($parts);

                    $newName = strrev(implode(strrev($replace), explode(strrev($search), strrev($target), 2)));
                    $newFile = str_replace($target, $newName, $file);
                    $filesToAttach[] = $newFile;
                    Storage::copy("public/{$file}", "public/{$newFile}");
                }
                $newTask->attachments = $filesToAttach;
            }

            $task->start_date != null && $task->end_date == null ? $newTask->start_date = new DateTime() : null;
            $newTask->name = $newTask->name . " - copy";
            $newTask->save();

            $this->setResponse(false, 'Duplicate Task Created Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function leaveTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }
        try {
            $task = Task::find($request->task_id);

            if ($task->assignedTo()->where('_id', auth()->id())->exists()) {
                $task->assignedTo()->detach(auth()->id());
                $userName = auth()->user()->name ? auth()->user()->name : auth()->user()->email;
                TaskComment::create([
                    "task_id" =>  $task->id,
                    "message" => "{$userName} left the task",
                    "type" => "notification",
                    "read_by" => []
                ]);
            }

            $this->setResponse(false, 'You left from the task successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function markAsCompleteAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "task_ids" => "required|array|filled"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::whereIn('_id', $request->task_ids)->get();

            $task->each(function ($task) {
                $this->authorize('canEdit', [Task::class, $task]);
                $task->markAsComplete();
                $task->subTasks->each->markAsComplete();
                event(new TaskCompletedEvent($task));
            });

            /** auto complete milestones ****/
            // $completeMilestones = $task->milestones->filter(function($milestone){               
            //     return $milestone->tasks->whereNull('end_date')->isEmpty();
            // });
            // $completeMilestones->each->markAsComplete();

            $this->setResponse(false, 'Tasks Completed Successfully.');
            return response()->json($this->_response, 200);
        } catch (TaskActivityException $e) {
            $this->setResponse(false, $e->getMessage());
            return response()->json($this->_response, 500);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function reOpenAllTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "ids" => "required|array",
            "ids.*" => "exists:tasks,_id"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $tasks = Task::whereIn('_id', $request->ids)->get();

            $tasks->each(function ($task) {
                $this->authorize('canEdit', [Task::class, $task]);
                $task->markAsReopen();
                event(new ReopenProjectItemEvent($task)); // fire event to reopen milestone,project.
            });

            $this->setResponse(false, 'Re-open Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
