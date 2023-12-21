<?php

namespace App\Traits;

use DateTime;
use Illuminate\Support\Arr;

/**
 * @method markAsComplete()
 * @method markAsReopen()
 * @method markAsStart()
 */
trait MarkItem
{
    /**
     * mark model as completed
     * 
     * @return void
     */
    public function markAsComplete()
    {
        if ($this->end_date == null) {
            $this->update([
                "end_date" =>  new DateTime(),
                "status" => "completed"
            ]);
        } else {
            // throw new \Exception(class_basename($this) . ' Is Already Completed.');
        }

        return $this;
    }

    /**
     * mark model as re-open
     * 
     * @return void
     */
    public function markAsReopen()
    {
        if ($this->end_date != null) {
            $this->update([
                "end_date" =>  null,
                "status" => "re-open"
            ]);
        } else {
            // throw new \Exception(class_basename($this) . ' Is Still Open.');
        }

        return $this;
    }

    /**
     * mark model as started or continue without raising exception 
     * 
     * @return void
     */
    public function markAsStart()
    {
        if ($this->start_date == null) {
            $this->update([
                "start_date" =>   new DateTime(),
                "end_date" =>  null,
                "status" => "in progress"
            ]);
        }
    }

    /**
     * mark model as important 
     * 
     * @return void
     */
    public function markAsImportant()
    {
        $markedImportantBy = Arr::wrap($this->marked_important_by);

        if (!in_array(auth()->id(), $markedImportantBy)) {
            $this->push('marked_important_by', auth()->id());
        }
    }

    /**
     * mark model as important 
     * 
     * @return void
     */
    public function markAsUnImportant()
    {
        $markedImportantBy = Arr::wrap($this->marked_important_by);

        if (in_array(auth()->id(), $markedImportantBy)) {
            $this->pull('marked_important_by', auth()->id());
        }
    }
}
