<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CreateRecurringTodo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todo:recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Recurring Todo For Today';

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
                $user->todos->each(function ($todo) use ($user) {
                    if ($todo->todo_id == null) {
                        switch ($todo->recurrence) {
                            case 'daily':
                                $this->createDailyTodo($user, $todo);
                                break;
                            case 'weekly':
                                $this->createWeeklyTodo($user, $todo);
                                break;
                            case 'monthly':
                                $this->createMonthlyTodo($user, $todo);
                                break;
                            case 'yearly':
                                $this->createYearlyTodo($user, $todo);
                                break;
                        }
                    }
                });
            });
        });
    }

    private function createDailyTodo($user, $todo)
    {
        //get latest recurred todo
        $latestRecurringTodo = $user->todos()->where('todo_id', $todo->id)->last();

        //get next recurring day
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();

        //get latest recurred todo
        $created = $user->todos()->where('todo_id', $todo->id)->whereBetween('created_at', [$start, $end])->isNotEmpty();

        //create recurring todo if not created
        if (!$created && $start->isToday()) {
            $fetchDateTodo = $latestRecurringTodo ? $latestRecurringTodo : $todo;

            if ($todo->due_date) $todo->due_date = $fetchDateTodo->due_date->addDay();
            if ($todo->start_date) $todo->start_date = $fetchDateTodo->start_date->addDay();
            if ($todo->end_date) $todo->end_date = $todo->end_date ? $fetchDateTodo->end_date->addDay() : null;
            $this->createTodo($user, $todo);
        }
    }

    private function createWeeklyTodo($user, $todo)
    {
        //get latest recurred todo
        $latestRecurringTodo = $user->todos()->where('todo_id', $todo->id)->last();

        //get next recurring day
        if ($latestRecurringTodo) {
            $start = $latestRecurringTodo->created_at->addWeek()->startOfDay();
            $end = $latestRecurringTodo->created_at->addWeek()->endOfDay();
        } else {
            $start = $todo->created_at->addWeek()->startOfDay();
            $end = $todo->created_at->addWeek()->endOfDay();
        }

        //create recurring todo if not created
        $created = $user->todos()->where('todo_id', $todo->id)->whereBetween('created_at', [$start, $end])->isNotEmpty();
        if (!$created && $start->isToday()) {
            $fetchDateTodo = $latestRecurringTodo ? $latestRecurringTodo : $todo;

            if ($todo->due_date) $todo->due_date = $fetchDateTodo->due_date->addWeek();
            if ($todo->start_date) $todo->start_date = $fetchDateTodo->start_date->addWeek();
            if ($todo->end_date) $todo->end_date = $todo->end_date ? $fetchDateTodo->end_date->addWeek() : null;
            $this->createTodo($user, $todo);
        }
    }

    private function createMonthlyTodo($user, $todo)
    {
        //get the day name for recurrence
        $recurringDay = $todo->created_at->format('l');
        $dayOfMonth = $this->getDayInMonth($recurringDay);

        if ($dayOfMonth != null) {

            //get latest recurred todo
            $latestRecurringTodo = $user->todos()->where('todo_id', $todo->id)->last();

            //get next recurring day
            if ($latestRecurringTodo) {
                Carbon::setTestNow($latestRecurringTodo->created_at);

                $start = $latestRecurringTodo->created_at->parse("{$dayOfMonth} {$recurringDay} of next month")->startOfDay();
                $end = $latestRecurringTodo->created_at->parse("{$dayOfMonth} {$recurringDay} of next month")->endOfDay();
            } else {
                Carbon::setTestNow($todo->created_at);

                $start = $todo->created_at->parse("{$dayOfMonth} {$recurringDay} of next month")->startOfDay();
                $end = $todo->created_at->parse("{$dayOfMonth} {$recurringDay} of next month")->endOfDay();
            }

            //create recurring todo if not created
            $created = $user->todos()->where('todo_id', $todo->id)->whereBetween('created_at', [$start, $end])->isNotEmpty();
            Carbon::setTestNow();
            if (!$created && $start->isToday()) {
                if ($todo->due_date) $todo->due_date = $todo->due_date->addMonth();
                if ($todo->start_date) $todo->start_date = $todo->start_date->addMonth();
                if ($todo->end_date) $todo->end_date = $todo->end_date ? $todo->end_date->addMonth() : null;
                $this->createTodo($user, $todo);
            }
        }
    }

    private function createYearlyTodo($user, $todo)
    {
        //get latest recurred todo
        $latestRecurringTodo = $user->todos()->where('todo_id', $todo->id)->last();

        //get next recurring day
        if ($latestRecurringTodo) {
            $start = $latestRecurringTodo->created_at->addYear()->startOfDay();
            $end = $latestRecurringTodo->created_at->addYear()->endOfDay();
        } else {
            $start = $todo->created_at->addYear()->startOfDay();
            $end = $todo->created_at->addYear()->endOfDay();
        }

        //create recurring todo if not created
        $created = $user->todos()->where('todo_id', $todo->id)->whereBetween('created_at', [$start, $end])->isNotEmpty();
        if (!$created && $start->isToday()) {
            $fetchDateTodo = $latestRecurringTodo ? $latestRecurringTodo : $todo;

            if ($todo->due_date) $todo->due_date = $fetchDateTodo->due_date->addYear();
            if ($todo->start_date) $todo->start_date = $fetchDateTodo->start_date->addYear();
            if ($todo->end_date) $todo->end_date = $todo->end_date ? $fetchDateTodo->end_date->addYear() : null;
            $this->createTodo($user, $todo);
        }
    }

    private function createTodo($user, $todo)
    {
        // $todo->load('assignedTo');
        // $todo->load('milestones');
        // $todo->load('tags');

        $newTodo = $todo->replicate();

        $newTodo->todo_id = $todo->id;
        $newTodo->reminder_sent = false;

        // $newTodo->push();

        // foreach ($todo->getRelations() as $relation => $items) {
        //     foreach ($items as $item) {
        //         $newTodo->{$relation}()->attach($item);
        //     }
        // }

        if (!empty($newTodo->attachments)) {
            $filesToAttach = [];
            foreach ($newTodo->attachments as $file) {
                $parts = explode("/", $file);
                $search = '.';
                $replace = '-' . getUniqueStamp() . '-copy.';
                $target = end($parts);

                $newName = strrev(implode(strrev($replace), explode(strrev($search), strrev($target), 2)));
                $newFile = str_replace($target, $newName, $file);
                $filesToAttach[] = $newFile;
                Storage::copy("public/{$file}", "public/{$newFile}");
            }
            $newTodo->attachments = $filesToAttach;
        }

        // $todo->start_date != null && $todo->end_date == null ? $newTodo->start_date = new DateTime() : null;
        // dd($newTodo->toArray());
        $user->todos()->create($newTodo->toArray()); //->save();
    }

    public function getDayInMonth($day)
    {
        /** get all dates of a particular week-day of the month */
        $dates = new \DatePeriod(
            Carbon::parse("first {$day} of this month"),
            CarbonInterval::week(),
            Carbon::parse("first {$day} of next month")
        );
        $days = [];
        foreach ($dates as $date) {
            $days[] = $date->format('Y-m-d');
        }

        /** search todays date exists in the list of dates */
        $searchKey = array_search(Carbon::now()->format('Y-m-d'), $days);
        $dayOfMonth = null;

        /** get todays no. for the day in the month, eg: second monday of this month **/
        if ($searchKey !== false) {
            $searchKey++;
            $weekWords = [
                1 => "first",
                2 => "second",
                3 => "third",
                4 => "fourth",
                5 => "fifth",
            ];

            $dayOfMonth = $weekWords[$searchKey];
        }

        /** return the occurrences **/
        return $dayOfMonth;
    }
}
