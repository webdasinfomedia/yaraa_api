<?php

namespace App\Http\Controllers;

use App\Events\PusherMessageSend;
use App\Http\Resources\MessageResource;
use App\Http\Resources\TaskCommentResource;
use App\Jobs\CreateActivityJob;
use App\Models\Conversation;
use App\Models\Task;
use App\Models\TaskComment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskCommentController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,_id',
            'type' => 'required|in:all,important',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);
            if ($request->type == 'all') {
                $comments = $task->comments->sortByDesc('created_at');
            } else {
                $comments = $task->comments()->where('marked_important_by', auth()->id())->get()->sortByDesc('created_at');
            }
            return (TaskCommentResource::collection($comments))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "task_id" => "required|exists:tasks,_id",
            'message' => 'required_without:attachments|max:500',
            "attachments" => "filled|array",
            "attachments.*" => "filled|mimes:doc,jpg,jpeg,png,docx,csv,pdf,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $task = Task::find($request->task_id);

            $this->authorize('addComment', [TaskComment::class, $task]);

            $taskComment = TaskComment::create($request->except('attachments'));

            $attachments = $this->addFileAttachments($request->attachments, 'task/comments');
            $taskComment->attachments = $attachments;
            $taskComment->type = "message";
            $taskComment->save();

            $activityData = [
                "activity" => 'New Message in Task',
                "activity_by" => auth()->id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["task_id" => $request->task_id, "author_name" => auth()->user()->name]),
                "activity" => "task_commentadded",
            ];

            dispatch(new CreateActivityJob($activityData));

            $this->setResponse(false, 'Message Added Successfully.');
            return response()->json($this->_response, 201);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function addLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "task_id" => "required|exists:tasks,_id",
            'type' => 'required|in:location',
            'details' => 'required|json'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        $task = Task::find($request->task_id);

        $this->authorize('addComment', [TaskComment::class, $task]);

        try {
            $request->merge(['location_details' => $request->details]);
            TaskComment::create($request->except('attachments,details'));

            $activityData = [
                "activity" => 'New Message in Task',
                "activity_by" => auth()->id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["task_id" => $request->task_id, "author_name" => auth()->user()->name]),
                "activity" => "task_commentadded",
            ];

            dispatch(new CreateActivityJob($activityData));

            $this->setResponse(false, 'Location Logged Successfully.');
            return response()->json($this->_response, 201);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function addGoogleMeetDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required_without:conversation_id|exists:tasks,_id',
            'conversation_id' => 'required_without:task_id|exists:conversations,_id',
            'type' => 'required|in:meet',
            'meet_url' => 'required',
            'date_time' => 'required'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            /** Add Task comment entry **/
            if ($request->has('task_id') && $request->task_id != null) {
                $task = Task::find($request->task_id);

                $this->authorize('addComment', [TaskComment::class, $task]);

                TaskComment::Create([
                    'task_id' => $request->task_id,
                    'message' => auth()->user()->name . ' created google meet room',
                    'type' => $request->type,
                    'meet_details' => [
                        'meet_url' => $request->meet_url,
                        'date_time' => $request->date_time,
                    ]
                ]);

                $module = 'task';
                $moduleId = $request->task_id;
            }

            /** Add Conversation message entry **/
            if ($request->has('conversation_id')) {
                $conversation = Conversation::find($request->conversation_id);
                if ($conversation) {
                    // $notificationDescription = "Join now.";
                    $conversation->messages()->create([
                        'body' => auth()->user()->name . ' created google meet room',
                        'type' => $request->type,
                        'meet_details' => [
                            'meet_url' => $request->meet_url,
                            'date_time' => $request->date_time,
                        ],
                        'read_by' => [],
                    ]);

                    $module = 'message';
                    $moduleId = $conversation->id;

                    $recentMessage = $conversation->messages()->orderBy('created_at', 'desc')->first();
                    $payLoad = new MessageResource($recentMessage);
                    broadcast(new PusherMessageSend($payLoad, $conversation->id))->toOthers();
                }
            }

            /** Create activity log and create chat group, notification & FCM notification */
            $activityData = [
                "activity_title" => "Google Meet Room created",
                "activity_by" => auth()->id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["auth_id" => auth()->id(), "author_name" => auth()->user()->name, 'module' => $module, 'module_id' => $moduleId]),
                "activity" => "meet_meeetingcreated",
                "notification_data" => json_encode(["title" => "Google Meet Room created by " . auth()->user()->name, "description" => "Join Now!"])
            ];

            dispatch(new CreateActivityJob($activityData));

            $this->setResponse(false, 'Google Meet Url Created Successfully.');
            return response()->json($this->_response, 201);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function markAsImportant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|exists:task_comments,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $comment = TaskComment::find($request->comment_id);

            $this->authorize('addComment', [TaskComment::class, $comment->task]);

            $comment->markAsImportant();

            $this->setResponse(false, 'Marked as important Successfully.');
            return response()->json($this->_response);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function markAsUnimportant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|exists:task_comments,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $comment = TaskComment::find($request->comment_id);

            $this->authorize('addComment', [TaskComment::class, $comment->task]);

            $comment->markAsUnImportant();

            $this->setResponse(false, 'Removed from important Successfully.');
            return response()->json($this->_response);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
