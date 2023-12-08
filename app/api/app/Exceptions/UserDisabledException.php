<?php

namespace App\Exceptions;

use Exception;

class UserDisabledException extends Exception
{
    public function errorMessage()
    {
        return $this->getMessage();
    }
}
