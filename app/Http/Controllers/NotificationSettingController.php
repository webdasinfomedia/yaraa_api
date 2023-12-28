<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function setSettings(Request $request)
    {
        try{
            $settings = NotificationSetting::first();
            $settings = $settings ? $settings : NotificationSetting::create([]);
            $settings->update($request->all());

            // /** email notification settings **/
            // $settings->email = $request->email;

            // /** Push notification settings **/
            // $settings->push = $request->push;

            // /** Task notification settings **/
            // $settings->task_created = $request->task_created;
            // $settings->task_completed = $request->task_completed;
            // $settings->task_uncompleted = $request->task_uncompleted;
            // $settings->task_comment = $request->task_comment;
            // $settings->task_deleted = $request->task_deleted;
            // $settings->task_restored = $request->task_restored;

            // /** Project notification settings **/
            // $settings->project_created = $request->project_created;
            // $settings->project_invite_member = $request->project_invite_member;
            // $settings->project_completed = $request->project_completed;
            // $settings->project_reopen = $request->project_reopen;
            // $settings->project_deleted = $request->project_deleted;
            // $settings->project_restored = $request->project_restored;

            // /** Message notification settings **/
            // $settings->message_notify_recipients = $request->message_notify_recipients;

            // /** Milestone notification settings **/
            // $settings->milestone_created = $request->milestone_created;
            // $settings->milestone_completed = $request->milestone_completed;
            // $settings->milestone_reopen = $request->milestone_reopen;
            // $settings->milestone_deleted = $request->milestone_deleted;

            // $settings->save();

            $this->setResponse(false, 'Notification Settings Updated Successfully');
            return response()->json($this->_response);

        } catch(\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getSettings()
    {
        try{
            $settings = NotificationSetting::first();
            $this->_response['data'] = $settings;
            $this->setResponse(false, null);
            return response()->json($this->_response);
        } catch(\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
