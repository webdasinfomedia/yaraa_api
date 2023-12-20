<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Events\PusherMessageSend;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageListSectionResource;
use App\Jobs\CreateActivityJob;
use App\Jobs\DeleteMessageHistoryJob;
use Carbon\Carbon;

class MessageController extends Controller
{
    public function memberMessageList()
    {
        try {
            // $members = User::whereNotIn('email',[auth()->user()->email])->get();
            // return (MessageMemberListResource::collection($members))->additional(['error' => false, 'message' => null]);
            return (new MessageListSectionResource(auth()->user()))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'filled|exists:conversations,_id',
            'to' => 'required_without:conversation_id|email|exists:users,email,deleted_at,NULL',
            'body' => 'required_without:attachments|max:500',
            "attachments" => "filled|array",
            "attachments.*" => "filled|mimes:doc,jpg,jpeg,png,docx,csv,pdf,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            if ($request->has('conversation_id')) {
                $conversation = Conversation::find($request->conversation_id);
                if ($conversation->type == 'personal') {
                    $conversation->deleted_by = [];
                }
            } else {
                $user = User::where('email', $request->to)->first();
                $hasConversation = Conversation::whereRaw([
                    'member_ids' => ['$all' => [auth()->id(), $user->id]],
                    'type' => 'personal',
                ])
                    ->first();
                if ($hasConversation) {
                    $conversation = $hasConversation;
                    $conversation->pull('deleted_by', auth()->id());
                    $conversation->pull('deleted_by', $user->id);
                } else {
                    $conversation = new Conversation;
                    $conversation->type = 'personal';
                    $conversation->created_by = auth()->id();
                    $conversation->save();

                    $user = User::where('email', $request->to)->first();
                    $conversation->members()->attach([auth()->id(), $user->id]);
                }

                $request->merge(['conversation_id' => $conversation->id]);
            }

            $files = $this->addFileAttachments($request->attachments, 'chat/attachments/');

            $conversation->messages()->create([
                'body' => $request->body,
                'attachments' => $files,
                'read_by' => [],
            ]);

            $conversation->last_message_at = new DateTime();
            $conversation->save();

            /** Create activity log and create chat group, notification & FCM notification */
            $activityData = [
                "activity" => "Send new message",
                "activity_by" => auth()->id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["auth_id" => auth()->id(), "author_name" => auth()->user()->name, 'conversation_id' => $conversation->id]),
                "notification_data" => json_encode(["body" => $request->body]),
                "activity" => "message_received",
            ];

            dispatch(new CreateActivityJob($activityData));

            $recentMessage = $conversation->messages()->orderBy('created_at', 'desc')->first();

            $lastMessage = $recentMessage->body ?? '';
            $lastMessageAttachment =  $recentMessage->attachments ?? [];

            // $recentMessage->body = $lastMessage == '' && !empty($lastMessageAttachment) ? 'Attachment' : $lastMessage;
            $recentMessage->body = $lastMessage;
            $recentMessage->created_at = $recentMessage->created_at; // it will set current users timezone for pusher message

            $payLoad = new MessageResource($recentMessage);
            broadcast(new PusherMessageSend($payLoad, $conversation->id))->toOthers();

            $this->setResponse(false, 'Message Sent Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getMessageHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $conversation = Conversation::find($request->conversation_id);
            // Cache::flush();
            // if(!Cache::has('conversations')){
            //     // Cache::forever('conversations',Conversation::find($request->conversation_id));
            //     Cache::forever('conversations',Conversation::find($request->conversation_id)->with("messages")->first());
            // }

            // $conversation = Cache::get('conversations');
            return (new ConversationResource($conversation))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $conversation = Conversation::find($request->conversation_id);

            $message = $conversation->othersMessages()->push('read_by', auth()->id());
            // $message->push('read_by',auth()->id());

            $this->setResponse(false, 'Marked as Read Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function addMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'members' => 'required|array',
            'members.*' => 'required|exists:users,email',
            'conversation_id' => 'required|exists:conversations,_id'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $conversation = Conversation::find($request->conversation_id);

            $users = User::whereIn('email', $request->members)->get()->pluck('id')->toArray();
            $conversation->members()->attach($users);

            $this->setResponse(false, 'Member Added Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }


    public function clearMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $conversation = Conversation::find($request->conversation_id);
            $conversation->messages->each->clean();

            dispatch(new DeleteMessageHistoryJob($conversation->id));

            $this->setResponse(false, "Message History Cleared.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
