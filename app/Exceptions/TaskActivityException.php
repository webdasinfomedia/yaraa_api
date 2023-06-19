<?php

namespace App\Exceptions;

use Exception;

class TaskActivityException extends Exception
{
    public function errorMessage()
    {
        return $this->getMessage();
    }
}
