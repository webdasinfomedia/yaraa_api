<?php

namespace App\Http\Controllers;

use App\Events\SendFcmNotification;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        try {
            $notifications = Notification::whereIn('receiver_ids', [auth()->id()])->orderBy('created_at', 'desc')->get();
            return NotificationResource::collection($notifications)->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function readNotification()
    {
        try {
            Notification::where('receiver_ids', auth()->id())->where('read_by', '!=', auth()->id())->push('read_by', auth()->id());

            $this->setResponse(false, "Marked as read successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
