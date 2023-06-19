<?php

namespace App\Traits;

use DateTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

trait TaskFilterable
{
    // methods list - that can be added in future
    //searchTodaysPendingTask()
    //SearchPreviousPendingTask()
    //getThisWeekPendingTask()
    //getTodaysPendingTask()
    //setTasks()
    //getTasks()
    //setCount()
    //getCount()

    private $_todayEnd;
    private $_endOfWeek;
    private $_todayStart;
    private $_startOfWeek;

    public function getTodaysPendingTask(Collection $tasks)
    {        
        $this->_todayStart = new DateTime(date('Y-m-d'));
        $this->_todayEnd = new DateTime(date('Y-m-d').'+1 day');
        
        $this->setSearchableTasks($tasks);

        $todayDueTasks = $this->searchTodaysPendingTask();
        $previousPendingTasks = $this->SearchPreviousPendingTask();
        return $todayDueTasks->merge($previousPendingTasks);
    }

    public function getThisWeeksPendingTask(Collection $tasks)
    {
        $this->_startOfWeek = CarbonImmutable::now()->startOfWeek();
        $this->_endOfWeek = CarbonImmutable::now()->endOfWeek(); //Carbon::parse('2021-07-29 17:26:00'); //for test

        return $tasks->filter(function($task) {
            $flag = false;
            $flag = ($task->due_date >= $this->_startOfWeek && $task->due_date <= $this->_endOfWeek) ? true : false ;            
            $flag = ($flag || ($task->due_date > $this->_endOfWeek && $task->start_date <= $this->_endOfWeek)) ? true : false ;
            $flag = ($flag || ($task->due_date < $this->_startOfWeek)) ? true : false ;
            $flag = $flag && $task->end_date == null ? true : false ;
            return $flag;
        });
    }
    
    
    private function searchTodaysPendingTask()
    {
        return $this->_searchableTasks->filter(function($task){
            $flag = false;
            $flag = ($task->due_date >= $this->_todayStart && $task->due_date < $this->_todayEnd) ? true : false;
            $flag = ($flag && $task->end_date == null) ? true : false;
            return $flag;
        });
    }

    private function SearchPreviousPendingTask()
    {
        return $this->_searchableTasks->filter(function($task){
            $flag = false;
            $flag = ($task->due_date < $this->_todayStart || $task->due_date > $this->_todayEnd) ? true : false;
            if($task->due_date > $this->_todayEnd){
                $flag = ($flag && ($task->start_date <= $this->_todayEnd || $task->start_date == null)) ? true : false;
            }
            // $flag = ($flag && $task->start_date <= $this->_todayEnd) ? true : false;
            $flag = ($flag && $task->end_date == null) ? true : false;
            return $flag;
        });
    }

    private function setSearchableTasks($searchableTasks) 
    {
        $this->_searchableTasks = $searchableTasks;
    }
}
