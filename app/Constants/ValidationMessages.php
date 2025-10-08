<?php

declare(strict_types = 1);

namespace App\Constants;

final class ValidationMessages {
    public const BOOLEAN_INVALID = 'err.boolean.invalid';

    public const EMAIL_INVALID = 'validation.email';

    public const EXISTS_VIOLATION = 'validation.exists';

    public const INTEGER_INVALID = 'err.integer.invalid';

    public const INVALID_DATE = 'err.invalid_date';

    public const INVALID_ORG = 'err.invalid_org';

    public const ITEM_NOT_EXISTS = 'err.item.not_exists';

    public const MAINTENANCE_CATEGORY_NAME_REQUIRED = 'err.maintenance_category.name_required';

    public const MAINTENANCE_CATEGORY_NOT_FOUND = 'err.maintenance_category.not_found';

    public const MAINTENANCE_CONDITION_CATEGORY_REQUIRED = 'err.maintenance_condition.category_required';

    // Request-specific field validation
    public const MAINTENANCE_CONDITION_FIELD_REQUIRED = 'err.maintenance_condition.required';

    // ==================================================
    // SPECIFIC FIELD VALIDATION MESSAGES
    // ==================================================

    // Maintenance specific validation
    public const MAINTENANCE_CONDITION_ITEM_REQUIRED = 'err.maintenance_condition.item_required';

    public const MAINTENANCE_CONDITION_NEGATIVE_VALUES = 'err.maintenance_condition.negative_values';

    public const MAINTENANCE_CONDITION_NOT_FOUND = 'err.maintenance_condition.not_found';

    public const MAINTENANCE_CONDITION_REQUIRED = 'err.maintenance.condition_required';

    public const MAINTENANCE_CONDITION_UNIT_REQUIRED = 'err.maintenance_condition.unit_required';

    public const MAINTENANCE_COST_INVALID = 'err.maintenance.cost_invalid';

    public const MAINTENANCE_COST_NEGATIVE = 'err.maintenance.cost_negative';

    public const MAINTENANCE_DETAIL_VALUE_NUMERIC = 'err.maintenance_detail.value_numeric';

    public const MAINTENANCE_DETAIL_VALUE_REQUIRED = 'err.maintenance_detail.value_required';

    public const MAINTENANCE_FIELD_REQUIRED = 'err.maintenance.required';

    public const MAINTENANCE_ITEM_REQUIRED = 'err.maintenance.item_required';

    public const MAINTENANCE_NOT_FOUND = 'err.maintenance.not_found';

    public const MAINTENANCE_REMARKS_TOO_LONG = 'err.maintenance.remarks_too_long';

    public const MAINTENANCE_TRIGGER_VALUE_INVALID = 'err.maintenance.trigger_value_invalid';

    public const NUMERIC_INVALID = 'err.numeric.invalid';

    // ==================================================
    // ENTITY EXISTENCE VALIDATION
    // ==================================================
    public const ORG_REQUIRED = 'err.org_required';

    // ==================================================
    // GENERIC VALIDATION MESSAGES
    // ==================================================
    public const REQUIRED = 'validation.required';

    public const STATUS_NOT_EXISTS = 'err.status.not_exists';

    public const STRING_INVALID = 'err.string.invalid';

    public const STRING_TOO_LONG = 'err.string.too_long';

    public const SUPPLIER_NOT_EXISTS = 'err.supplier.not_exists';

    public const UNIQUE_VIOLATION = 'validation.unique';

    public const UNIT_NOT_EXISTS = 'err.unit.not_exists';

    public const USER_NOT_EXISTS = 'err.user.not_exists';
}
