<?php

namespace App\Constants;

class ErrorMessages
{
    // Authentication
    public const UNAUTHORIZED = 'err.unauthorized';
    public const LOGIN_FAILED = 'err.login_failed';
    public const TOKEN_EXPIRED = 'err.token_expired';
    public const NOT_FOUND = 'err.not_found';
    public const INVALID_CREDENTIALS = 'err.invalid_creds';

    // Authorization
    public const FORBIDDEN = 'err.forbidden';
    public const CROSS_ORG_ACCESS = 'err.cross_org';
    public const INSUFFICIENT_PERMISSIONS = 'err.no_perms';
    public const SELF_DELETION_FORBIDDEN = 'err.no_self_delete';
    public const ALREADY_EXISTS = 'err.exists';
    public const RESOURCE_IN_USE = 'err.in_use';
    public const ORG_REQUIRED = 'err.org_required';
    public const INVALID_ORG = 'err.invalid_org';

    // Validation
    public const VALIDATION_FAILED = 'err.validation';
    public const INVALID_QUERY_PARAMETER = 'err.invalid_param';
    public const INVALID_RELATION = 'err.invalid_rel';
    public const INVALID_ID = 'err.invalid_id';

    // Creation/Update
    public const INVALID_DATA = 'err.invalid_data';
    public const EMPTY_DATA = 'err.empty_data';
    public const INVALID_DATE_FORMAT = 'err.invalid_date';
    public const NEGATIVE_PRICE = 'err.negative_price';
    public const NEGATIVE_LEAD_TIME = 'err.negative_lead';
    public const INVALID_PUBLIC_ID_FORMAT = 'err.invalid_pub_id';
    public const INVALID_ENTITY_TYPE = 'err.invalid_entity';
    public const ITEM_NOT_FOUND = 'err.item_not_found';
    public const ITEM_MOVE_FAILED = 'err.item_move_fail';

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
    public const LICENSE_LIMIT_EXCEEDED = 'err.license_exceeded';
}
