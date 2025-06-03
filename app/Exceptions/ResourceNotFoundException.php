<?php

namespace App\Exceptions;

use App\Constants\ErrorMessages;
use Exception;

class ResourceNotFoundException extends Exception
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: ErrorMessages::NOT_FOUND);
    }
}
