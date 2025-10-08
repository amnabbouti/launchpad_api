<?php

declare(strict_types = 1);

namespace App\Exceptions;

use App\Constants\ErrorMessages;
use Exception;

final class ResourceNotFoundException extends Exception {
    public function __construct(string $message = '') {
        parent::__construct($message ?: ErrorMessages::NOT_FOUND);
    }
}
