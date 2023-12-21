<?php

namespace App\Traits;

trait TaskProgressible
{
    private $_totalItems = 0;
    private $_totalCompletedItems = 0;
    private $_avgProgress = 0;

    /**
     * 
     * calculate task,subtask progress
     * 
     * @return int average progress
     * 
     */
    public function getProgress() : int
    {
        $this->setTaskProgress();

        return $this->calculateAvgProgress();
    }

    /**
     * set tasks count
     * @return none
     */
    private function setTaskProgress() : void
    {
        if($this->end_date != null){
            $this->_totalItems += 1;
            $this->_totalCompletedItems += 1;
        }else{
            $this->_totalItems += 1;
            $this->setSubTasksProgress();
        }
    }

    /**
     * set subtask count
     * @return none
     */
    private function setSubTasksProgress() : void
    {
        $this->_totalItems += $this->subtasks->count();
        $this->_totalCompletedItems += $this->subtasks->whereNotNull('end_date')->count();
    }

    private function calculateAvgProgress() : int
    {
        return $this->_totalCompletedItems > 0 
                    ? (int)round(($this->_totalCompletedItems * 100) / $this->_totalItems)
                    :0;
    }

}
