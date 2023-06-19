<?php

namespace App\Traits;

trait ProjectProgressible
{
    private $_totalItems = 0;
    private $_totalCompletedItems = 0;
    private $_avgProgress = 0;

    /**
     * 
     * calculate task,milestone progress
     * 
     * @return int average progress
     * 
     */
    public function getProgress() : int
    {
        $this->setTasksProgress();
        $this->setMilestoneProgress();

        return $this->calculateAvgProgress();
    }

    /**
     * set projects tasks,subtasks count
     * @return none
     */
    private function setTasksProgress() : void
    {
        $this->_totalItems += $this->tasks->count();
        $this->_totalCompletedItems += $this->tasks->whereNotNull('end_date')->count();

        $this->tasks->each(function ($task){
            if($task->subtasks){
                $this->_totalItems += $task->subtasks->count();
                $this->_totalCompletedItems += $task->subtasks->whereNotNull('end_date')->count();
            }
        });
    }

    /**
     * set projects milestone count
     * @return none
     */
    private function setMilestoneProgress() : void
    {
        $this->_totalItems += $this->milestones->count();
        $this->_totalCompletedItems += $this->milestones->whereNotNull('end_date')->count();
    }

    private function calculateAvgProgress() : int
    {
        return $this->_totalCompletedItems > 0 
                    ? (int)round(($this->_totalCompletedItems * 100) / $this->_totalItems)
                    :0;
    }

}
