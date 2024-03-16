<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Tag;
use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\ProjectDeleteJob;
use App\Jobs\SyncGroupChatMember;
use Illuminate\Support\Facades\Gate;
use App\Jobs\CreateProjectChatGroup;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\MemberResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\ProjectResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProjectListResource;
use App\Http\Resources\TaskListResource;
use App\Http\Resources\TimelineResource;
use App\Jobs\CreateActivityJob;
use App\Jobs\CreateMemberInviteRegisterMail;
use App\Jobs\CreateMemberInviteAcknowledgeMail;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Timeline;
use App\Rules\MemberRoleCheck;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
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
        // if(!Gate::allows('index', Project::class)){ dd('not allowed'); }
        try {
            $this->authorize('index', Project::class);

            return (ProjectListResource::collection(auth()->user()->orderWith($request->order_by)->getProjects($request->task_by)))->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // "name" => "required|unique:projects,name|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:100",
            "name" => "required|unique:projects,name|max:100",
            "visibility" => "required|in:private,public,secret",
            "board_view" => "required|in:list,board,calendar,overview",
            // "members" => "filled",
            "members" => ["filled", "json", new MemberRoleCheck],
            "description" => "nullable|nullable|max:255",
            "attachments" => "filled|array",
            "attachments.*" => "filled|mimes:doc,jpg,jpeg,png,pdf,docx,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
            "image" => "filled|mimes:jpg,png|max:512",
            "milestones" => "filled|json",
            "tags" => "filled",
            "due_date" => "filled|date",
            "is_favourite" => "required|boolean",
            "customer_id" => "nullable|exists:users,_id",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::create($request->except(['members', 'attachments', 'milestones', 'tags', 'due_date', 'is_favourite', 'image']));

            if ($project) {
                $project->due_date = $request->due_date ? new DateTime($request->due_date) : null;

                //add members to project
                // $request->members .= "," . auth()->user()->email; // assign creator as member of project
                // $request->members = trim($request->members,',');
                // $this->attachMembers($request->members, $project);

                // $members = array_filter(explode(',', $request->members));
                $members = json_decode($request->members, true);
                $members[] = [
                    // "email" => auth()->user()->email, //keep project owner as member
                    "email" => $project->owner->email, //keep project owner as member
                    "role" => Project::CAN_EDIT
                ];

                /** Sync Member, App/Traits/SyncUsers **/
                $emails = Arr::pluck($members, 'email');
                $project->sync($emails, []);
                $project->skipUser(auth()->id())->sendProjectMail(false);

                /** Add project roles for members **/
                $project->addMembersRole($members);

                /** add customer to project **/
                if ($request->has('customer_id')) {
                    // Customer::find($request->customer_id)->update('project_id', $project->id)->save();
                    // $project->customers()->attach($request->customer_id);
                    $project->customer_id = $request->customer_id;
                }

                /** add milestones to project **/
                $milestones = json_decode($request->milestones, true);
                if (!empty($milestones)) {
                    foreach ($milestones as $key => $milestone) {
                        if ($milestone['has_attachment']) {
                            $files = $this->addFileAttachments($request->milestone_attachment[$key], 'milestone/attachments/');
                            $milestone['attachments'] = $files;
                        }
                        $milestone = $project->milestones()->create($milestone);
                    }
                }

                //upload image
                $uploadedLogo = $this->uploadFile($request->image, 'project/logos');
                if ($uploadedLogo != false) {
                    $project->image = $uploadedLogo;
                }

                // if ($request->has('image')) {
                //     $img = $request->image;
                //     $image = getUniqueStamp() . '.' . $request->image->extension();
                //     $imageName = 'project/logos/' . $image;
                //     $img->storeAs('public', $imageName);
                //     $project->image = $imageName;
                // }
                //upload attachments
                $files = $this->addFileAttachments($request->attachments, 'project/attachments/');
                $project->attachments = $files;

                //attach tags
                $this->attachTags($request->tags, $project);

                $project->status = 'pending';
                $project->save();

                if ($request->is_favourite) {
                    Auth::user()->favourite_projects()->attach($project);
                }

                /** Create activity log and create chat group, notification & FCM notification */
                $activityData = [
                    "activity" => "Project {$project->name} Created",
                    "activity_by" => Auth::id(),
                    "activity_time" => Carbon::now(),
                    "activity_data" => json_encode(["project_id" => $project->id, "assigned_by" => Auth::user()->name]),
                    "activity" => "project_created",
                ];

                dispatch(new CreateActivityJob($activityData));


                $this->_response['data'] = MemberResource::collection($project->members);
                $this->setResponse(false, 'Project created successfully.', 201);
            } else {
                $this->setResponse(false, 'Project not created, please try again.', 500);
            }
            return response()->json($this->_response, $this->_responseCode);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function update(Request $request)
    {
        $rules = [
            "id" => "required",
            // "name" => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\#\@']+$/u|max:100",
            "name" => "required|max:100",
            "visibility" => "required|in:private,public,secret",
            "board_view" => "required|in:list,board,calendar,overview",
            "members" => ["filled", "json", new MemberRoleCheck],
            // "members" => "required",
            "description" => "nullable|max:255",
            "attachments" => "filled|array",
            "removed_attachments" => "filled|json",
            "attachments.*" => "filled|mimes:doc,jpg,jpeg,png,pdf,docx,txt,ppt,pptm,pptx,xls,xlsx|max:2048",
            "image" => "filled|mimes:jpg,png|max:512",
            "milestones" => "filled|json",
            "tags" => "filled",
            "due_date" => "filled|date",
        ];

        if ($request->customer_id) {
            $rules["customer_id"] = "nullable|exists:users,_id,role_id," . getRoleBySlug('customer')->id;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::find($request->id);

            $this->authorize('update', [Project::class, $project]);

            // $this->authorize('update', $project); // Gate::authorize('update', $project);

            if ($project) {
                $project->update($request->except(['members', 'attachments', 'milestones', 'tags', 'removed_attachments', 'image', 'is_favourite']));

                //remove all members and re-attach members for current sync
                // $project->members()->detach($project->members->pluck('id')->toArray());
                // $request->members .= "," . $project->owner->email; // assign creator as member of task
                // $this->attachMembers($request->members, $project);

                //sync members
                // $request->members .= "," . $project->owner->email; // assign creator as member of task
                // $members = array_filter(explode(',', $request->members));
                // $members[] = $project->owner->email; //keep project owner as member
                $members = json_decode($request->members, true);
                $members[] = [
                    "email" => $project->owner->email, //keep project owner as member
                    "role" => Project::CAN_EDIT
                ];

                /** Sync Member, App/Traits/SyncUsers **/
                $emails = Arr::pluck($members, 'email');
                $project->sync($emails, $project->members()->pluck('email')->toArray());
                $project->sendProjectMail();

                /** Add project roles for members **/
                $project->addMembersRole($members);

                //update Customer
                if ($request->has('customer_id')) {
                    $project->customers()->detach();
                    $project->customer_ids = [];
                    $project->save();
                    $project->customers()->attach($request->customer_id);
                } else {
                    $project->customers()->detach();
                    $project->customer_ids = [];
                }

                //add milestones to project
                $milestones = json_decode($request->milestones, true);
                if (!empty($milestones)) {
                    foreach ($milestones as $key => $milestone) {
                        $milestoneRow = $project->milestones()->find($milestone['id']);
                        if ($milestoneRow) {
                            $milestoneRow->title = $milestone['title'];
                            $milestoneRow->description = $milestone['description'];
                            $milestoneRow->due_date = $milestone['due_date'];

                            //add new milestone attachments
                            if ($milestone['has_attachment'] && isset($request->milestone_attachment[$key])) {
                                $newFiles = $this->addFileAttachments($request->milestone_attachment[$key], 'milestone/attachments/');
                                if (!empty($newFiles)) {
                                    $milestoneRow->push('attachments', $newFiles);
                                }
                            }

                            //Remove milestone attachments
                            if (isset($request->milestone_attachment_remove[$key])) {
                                $filesToDeleteAsString = implode(',', $request->milestone_attachment_remove[$key]);
                                $deletedFiles = $this->removeFileAttachment($filesToDeleteAsString);
                                $milestoneRow->pull('attachments', $deletedFiles);
                            }

                            $milestoneRow->save();
                        } else {
                            if ($milestone['has_attachment']) {
                                $files = $this->addFileAttachments($request->milestone_attachment[$key], 'milestone/attachments/');
                                $milestone['attachments'] = $files;
                            }
                            $project->milestones()->create($milestone);
                        }
                    }
                }

                $remove_milestones = json_decode($request->remove_milestone);
                if (!empty($remove_milestones)) {
                    $removeMilestones = $project->milestones()->whereIn("_id", $remove_milestones)->get();
                    if ($removeMilestones) {
                        foreach ($removeMilestones as $milestone) {
                            $attachment = $milestone->attachments;
                            if (!empty($attachment)) {
                                $filesToDeleteAsString = implode(',', $milestone->attachments);
                                $this->removeFileAttachment($filesToDeleteAsString);
                            }
                            $milestone->delete();
                        }
                    }
                }

                $uploadedLogo = $this->uploadFile($request->image, 'project/logos');
                if ($uploadedLogo != false) {
                    $this->removeFile($project->image);
                    $project->image = $uploadedLogo;
                }

                //remove deleted attachments
                $remove_attachments = json_decode($request->removed_attachments, true);
                if (!empty($remove_attachments)) {
                    $filesToDeleteAsString = implode(',', $remove_attachments);
                    $deletedFiles = $this->removeFileAttachment($filesToDeleteAsString);
                    $project->pull('attachments', $deletedFiles);
                }

                //add attachments
                $newFiles = $this->addFileAttachments($request->attachments, 'project/attachments/');
                if (!empty($newFiles)) {
                    $project->push('attachments', $newFiles);
                }

                //detach tags and attach it back for sync
                $project->tags()->detach($project->tags->pluck('id')->toArray());
                $this->attachTags($request->tags, $project);

                $project->save();

                dispatch(new SyncGroupChatMember($project, $project->attachUsers, $project->detachUsers));



                $this->setResponse(false, 'Project updated successfully.', 200);
            } else {
                $this->setResponse(false, 'Project not found.', 404);
            }
            return response()->json($this->_response, $this->_responseCode);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    private function attachTags($tags, $project)
    {
        $tags = explode(',', $tags);
        if (!empty($tags)) {
            $tags = array_map("trim", $tags);
            foreach ($tags as $tag) {
                $tag = Tag::updateOrCreate(['name' => trim($tag)]);
                if ($tag) {
                    $project->tags()->attach($tag);
                }
            }
        }
    }

    private function attachMembers($members, $project)
    {
        $members = explode(',', $members);
        if (!empty(array_filter($members))) {
            $members = array_unique(array_map("trim", $members));
            $token = hash('sha256', Str::random(60));

            foreach ($members as $member) {
                $user = User::where('email', $member)->first();
                if ($user) {
                    $project->members()->attach($user);
                    $assignedTo[] = $user->name;
                } elseif (filter_var($member, FILTER_VALIDATE_EMAIL) != false) {
                    $user = $this->registerUser($member); //method derived from base controller
                    // dispatch(new CreateMemberInviteRegisterMail($user, Auth::user(), 'Project', $project->name, $token));
                    $project->members()->attach($user);
                    $project->save();
                    $assignedTo[] = $member;
                    // Mail::to($user)->queue(new SendInvitationMember($user, auth()->user(), "project", $project->name,$fcmResponse['shortLink']));
                }

                if ($user->name == "") {
                    dispatch(new CreateMemberInviteRegisterMail($user, Auth::user(), 'Project', $project->name, $token));
                } elseif (!$user->hasProject($project->id) && $user->email != "") {
                    dispatch(new CreateMemberInviteAcknowledgeMail($user, Auth::user(), 'Project', $project->name, $project->due_date, $project->members->count(), $project->description));
                }
            }

            //add activity logs
            if (!empty($assignedTo)) {
                $memberNames = implode(',', $assignedTo);
                $project->push('activity_logs', Auth::user()->name . " assigned project to " . $memberNames);
            }
        }
    }

    public function getProject($projectId)
    {
        $fields = ["project_id" => $projectId];
        $validator = Validator::make($fields, [
            "project_id" => "alpha_num|exists:projects,_id"
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            // $project = Project::with(['members', 'milestones', 'tags', 'tasks'])->find($projectId);
            if (auth()->user()->role->slug == 'customer') {
                $project = auth()->user()->AsCustomerProjects()->find($projectId);
            } else {
                $project = auth()->user()->projects()->find($projectId);
            }
            if ($project) {
                return (new ProjectResource($project))->additional(["error" => false, "message" => null]);
            }

            throw new \Exception('Project Not Found.');
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function markAsFavourite($projectId)
    {
        $fields = [
            "project_id" => $projectId,
        ];

        $validator = Validator::make($fields, [
            "project_id" => "required|alpha_num|exists:projects,_id",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::find($projectId);
            Auth::user()->favourite_projects()->attach($project);

            $this->setResponse(false, 'Added as Favourite Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }



    public function markAsUnfavourite($projectId)
    {
        $fields = [
            "project_id" => $projectId,
        ];

        $validator = Validator::make($fields, [
            "project_id" => "required|alpha_num|exists:projects,_id",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::find($projectId);
            if (Auth::user()->favourite_projects()->exists()) {
                Auth::user()->favourite_projects()->detach($project);
            }
            $this->setResponse(false, 'Removed From Favourites Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function inviteMember(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,_id',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:' . Project::CAN_EDIT . ',' . Project::CAN_COMMENT . ',' . Project::CAN_VIEW,
            'notes' => 'filled|regex:/^[\pL\pN\s\-]+$/u|max:250',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $user = User::where('email', $request->email)->first();
            $project = Project::find($request->project_id);
            if ($user) {
                $project->push('activity_logs', Auth::user()->name . " assigned project to " . $user->name);
            } else {
                $user = $this->registerUser($request->email);
                $project->push('activity_logs', Auth::user()->name . " assigned project to " . $request->email);
            }

            $project->members()->attach($user);
            $user->save();

            $project->addMemberRole($request->email, $request->role);

            /** Create Email notification & activity log ***/
            $project->attachUsers = [$user->id];
            $project->sendProjectMail();

            // /** Create activity log ***/
            // $activityData = [
            //     "activity" => "User Invited to Project {$project->name}",
            //     "activity_by" => Auth::id(),
            //     "activity_time" => Carbon::now(),
            //     "activity_data" => json_encode(["project_id" => $project->id, "invited_by" => Auth::user()->name, "receiver_id" => $user->id]),
            //     "activity" => "project_userinvited",
            // ];

            // dispatch(new CreateActivityJob($activityData));

            $this->setResponse(false, 'Invitation Send Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function complete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:projects,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::find($request->id)->markAsComplete();

            /** Create activity log ***/
            $activityData = [
                "activity" => "Project {$project->name} Completed",
                "activity_by" => Auth::id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["project_id" => $request->id]),
                "activity" => "project_completed",
            ];

            dispatch(new CreateActivityJob($activityData));

            $this->setResponse(false, 'Project Completed Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function reOpen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:projects,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::find($request->id)->markAsReopen();

            /** Create activity log ***/
            $activityData = [
                "activity" => "Project {$project->name} Reopened",
                "activity_by" => Auth::id(),
                "activity_time" => Carbon::now(),
                "activity_data" => json_encode(["project_id" => $project->id]),
                "activity" => "project_reopened",
            ];

            dispatch(new CreateActivityJob($activityData));

            $this->setResponse(false, 'Re-Open Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    /** Permanently Deleted Single Project ***/
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:projects,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            // $project = Project::with('tasks')->withTrashed()->withArchived()->find($request->id);
            $project = Project::withTrashed()->withArchived()->find($request->id);

            if ($project->created_by == auth()->id()) {
                $project->is_deleting = true;
                $project->save();
                dispatch(new ProjectDeleteJob($project));
                $this->setResponse(false, "Project is Deleting.");
            } else {
                //remove user from archive_by
                $project->unArchive();

                //detach user from project
                $project->members()->detach(Auth::user());

                //remove project role
                $project->roles()->where('user_id', auth()->id())->delete();

                auth()->user()->archives()->where('module', 'project')->where('module_id', $project->id)->delete();

                $this->setResponse(false, "Project Removed from Archive.");
            }

            // dispatch(
            //     (new ProjectDeleteJob($project))
            //     ->chain([
            //         // new CreateActivityJob($activityData)
            //     ])
            // );

            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    /** Permanently Deleted Multiple Project ***/
    public function deleteMultipleProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|exists:projects,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $projects = Project::withTrashed()->withArchived()->whereIn('_id', $request->ids)->get();
            $projects->each(function ($project) {

                if ($project->created_by == auth()->id()) {
                    $project->is_deleting = true;
                    $project->save();
                    dispatch(new ProjectDeleteJob($project));
                    $this->setResponse(false, "Project is Deleting.");
                } else {
                    //remove user from archive_by
                    $project->unArchive();

                    //detach user from project
                    $project->members()->detach(Auth::user());

                    //remove project role
                    $project->roles()->where('user_id', auth()->id())->delete();

                    auth()->user()->archives()->where('module', 'project')->where('module_id', $project->id)->delete();

                    $this->setResponse(false, "Project Removed from Archive.");
                }
            });

            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getAllTaskByProject($projectId)
    {
        $fields = [
            "project_id" => $projectId,
        ];

        $validator = Validator::make($fields, [
            "project_id" => "required|alpha_num|exists:projects,_id",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $project = Project::find($projectId);
            return (TaskListResource::collection($project->tasks))->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getTimeline($projectId)
    {
        $fields = [
            "project_id" => $projectId,
        ];

        $validator = Validator::make($fields, [
            "project_id" => "required|alpha_num|exists:projects,_id",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $timelineData = Timeline::where('project_id', $projectId)->get();
            return TimelineResource::collection($timelineData)->additional(["error" => false, "message" => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function csvImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $file = $request->csv_file;
            $path = "project/csv/";
            $fileFullName = $file->getClientOriginalName();
            $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME));
            $filePath = $path . $fileName . '-' . getUniqueStamp() . '.csv';
            $file->storeAs('public', $filePath);

            //import csv
            $csvFile = $filePath;

            if (Storage::disk('public')->exists($csvFile)) {
                $csvFile = Storage::disk('public')->path($csvFile);
                $file = fopen($csvFile, 'r');

                fgetcsv($file); // to skip first row
                while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {

                    $due_date = $data[4];
                    $end_date = $data[6];
                    
                    // If due_date is not available, add a day to the current date
                    if (!$due_date) {
                        $currentDate = Carbon::now();
                        $due_date = $currentDate->addDay();
                    }

                    // For project status
                    $status = "pending";
                    if ($end_date != null) {
                        if (strtotime($end_date) < strtotime($due_date)) {
                            $status = "completed";
                        } else {
                            $status = "delayed";
                        }
                    }

                    $project = Project::create([
                        'name' => $data[0],
                        'description' => $data[1],
                        'visibility' => $data[2],
                        'board_view' => $data[3],
                        'status' => $status,
                        'due_date' => $due_date,
                        'end_date' => $end_date,
                    ]);

                    $emails = explode(",", $data[5]);
                    $emails[] = $project->owner->email;

                    foreach ($emails as $email) {
                        $members[] = [
                            "email" => $email,
                            "role" => Project::CAN_EDIT
                        ];

                        $project->sync($emails, []);
                        $project->skipUser(auth()->id())->sendProjectMail(false);
                    }

                    $members[] = [
                        "email" => $project->owner->email, //keep project owner as member
                        "role" => Project::CAN_EDIT
                    ];

                    /** Add project roles for members **/
                    $project->addMembersRole($members);
                }

                Storage::disk('public')->delete($csvFile);
            }

            $this->setResponse(false, "Projects imported successfully");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
