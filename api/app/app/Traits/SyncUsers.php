<?php

namespace App\Traits;

use App\Facades\CreateDPWithLetter;
use App\Jobs\CreateActivityJob;
use App\Jobs\CreateMemberInviteAcknowledgeMail;
use App\Jobs\CreateMemberInviteRegisterMail;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

trait SyncUsers
{
    public $attachUsersEmail = [];
    public $detachUsers = [];
    public $attachUsers = [];
    public $relation = null;
    public $model = null;

    public function sync($keepUsersEmail, array $oldUsersEmail, $relation = 'members')
    {
        $this->relation = $relation;
        $this->model = class_basename($this);

        $keepUsersEmail = array_unique(array_map("trim", $keepUsersEmail));

        $this->attachUsersEmail = Arr::where($keepUsersEmail, function ($email) use ($oldUsersEmail) {
            return in_array($email, $oldUsersEmail) ? false : true;
        });

        $this->detachUsers = $this->{$relation}->filter(function ($member) use ($keepUsersEmail) {
            return !in_array($member->email, $keepUsersEmail) ? true : false;
        })->pluck('id');

        $this->attachUsers();

        $this->detachUsers();

        return $this;
    }

    private function attachUsers()
    {
        if (!empty($this->attachUsersEmail)) {
            // $this->attachUsers = User::whereIn('email', $this->attachUsersEmail)->pluck('_id')->toArray();
            // $this->{$this->relation}()->attach($this->attachUsers);
            // $this->save();

            foreach ($this->attachUsersEmail as $email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $this->{$this->relation}()->attach($user);
                    // $assignedTo[] = $user->name;
                } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) != false) {
                    $user = $this->registerUser($email); //method derived from base controller
                    $this->{$this->relation}()->attach($user);
                    $this->save();
                }
            }

            $this->attachUsers = User::whereIn('email', $this->attachUsersEmail)->pluck('_id')->toArray();
        }
    }

    private function detachUsers()
    {
        if (!empty($this->detachUsers)) {
            foreach ($this->detachUsers as $userId) {
                $this->{$this->relation}()->detach($userId);

                //remove user from all tasks of this project
                if ($this->model == 'Project') {
                    $this->tasks->each(function ($task) use ($userId) {
                        $task->assignedTo()->detach($userId);
                    });

                    if ($this->roles()->where('user_id', $userId)->exists()) {
                        $this->roles()->where('user_id', $userId)->delete();
                    }
                }
            }
        }
    }

    protected function registerUser($email)
    {
        if (isUserLimitReached()) {
            throw new Exception("User Limit Reached,Please upgrade plan or delete user to add more.");
        }

        if (isAlreadyTenantSlaveUser($email)) {
            // throw new Exception("User with same email already exists.");
            $user = cloneUser($email, app('tenant')->id);
        } else {
            $user = User::create(["email" => $email]);
            $imageName = 'user_images/' . getUniqueStamp() . '.png';
            $path = 'public/' . $imageName;
            $img = CreateDPWithLetter::create($email);
            Storage::put($path, $img->encode());
            $user->name = strtok($email, '@');

            $image_resize = Image::make(Storage::path($path));
            $image_resize->resize(48, 48); //before 60x60
            $fileFullName = $imageName;
            $fileName = str_replace(' ', '_', pathinfo($fileFullName, PATHINFO_FILENAME)) .  getUniqueStamp() . '_48x48.' .  'png';
            $image_resize->save(base_path('public/storage/user_images/' . $fileName), 60);

            $user->image = $imageName;
            $user->image_48x48 = "user_images/{$fileName}";
            $user->is_verified = false;
            $user->save();

            createTenantSlaveUser($user->email);
        }

        return $user;
    }

    public function sendProjectMail($createActivityLog = true)
    {
        $token = Crypt::encryptString(app('tenant')->id);

        foreach ($this->attachUsers as $userId) {
            $user = User::find($userId);
            $user->push('invite_member_token', $token);
            if (!$user->is_verified) {
                dispatch(new CreateMemberInviteRegisterMail($user, Auth::user(), 'Project', $this->name, $token));
            } else {
                if (getNotificationSettings('email') || getNotificationSettings('email') == "true") {
                    dispatch(new CreateMemberInviteAcknowledgeMail($user, Auth::user(), 'Project', $this->name, $this->due_date, $this->members->count(), $this->description));
                }
            }

            if ($createActivityLog) {
                /** Create activity log ***/
                $activityData = [
                    "activity_title" => "User Invited to Project {$this->name}",
                    "activity_by" => Auth::id(),
                    "activity_time" => Carbon::now(),
                    "activity_data" => json_encode(["project_id" => $this->id, "invited_by" => Auth::user()->id, "receiver_id" => $userId]),
                    "activity" => "project_userinvited",
                ];

                dispatch(new CreateActivityJob($activityData));
            }
        }
    }

    public function sendTaskMail($createActivityLog = true)
    {
        $token = Crypt::encryptString(app('tenant')->id);

        foreach ($this->attachUsers as $userId) {
            $user = User::find($userId);
            $user->push('invite_member_token', $token);
            if (!$user->is_verified) {
                dispatch(new CreateMemberInviteRegisterMail($user, Auth::user(), 'Task', $this->name, $token));
            } else {
                if (getNotificationSettings('email') || getNotificationSettings('email') == "true") {
                    dispatch(new CreateMemberInviteAcknowledgeMail($user, Auth::user(), 'Task', $this->name, $this->due_date, $this->assignedTo->count(), $this->description, $this->priority));
                }
            }

            if ($createActivityLog) {
                /** Create activity log ***/
                $activityData = [
                    "activity" => "User Assigned to Task {$this->name}",
                    "activity_by" => Auth::id(),
                    "activity_time" => Carbon::now(),
                    // "activity_data" => json_encode(["task_id" => $this->id, "invited_by" => Auth::user()->id, "receiver_id" => $userId]),
                    "activity_data" => json_encode(["task_id" => $this->id, "receiver_id" => $userId]),
                    "activity" => "task_userinvited",
                ];

                dispatch(new CreateActivityJob($activityData));
            }
        }
    }

    public function skipUser($userId)
    {
        if (($key = array_search($userId, $this->attachUsers)) !== false) {
            unset($this->attachUsers[$key]);
        }

        return $this;
    }

    /*** Add user to project if not already a member **/
    public function syncProjectMember()
    {
        if (!empty($this->attachUsersEmail)) {
            foreach ($this->attachUsersEmail as $email) {
                $user = User::where('email', $email)->first();
                if ($user && $this->project()->exists() && !$this->project->members->contains($user->id)) {
                    $this->project->members()->attach($user);
                    $this->project->addMemberRole($email, Project::CAN_EDIT);
                }
            }
        }
    }
}
