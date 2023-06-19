<?php

namespace App\Http\Controllers;

use App\Events\MilestoneCompletedEvent;
use DateTime;
use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MilestoneResource;
use App\Http\Resources\TaskListResource;
use App\Jobs\CreateActivityJob;
use App\Models\Project;
use App\Rules\UniqueMilestoneTitle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MilestoneController extends Controller
{
    public function addMilestones(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,_id|bail',
            'title' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|unique:milestones,title,NULL,id,project_id,{$request->project_id}",
            // 'title' => ["required","bail","regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u", new UniqueMilestoneTitle],
            'description' => "nullable",
            'due_date' => "required|date",
            'attachments' => 'filled|array',
            'attachments.*' => "filled|mimes:doc,jpg,jpeg,png,pdf,docx,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }


        try {
            $this->authorize('canEdit', [Milestone::class, $request->project_id]);

            $milestone = Milestone::create($request->except('attachments', 'due_date'));
            if ($milestone) {
                $milestone->due_date = $request->due_date ? new DateTime($request->due_date) : null;
                if ($request->has('attachments')) {
                    $files = $this->addFileAttachments($request->attachments, 'milestone/attachments/');
                    $milestone->attachments = $files;
                }
                $milestone->save();
            }

            /** Create activity log and create chat group, notification & FCM notification */
            $activityData = [
                "activity" => "Milestone {$milestone->title} Created",
                "activity_by" => Auth::id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["project_id" => $request->project_id, 'milestone_title' => $milestone->title, 'author_name' => Auth::user()->name, "milestone_id" => $milestone->id]),
                "activity" => "milestone_created",
            ];

            dispatch(new CreateActivityJob($activityData));

            return (MilestoneResource::collection($milestone->project->milestones()->get()))->additional(["error" => false, "message" => __('Milestone Successfully Added.')]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function editMilestones(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:milestones,_id|bail',
            'project_id' => 'required|exists:projects,_id',
            'title' => ["required", "bail", "regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u", new UniqueMilestoneTitle],
            'description' => "nullable|max:250",
            'due_date' => "required|date",
            'attachments' => 'filled|array',
            'attachments.*' => "filled|mimes:doc,jpg,jpeg,png,pdf,docx,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
            "removed_attachments" => "filled",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }


        try {
            $this->authorize('canEdit', [Milestone::class, $request->project_id]);

            $milestone = Milestone::find($request->id);
            $milestone->update($request->except(['attachments', 'due_date']));
            $milestone->due_date = $request->due_date ? new DateTime($request->due_date) : null;
            if ($request->has('removed_attachments')) {
                $deletedFiles = $this->removeFileAttachment($request->removed_attachments);
                $milestone->attachments = array_values(array_diff($milestone->attachments, $deletedFiles));
            }
            if ($request->has('attachments')) {
                $files = $this->addFileAttachments($request->attachments, 'milestone/attachments/');
                $milestone->attachments = $files;
            }

            $milestone->save();

            $this->setResponse(false, 'Milestone Updated successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getMilestones($projectId)
    {
        $fields = [
            "project_id" => $projectId,
        ];

        $validator = Validator::make($fields, [
            "project_id" => "required|alpha_num|exists:projects,_id,deleted_at,NULL",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::find($projectId);
            return (MilestoneResource::collection($project->milestones()->orderBy('created_at', 'desc')->get()))->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function markAsComplete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => "required|alpha_num|exists:milestones,_id"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $milestone = Milestone::find($request->id);

            $this->authorize('canEdit', [Milestone::class, $milestone->project_id]);

            $milestone->markAsComplete();

            event(new MilestoneCompletedEvent($milestone));

            $this->setResponse(false, 'Milestone completed successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:milestones,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $milestone = Milestone::find($request->id);

            $this->authorize('canEdit', [Milestone::class, $milestone->project_id]);

            //1.Unassign all tasks
            $milestone->tasks()->detach();

            //2.Delete all attachments from the disk
            if (!empty($milestone->attachments)) {
                $filesToDeleteAsString = implode(',', $milestone->attachments);
                $this->removeFileAttachment($filesToDeleteAsString);
            }

            //3.Remove activity logs for table if any.
            //

            //4.delete the milestone permanently
            $milestone->delete();

            //store notification
            /** Create activity log and create chat group, notification & FCM notification */
            $activityData = [
                "activity" => "Milestone {$milestone->title} Deleted",
                "activity_by" => Auth::id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["project_id" => $milestone->project_id, "author_name" => Auth::user()->name, 'milestone_title' => $milestone->title, "milestone_id" => $milestone->id]),
                "activity" => "milestone_deleted",
            ];

            dispatch(new CreateActivityJob($activityData));

            $this->setResponse(false, 'Milestone Permanently Deleted.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function get(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:milestones,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $milestone = Milestone::find($request->id);
            return (TaskListResource::collection($milestone->tasks))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
