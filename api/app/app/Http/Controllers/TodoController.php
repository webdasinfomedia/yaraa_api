<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\TodoResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class TodoController extends Controller
{
    public function createTodo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u",
            "attachments.*" => "filled|mimes:doc,jpg,jpeg,png,docx,csv,pdf,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
            "description" => "nullable|max:255",
            "reminder" => "nullable|in:true,false",
            "start_date" => "nullable|date|required_if:reminder,true",
            "recurrence" => "in:daily,weekly,monthly,yearly,no"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $todo = auth()->user()->todos()->create($request->except('attachments', 'start_date', 'reminder'));
            if ($request->has('attachments')) {
                $files = $this->addFileAttachments($request->attachments, 'todo/attachments/');
                $todo->attachments = $files;
            }

            /** convert start_date from user TZ to UTC TZ and save **/
            if ($request->has('start_date')) {
                $date = Carbon::createFromFormat('Y-m-d H:i A', $request->start_date, getUserTimezone());
                $date->setTimezone('UTC');
                $todo->start_date = $date;
            }

            if ($request->has('reminder')) {
                $todo->reminder = filter_var($request->reminder, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }

            $todo->visibility = 'todo';
            $todo->status = 'pending';
            $todo->save();

            $this->setResponse(false, 'Todo Added successfully.');
            return response()->json($this->_response, 201);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function editTodo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:100",
            "attachments.*" => "filled|mimes:doc,jpg,jpeg,png,docx,csv,pdf,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
            "remove_attachments" => "filled",
            "description" => "nullable|max:255",
            "reminder" => "nullable|in:true,false",
            "start_date" => "nullable|date|required_if:reminder,true",
            "recurrence" => "in:daily,weekly,monthly,yearly,no"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $todo = auth()->user()->todos()->where('_id', $request->id)->first();

            if ($todo->update($request->except('attachments', 'start_date', 'reminder'))) {
                if ($request->has('remove_attachments')) {
                    $deletedFiles = $this->removeFileAttachment($request->remove_attachments);
                    $todo->attachments = array_values(array_diff($todo->attachments, $deletedFiles));
                }

                if ($request->has('attachments')) {
                    $files = $this->addFileAttachments($request->attachments, 'todo/attachments/');
                    $todo->attachments = $files;
                }

                /** convert start_date from user TZ to UTC TZ and save **/
                if ($request->has('start_date')) {
                    $date = Carbon::createFromFormat('Y-m-d H:i A', $request->start_date, getUserTimezone());
                    $date->setTimezone('UTC');
                    $todo->start_date = $date;
                }

                if ($request->has('reminder')) {
                    $todo->reminder = filter_var($request->reminder, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }

                if ($request->has('recurrence')) {
                    $todo->recurrence = $request->recurrence;
                    if ($todo->parentTodo()->exists()) {
                        $todo->parentTodo->recurrence = $request->recurrence;
                        $todo->parentTodo->save();
                    }
                }

                $todo->save();
            }

            $this->setResponse(false, 'Todo Updated successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getTodo($todoId)
    {
        $fields = ["todo_id" => $todoId];

        $validator = Validator::make($fields, [
            'todo_id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }
        try {
            $todo = auth()->user()->todos()->find($todoId);
            if ($todo) {
                return (new TodoResource($todo))->additional(['error' => false, 'message' => null]);
            }
            $this->setResponse(true, 'Todo Not Found');
            return response()->json($this->_response, 404);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function complete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $todo = auth()->user()->todos()->find($request->id);
            if ($todo) {
                $todo->markAsComplete();
                $this->setResponse(false, 'Completed Successfully.');
                return response()->json($this->_response, 200);
            }
            throw new \Exception('Todo Not Found.');
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $todo = auth()->user()->todos()->find($request->id);
            if ($todo) {
                if (is_array($todo->attachments)) {
                    $deleteFiles = implode(',', $todo->attachments);
                    $this->removeFileAttachment($deleteFiles);
                }
                $todo->delete();
            }
            $this->setResponse(false, 'Deleted Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function reOpen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $todo = auth()->user()->todos()->find($request->id);
            if ($todo) {
                $todo->markAsReopen();
            } else {
                $this->setResponse(true, 'Todo Not Found.');
            }
            $this->setResponse(false, 'Re-open Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
