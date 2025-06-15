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
    public const INVALID_DATE_FORMAT = 'Invalid date format. Use YYYY-MM-DD.';
    public const NEGATIVE_PRICE = 'Price cannot be negative.';
    public const NEGATIVE_LEAD_TIME = 'Lead time cannot be negative.';
    public const INVALID_PUBLIC_ID_FORMAT = 'Invalid public ID format';
    public const INVALID_ENTITY_TYPE = 'Invalid entity type';
    public const ITEM_NOT_FOUND = 'Item not found';
    public const ITEM_MOVE_FAILED = 'Failed to move item';

    // Stock
    public const ITEM_REQUIRED = 'An item must be selected';
    public const ITEM_NOT_EXISTS = 'The selected item does not exist';
    public const SUPPLIER_REQUIRED = 'A supplier must be selected';
    public const SUPPLIER_NOT_EXISTS = 'The selected supplier does not exist';
    public const QUANTITY_REQUIRED = 'The quantity is required';
    public const QUANTITY_NUMERIC = 'The quantity must be a number';
    public const QUANTITY_MIN = 'The quantity must be greater than 0';
    public const BATCH_NUMBER_REQUIRED = 'The batch number is required for stock tracking';
    public const BATCH_NUMBER_UNIQUE = 'This batch number already exists in your organization';
    public const RECEIVED_DATE_REQUIRED = 'The received date is required';
    public const RECEIVED_DATE_INVALID = 'The received date must be a valid date';
    public const EXPIRY_DATE_INVALID = 'The expiry date must be a valid date';
    public const EXPIRY_DATE_AFTER = 'The expiry date must be after the received date';
    public const UNIT_COST_REQUIRED = 'The unit cost is required';
    public const UNIT_COST_NUMERIC = 'The unit cost must be a number';
    public const UNIT_COST_MIN = 'The unit cost cannot be negative';
    public const ACTIVE_STATUS_BOOLEAN = 'The active status must be true or false';
    public const ORG_ID_REQUIRED = 'Organization ID is required';
    public const ORG_NOT_EXISTS = 'The selected organization does not exist';
    public const NO_RESOURCES_FOUND = 'No resources found';
    public const NO_RESOURCES_AVAILABLE = 'No resources available';
    public const SERVER_ERROR = 'An unexpected error occurred';
}
