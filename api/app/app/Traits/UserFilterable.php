<?php

namespace App\Traits;

/**
 * @method public orderWith()
 * @method public getTasks()
 * @method private getTodaysTasks()
 * @method private getThisWeekTasks()
 * @method private getThisWeekTasks()
 * @method public getProjects()
 * @method private getTodaysProjects()
 * @method private getThisWeekProjects()
 * @method private getAllProjects()
 * @method private setSortField()
 */

trait UserFilterable
{
    use TaskFilterable;

    private $_doSort = false;

    /**
     * @param string 
     * 
     * @return object 
     */
    public function orderWith($orderBy = 'new_first')
    {
        switch ($orderBy){
            case 'new_first':
                $this->setSortField('created_at', true); //true = descending order
            break;
            case 'old_first':
                $this->setSortField('created_at', false);
            break;
            case 'az':
                $this->setSortField('name', false);
            break;
            case 'za':
                $this->setSortField('name', true);
            break;
        }

        return $this;
    }

    /**
     * @param string
     * 
     * @return collection tasks
     */
    public function getTasks($filter)
    {
        switch ($filter)
        {
            CASE 'todays':
                return $this->getTodaysTasks(auth()->user()->userTasks());
            break;
            CASE 'thisweeks':
                return $this->getThisWeekTasks(auth()->user()->userTasks());
            break;
            CASE 'all':
                return $this->getAllTasks(auth()->user()->userTasks());
            break;
        }
    }

    /**
     * @param string
     * 
     * @return collection todos
     */
    public function getTodos($filter)
    {
        switch ($filter)
        {
            CASE 'todays':
                return $this->getTodaysTasks(auth()->user()->todos);
            break;
            CASE 'thisweeks':
                return $this->getThisWeekTasks(auth()->user()->todos);
            break;
            CASE 'all':
                return $this->getAllTasks(auth()->user()->todos);
            break;
        }
    }

    private function getTodaysTasks($tasks)
    {
        return $this->getTodaysPendingTask($tasks)
                ->when($this->_doSort, function($tasks){
                    return $tasks->sortBy($this->_sortField, SORT_NATURAL | SORT_FLAG_CASE, $this->_sortFieldBy);  //two flags for incase-sensitive ordering
                });
    }

    private function getThisWeekTasks($tasks)
    {
        return $this->getThisWeeksPendingTask($tasks)
                ->when($this->_doSort, function($tasks){
                    return $tasks->sortBy($this->_sortField, SORT_NATURAL | SORT_FLAG_CASE, $this->_sortFieldBy);  //two flags for incase-sensitive ordering
                });
      
    }

    private function getAllTasks($tasks)
    {
        return $tasks->when($this->_doSort, function($tasks){
            return $tasks->sortBy($this->_sortField, SORT_NATURAL | SORT_FLAG_CASE, $this->_sortFieldBy);  //two flags for incase-sensitive ordering
        });
    }
    
    public function getProjects($filter)
    {
        switch ($filter)
        {
            CASE 'todays':
                return $this->getTodaysProjects();
            break;
            CASE 'thisweeks':
                return $this->getThisWeekProjects();
            break;
            CASE 'all':
                return $this->getAllProjects();
            break;
        }
    }

    private function getTodaysProjects()
    {
        return auth()->user()->projects->filter(function($project){

            return $this->getTodaysPendingTask($project->tasks)->count() > 0 ? true : false;

        })
        ->when($this->_doSort, function($projects){
            return $projects->sortBy($this->_sortField, SORT_NATURAL | SORT_FLAG_CASE, $this->_sortFieldBy);  //two flags for incase-sensitive ordering
        });
    }

    private function getThisWeekProjects()
    {
        return auth()->user()->projects->filter(function($project){

            if ($project->tasks()->count() === 0) {
                return false;
            }

            return $this->getThisWeeksPendingTask($project->tasks)->count() > 0 ? true :false ;
            
        })
        ->when($this->_doSort, function($projects){
            return $projects->sortBy($this->_sortField, SORT_NATURAL | SORT_FLAG_CASE, $this->_sortFieldBy);  //two flags for incase-sensitive ordering
        });
    }

    private function getAllProjects()
    {
        return auth()->user()->projects->when($this->_doSort, function($projects){
            return $projects->sortBy($this->_sortField, SORT_NATURAL | SORT_FLAG_CASE, $this->_sortFieldBy);  //two flags for incase-sensitive ordering
        });
    }    

    private function setSortField($field, $by)
    {
        $this->_doSort = true;
        $this->_sortField = $field;
        $this->_sortFieldBy = $by;
    }
}
