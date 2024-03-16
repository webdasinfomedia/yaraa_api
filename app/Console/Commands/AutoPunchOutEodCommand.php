<?php

namespace App\Console\Commands;

use App\Models\PunchDetail;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoPunchOutEodCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:punchout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Punch Out at the end of the day with user time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tenants = Tenant::all();

        $tenants->each(function ($tenant) {
            $tenant->configure()->use();

            $users = User::all();

            $users->each(function ($user) {
                $dt = Carbon::now($user->timezone)->startOfDay();
                $edt = Carbon::now($user->timezone)->endOfDay();
                $now = Carbon::now($user->timezone);

                //find is it end of the day
                $punchDetails = PunchDetail::where('user_id', $user->id)
                    ->whereBetween('created_at', [$dt, $edt])
                    ->whereNotNull('punch_in')
                    ->whereNull('punch_out');
                if ($punchDetails->exists()) {
                    $punchRecord = $punchDetails->first();
                    // if ($now->hour == 23 && $now->minute > 54) {
                    $startHour = $punchRecord->punch_in;
                    $endHour = Carbon::now();

                    $diff = $startHour->floatDiffInMinutes($endHour);
                    $diff = floatval(number_format($diff, 2));

                    $punchRecord->punch_out = $endHour;
                    $punchRecord->total_work_hour = $diff;
                    $punchRecord->save();
                    // }
                }

                /** Update user preference to handel frontend use case **/
                // if ($now->hour == 23 && $now->minute > 54) {
                if ($user->preferences != null) {
                    $user->preferences->update(["punch_details" => "punch_out"]);
                } else {
                    $user->preferences()->create(["punch_details" => "punch_out"]);
                }
                // }
            });
        });
    }
}
