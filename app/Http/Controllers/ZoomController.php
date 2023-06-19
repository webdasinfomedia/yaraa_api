<?php

namespace App\Http\Controllers;

use App\Events\PusherMessageSend;
use App\Http\Resources\MessageResource;
use App\Jobs\CreateActivityJob;
use App\Models\Conversation;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\UserApp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ZoomController extends Controller
{
    public function createMeeting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required_without:conversation_id|exists:tasks,_id',
            'conversation_id' => 'required_without:task_id|exists:conversations,_id',
            "topic" => 'required',
            "type" => 'required',
            "start_time" => 'required',
            "duration" => 'required',
            "password" => 'required'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            if ($request->has('task_id') && $request->task_id != null) {
                $task = Task::find($request->task_id);

                $this->authorize('addComment', [TaskComment::class, $task]);
            }

            // $zoomToken = Setting::where('type', 'zoom_token')->first();
            $zoomToken = UserApp::where("user_id", auth()->id())->where('type', 'zoom_token');
            if ($zoomToken->exists()) {
                $response = Http::withHeaders([
                    'authorization' => 'Bearer ' . $zoomToken->first()->access_token,
                    'content-type' => 'application/json',
                ])
                    ->post(
                        'https://api.zoom.us/v2/users/me/meetings',
                        [
                            "topic" => $request->topic,
                            "type" => $request->type,
                            "start_time" => trim($request->start_time),
                            "duration" => $request->duration,
                            // "timezone" => getUserTimezone(),
                            "timezone" => getUserTimezone(),
                            "password" => $request->password,
                            "settings" => [
                                "join_before_host" => true,
                            ]
                        ]
                    );

                $response = json_decode($response->getBody()->getContents());

                if (isset($response->code)) {
                    throw new \Exception($response->message);
                }

                // $type = $request->type == 2 ? 'scheduled' : 'created';

                $startTime =  Carbon::createFromFormat('Y-m-d\TH:i:s',  trim($request->start_time))->format('D M j, Y h:i A');
                $endTime = Carbon::createFromFormat('Y-m-d\TH:i:s',  trim($request->start_time))->addMinutes($response->duration)->format('h:i A');

                /** Add Task comment entry **/
                if ($request->has('task_id')) {
                    $type = 'scheduled';
                    // $activityTitle = "Zoom Meeting {$type} on {$startTime}";
                    $notificationDescription = "Scheduled on {$startTime}";

                    TaskComment::create(
                        [
                            'task_id' => $request->task_id,
                            'message' => auth()->user()->name . ' ' . $type . ' a zoom Meeting',
                            'type' => 'zoom',
                            'zoom_details' => [
                                'start_time' =>  $startTime . ' - ' . $endTime,
                                'duration' => $response->duration,
                                'meeting_id' => $response->id,
                                'meeting_password' => $response->password,
                                'start_url' => $response->start_url,
                                'join_url' => $response->join_url,
                            ],
                        ]
                    );

                    $module = 'task';
                    $moduleId = $request->task_id;

                    // $activityData = [
                    //     "activity" => 'New Message in Task',
                    //     "activity_by" => auth()->id(),
                    //     "activity_time" => Carbon::now(),
                    //     "activity_data" => json_encode(["task_id" => $request->task_id, "author_name" => auth()->user()->name]),
                    //     "activity" => "task_commentadded",
                    // ];

                    // dispatch(new CreateActivityJob($activityData));
                }

                /** Add Conversation message entry **/
                if ($request->has('conversation_id')) {
                    $conversation = Conversation::find($request->conversation_id);
                    if ($conversation) {
                        $type = 'created';
                        // $activityTitle = "Zoom Meeting {$type}";
                        $notificationDescription = "Join now.";

                        $conversation->messages()->create([
                            'body' => auth()->user()->name . ' ' . $type . ' a zoom Meeting',
                            'type' => 'zoom',
                            'zoom_details' => [
                                'start_time' =>  $startTime . ' - ' . $endTime,
                                'duration' => $response->duration,
                                'meeting_id' => $response->id,
                                'meeting_password' => $response->password,
                                'start_url' => $response->start_url,
                                'join_url' => $response->join_url,
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
                    "activity_title" => "Zoom Meeting {$type}",
                    "activity_by" => auth()->id(),
                    "activity_time" => Carbon::now(),
                    "activity_data" => json_encode(["auth_id" => auth()->id(), "author_name" => auth()->user()->name, 'module' => $module, 'module_id' => $moduleId]),
                    "activity" => "zoom_meeetingcreated",
                    "notification_data" => json_encode(["title" => "Zoom Meeting {$type} by " . auth()->user()->name, "description" => $notificationDescription])
                ];

                dispatch(new CreateActivityJob($activityData));

                $this->setResponse(false, 'Meeting Created Successfully.');
                return response()->json($this->_response, 200);
            } else {
                throw new \Exception('Zoom not enabled.');
            }
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function deleteMeeting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            // $zoomToken = Setting::where('type', 'zoom_token')->first();
            $zoomToken = UserApp::where("user_id", auth()->id())->where('type', 'zoom_token')->exists();

            if ($zoomToken) {
                $response = Http::withHeaders([
                    'authorization' => 'Bearer ' . $zoomToken->access_token,
                    'content-type' => 'application/json',
                ])
                    ->delete('https://api.zoom.us/v2/meetings/' . $request->meeting_id);

                $response = json_decode($response->getBody()->getContents());
                if ($response == null) {
                    $this->setResponse(false, "Meeting Deleted Successfully.");
                    return response()->json($this->_response, 200);

                    TaskComment::where('zoom_details.meeting_id', $request->meeting_id)->delete();
                }

                $taskComment = TaskComment::whereRaw(["zoom_details.meeting_id" => intval($request->meeting_id)])->first();
                $taskComment ? $taskComment->delete() : true;

                $this->setResponse(false, "Meeting Not Found or Deleted.");
                return response()->json($this->_response, 200);
            }
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
