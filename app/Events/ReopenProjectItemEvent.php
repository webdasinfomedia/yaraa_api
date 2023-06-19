<?php

namespace App\Events;

class ReopenProjectItemEvent extends Event
{
    public $projectItem;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($projectItem)
    {
        $this->projectItem = $projectItem;
    }
}
