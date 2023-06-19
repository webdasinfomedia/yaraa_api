<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Tenant;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CreateRecurringTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Recurring Task For Today';

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
            $tasks = Task::all();

            $tasks->each(function ($task) {

                if ($task->task_id == null && $task->start_date != null && $task->due_date != null) {
                    switch ($task->recurrence) {
                        case 'daily':
                            $this->createDailyTask($task);
                            break;
                        case 'weekly':
                            $this->createWeeklyTask($task);
                            break;
                        case 'monthly':
                            $this->createMonthlyTask($task);
                            break;
                        case 'yearly':
                            $this->createYearlyTask($task);
                            break;
                    }
                }
            });
        });
    }

    private function createDailyTask($task)
    {
        try {

            //get latest recurred task
            $latestRecurringTask = $task->tasks()->orderBy('created_at', 'desc')->first();

            //get next recurring day
            $start = Carbon::now()->startOfDay();
            $end = Carbon::now()->endOfDay();

            //get latest recurred task
            $created = $task->tasks()->whereBetween('created_at', [$start, $end])->exists();

            //create recurring task if not created
            if (!$created && $start->isToday()) {
                $fetchDateTask = $latestRecurringTask ? $latestRecurringTask : $task;

                if ($task->due_date) $task->due_date = $fetchDateTask->due_date->addDay();
                if ($task->start_date) $task->start_date = $fetchDateTask->start_date->addDay();
                // if ($task->end_date) $task->end_date = $task->end_date ? $fetchDateTask->end_date->addDay() : null;
                $this->createTask($task);
            }
        } catch (\Throwable $th) {
            \Log::debug("Tenant Id : " . app('tenant')->id . "(" . app('tenant')->business_name . ")");
            \Log::debug("Error recurring in task : {$task->id}");
            \Log::debug("In file : {$th->getFile()} : {$th->getLine()}");
            \Log::debug("Error :{$th->getMessage()}");
        }
    }

    private function createWeeklyTask($task)
    {
        //get latest recurred task
        $latestRecurringTask = $task->tasks()->orderBy('created_at', 'desc')->first();

        //get next recurring day
        if ($latestRecurringTask) {
            $start = $latestRecurringTask->created_at->addWeek()->startOfDay();
            $end = $latestRecurringTask->created_at->addWeek()->endOfDay();
        } else {
            $start = $task->created_at->addWeek()->startOfDay();
            $end = $task->created_at->addWeek()->endOfDay();
        }

        //create recurring task if not created
        $created = $task->tasks()->whereBetween('created_at', [$start, $end])->exists();
        if (!$created && $start->isToday()) {
            $fetchDateTask = $latestRecurringTask ? $latestRecurringTask : $task;

            if ($task->due_date) $task->due_date = $fetchDateTask->due_date->addWeek();
            if ($task->start_date) $task->start_date = $fetchDateTask->start_date->addWeek();
            // if ($task->end_date) $task->end_date = $task->end_date ? $fetchDateTask->end_date->addWeek() : null;
            $this->createTask($task);
        }
    }

    private function createMonthlyTask($task)
    {
        //get the day name for recurrence
        $recurringDay = $task->created_at->format('l');
        $dayOfMonth = $this->getDayInMonth($recurringDay);

        if ($dayOfMonth != null) {

            //get latest recurred task
            $latestRecurringTask = $task->tasks()->orderBy('created_at', 'desc')->first();

            //get next recurring day
            if ($latestRecurringTask) {
                Carbon::setTestNow($latestRecurringTask->created_at);

                $start = $latestRecurringTask->created_at->parse("{$dayOfMonth} {$recurringDay} of next month")->startOfDay();
                $end = $latestRecurringTask->created_at->parse("{$dayOfMonth} {$recurringDay} of next month")->endOfDay();
            } else {
                Carbon::setTestNow($task->created_at);

                $start = $task->created_at->parse("{$dayOfMonth} {$recurringDay} of next month")->startOfDay();
                $end = $task->created_at->parse("{$dayOfMonth} {$recurringDay} of next month")->endOfDay();
            }

            //create recurring task if not created
            $created = $task->tasks()->whereBetween('created_at', [$start, $end])->exists();
            Carbon::setTestNow();
            if (!$created && $start->isToday()) {
                if ($task->due_date) $task->due_date = $task->due_date->addMonth();
                if ($task->start_date) $task->start_date = $task->start_date->addMonth();
                // if ($task->end_date) $task->end_date = $task->end_date ? $task->end_date->addMonth() : null;
                $this->createTask($task);
            }
        }
    }

    private function createYearlyTask($task)
    {
        //get latest recurred task
        $latestRecurringTask = $task->tasks()->orderBy('created_at', 'desc')->first();

        //get next recurring day
        if ($latestRecurringTask) {
            $start = $latestRecurringTask->created_at->addYear()->startOfDay();
            $end = $latestRecurringTask->created_at->addYear()->endOfDay();
        } else {
            $start = $task->created_at->addYear()->startOfDay();
            $end = $task->created_at->addYear()->endOfDay();
        }

        //create recurring task if not created
        $created = $task->tasks()->whereBetween('created_at', [$start, $end])->exists();
        if (!$created && $start->isToday()) {
            $fetchDateTask = $latestRecurringTask ? $latestRecurringTask : $task;

            if ($task->due_date) $task->due_date = $fetchDateTask->due_date->addYear();
            if ($task->start_date) $task->start_date = $fetchDateTask->start_date->addYear();
            // if ($task->end_date) $task->end_date = $task->end_date ? $fetchDateTask->end_date->addYear() : null;
            $this->createTask($task);
        }
    }

    private function createTask($task)
    {
        $task->load('assignedTo');
        $task->load('milestones');
        $task->load('tags');

        $newTask = $task->replicate();
        $newTask->task_id = $task->id;
        $newTask->reminder_sent = false;
        $newTask->push();

        foreach ($task->getRelations() as $relation => $items) {
            foreach ($items as $item) {
                $newTask->{$relation}()->attach($item);
            }
        }

        if (!empty($newTask->attachments)) {
            $filesToAttach = [];
            foreach ($newTask->attachments as $file) {
                $parts = explode("/", $file);
                $search = '.';
                $replace = '-' . getUniqueStamp() . '-copy.';
                $target = end($parts);

                $newName = strrev(implode(strrev($replace), explode(strrev($search), strrev($target), 2)));
                $newFile = str_replace($target, $newName, $file);
                $filesToAttach[] = $newFile;
                Storage::copy("public/{$file}", "public/{$newFile}");
            }
            $newTask->attachments = $filesToAttach;
        }

        // $task->start_date != null && $task->end_date == null ? $newTask->start_date = new DateTime() : null;
        $newTask->save();
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
