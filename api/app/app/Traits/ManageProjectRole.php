<?php

namespace App\Traits;

use App\Models\ProjectRole;
use App\Models\User;
use DateTimeZone;

trait ManageProjectRole
{
    public function addMemberRole($email, $role)
    {
        $user = User::where('email', $email)->first();

        ProjectRole::updateOrCreate(
            ["project_id" => $this->id, "user_id" => $user->id],
            ["role" => $role]
        );
    }

    public function addMembersRole(array $members)
    {
        if (!empty($members)) {
            foreach ($members as $member) {
                $user = User::where('email', $member['email'])->first();
                if ($user) {
                    ProjectRole::updateOrCreate(
                        ["project_id" => $this->id, "user_id" => $user->id],
                        ["role" => $member['role']]
                    );
                }
            }
        }
    }
}
