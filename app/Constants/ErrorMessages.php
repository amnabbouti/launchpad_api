<?php

namespace App\Constants;

class ErrorMessages
{
    // Authentication
    public const UNAUTHORIZED = 'Unauthorized';

    public const LOGIN_FAILED = 'Invalid credentials';

    public const TOKEN_EXPIRED = 'Token has expired';

    public const NOT_FOUND = 'Resource not found';

    // Authorization
    public const FORBIDDEN = 'Trying to access other organization resources is not allowed, this action has been logged for security purposes';

    public const CROSS_ORG_ACCESS = 'Access denied: You do not have permission to access resources from other organizations';

    public const INSUFFICIENT_PERMISSIONS = 'You do not have sufficient permissions to perform this action';

    public const SELF_DELETION_FORBIDDEN = 'You cannot delete your own account please contact us for assistance';

    public const ALREADY_EXISTS = 'Resource already exists';

    public const RESOURCE_IN_USE = 'Cannot delete resource as it is currently in use';

    // Organization
    public const ORG_REQUIRED = 'User must belong to an organization';

    public const INVALID_ORG = 'Invalid organization';

    // Validation
    public const VALIDATION_FAILED = 'Validation failed';

    public const INVALID_QUERY_PARAMETER = 'Unknown query parameter';

    public const INVALID_RELATION = 'Invalid relationship parameter';

    public const INVALID_ID = 'invalid ID provided, ID must be a positive integer';

    // Creation/Update
    public const INVALID_DATA = 'Invalid data provided';

    public const EMPTY_DATA = 'Data cannot be empty';

    // Generic
    public const SERVER_ERROR = 'An unexpected error occurred';
}
