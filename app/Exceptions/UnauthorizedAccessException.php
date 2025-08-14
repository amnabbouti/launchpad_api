<?php

declare(strict_types = 1);

namespace App\Exceptions;

use App\Constants\ErrorMessages;
use App\Constants\HttpStatus;
use Exception;

final class UnauthorizedAccessException extends Exception {
    public function __construct(string $message = '') {
        parent::__construct($message ?: __(ErrorMessages::FORBIDDEN), HttpStatus::HTTP_FORBIDDEN);
    }
}
