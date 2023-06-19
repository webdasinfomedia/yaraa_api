<?php

namespace App\Http\Controllers;

use App\Events\ReopenProjectItemEvent;
use DateTime;
use App\Models\Task;
use App\Models\User;
use App\Models\SubTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubTaskController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required',
            'name' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u",
            'assignee' => 'required',
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $subTask = $task->subTasks()->create($request->except(['assignee', 'due_date']));
            $subTask->due_date = $request->has('due_date') ? new DateTime($request->due_date) : null;

            if ($request->has('assignee')) {
                $user = User::where('email', $request->assignee)->first();
                $subTask = ($user) ? $subTask->assignee()->associate($user) : $subTask->assignee()->disassociate();
            }

            $subTask->status = 'pending';
            $subTask->save();

            event(new ReopenProjectItemEvent($subTask)); // fire event to reopen tasks.

            $this->setResponse(false, 'Subtask Added Successfully.');
            return response()->json($this->_response, 201);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    /**
     * retired API
     */
    public function AddAssignee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
            'subtask_id' => 'required|exists:sub_tasks,_id',
            'assignee' => 'required|exists:users,email',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }
        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $user = User::where('email', $request->assignee)->first();
            if (in_array($user->id, $task->assignee)) {
                $subTask = $task->subTasks()->find($request->subtask_id);
                $subTask = $subTask->assignee()->associate($user);
                $subTask->save();
                $this->setResponse(false, 'Subtask Assigned Successfully.');
                return response()->json($this->_response, 200);
            }
            $this->setResponse(true, 'User not part of the task.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    /**
     * retired API
     */
    public function removeAssignee($subtask_id)
    {
        $fields["id"] = $subtask_id;
        $validator = Validator::make($fields, [
            'id' => 'required|exists:sub_tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $subTask = SubTask::find($subtask_id);

            $this->authorize('canEdit', [Task::class, $subTask->parentTask]);

            $subTask->assignee()->dissociate();
            $subTask->save();

            $this->setResponse(false, 'Assignee removed Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
            'subtask_id' => 'required|exists:sub_tasks,_id',
            'name' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u",
            'assignee' => 'present', //present = required + can be empty
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $subTask = $task->subTasks()->find($request->subtask_id);
            $subTask->update($request->except(['assignee', 'due_date']));
            $subTask->due_date = $request->has('due_date') ? new DateTime($request->due_date) : null;

            if ($request->has('assignee')) {
                $user = User::where('email', $request->assignee)->first();
                $subTask = ($user) ? $subTask->assignee()->associate($user) : $subTask->assignee()->disassociate();
            }

            $subTask->save();

            $this->setResponse(false, 'Subtask Updated Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
            'subtask_id' => 'required|exists:sub_tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);

            $subTask = $task->subTasks()->find($request->subtask_id);
            if ($subTask->delete()) {
                $this->setResponse(false, 'Subtask Deleted Successfully.');
            } else {
                $this->setResponse(true, 'Something Went Wrong, Try Again.');
            }

            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function markAsComplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
            'subtask_id' => 'required|exists:sub_tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('canEdit', [Task::class, $task]);


            $subTask = $task->subTasks()->find($request->subtask_id);
            $subTask->markAsComplete();

            $this->setResponse(false, 'Completed Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }


    public function reOpen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:sub_tasks,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $subTask = SubTask::find($request->id);

            $this->authorize('canEdit', [Task::class, $subTask->parentTask]);

            $subTask->markAsReopen();

            event(new ReopenProjectItemEvent($subTask)); // fire event to reopen task.

            $this->setResponse(false, 'Re-open Successfully.');

            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
