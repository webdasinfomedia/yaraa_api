<?php

namespace App\Http\Controllers;

use App\Facades\CreateDPWithLetter;
use App\Http\Resources\GroupDetailsResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{
    public function createGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => "required|unique:conversations,name|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:50",
            'members' => 'required',
            'members.*' => 'filled|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $conversation = new Conversation;
            $conversation->type = 'group';
            $conversation->name = $request->name;
            $conversation->last_message_at = null;

            $imageName = 'group/logo/' . getUniqueStamp() . '.png';
            $path = 'public/' . $imageName;
            $img = CreateDPWithLetter::create($request->name);
            Storage::put($path, $img->encode());
            $conversation->logo = $imageName;
            $conversation->created_by = auth()->id();
            $conversation->save();

            $conversation->members()->attach([auth()->id()]);
            foreach ($request->members as $email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $conversation->members()->attach($user->id);
                }
            }

            $this->setResponse(false, 'Group Created successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function editGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "conversation_id" => 'required|exists:conversations,_id',
            "name" => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:100",
            "image" => "filled|mimes:jpg,png|max:512",
            'members' => 'required',
            'members.*' => 'filled|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $conversation = Conversation::find($request->conversation_id);
            $conversation->name = $request->name;

            $uploadedLogo = $this->uploadFile($request->image, 'project/logos');
            if ($uploadedLogo != false) {
                $conversation->logo = $uploadedLogo;
            }

            $members = $request->members;
            $members[] = $conversation->owner->email;
            $conversation->sync($members, $conversation->members()->pluck('email')->toArray());

            $conversation->save();

            $this->setResponse(false, "Group Updated Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function leaveGroup(Request $request)
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
            $conversation->members()->detach(auth()->id());

            $this->setResponse(false, "You Left From Group Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function groupDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,_id,type,group',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $conversation = Conversation::where('type', 'group')->find($request->conversation_id);

            return (new GroupDetailsResource($conversation))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function cleanConversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $conversation = Conversation::find($request->conversation_id);
            $conversation->clean();

            $membersArray = $conversation->members->pluck('id')->toArray();
            $arrayMatch = array_intersect($membersArray, $conversation->deleted_by);

            /** if all members have cleaned the conversation then delete it. */
            if (sizeof($arrayMatch)  == sizeof($membersArray)) {

                $conversation->messages->each(function ($message) {
                    foreach ($message->attachments as $file) {
                        if (Storage::disk()->exists('public/' . $file)) {
                            Storage::disk('public')->delete('public/' . $file);
                            Storage::delete('public/' . $file);
                        }
                    }

                    if ($message->logo && Storage::disk()->exists('public/' . $message->logo)) {
                        Storage::disk('public')->delete('public/' . $message->logo);
                        Storage::delete('public/' . $message->logo);
                    }
                });

                $conversation->messages->each->delete();
                $conversation->delete();
            }

            $this->setResponse(false, 'Conversation Deleted Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function deleteConversation($groupId)
    {
        $validator = Validator::make(['group_id' => $groupId], [
            'group_id' => 'required|exists:conversations,_id,type,group'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $conversation = Conversation::find($groupId);

            if ($conversation->created_by !== auth()->id()) {
                throw new \Exception('You do not have permission to delete group.');
            }

            //Delete Message History
            $conversation->messages->each(function ($message) {
                foreach ($message->attachments as $file) {
                    if (Storage::disk()->exists('public/' . $file)) {
                        Storage::disk('public')->delete('public/' . $file);
                        Storage::delete('public/' . $file);
                    }
                }

                if ($message->logo && Storage::disk()->exists('public/' . $message->logo)) {
                    Storage::disk('public')->delete('public/' . $message->logo);
                    Storage::delete('public/' . $message->logo);
                }

                $message->delete(); //delete message permanently
            });

            //Remove members from group
            $conversation->members->each(function ($member) use ($conversation) {
                $conversation->members()->detach($member);
            });

            //delete conversation permanently
            $conversation->delete();

            $this->setResponse(false, 'Group Deleted Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
// group/logo/daIa0-1601412872702844.jpg