<?php

namespace App\Exceptions;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use Exception;

class UnauthorizedAccessException extends Exception
{
    public function __construct(string $message = '')
    {
        parent::__construct($message ?: ErrorMessages::FORBIDDEN, HttpStatus::HTTP_FORBIDDEN);
    }
}
