<?php

namespace App\Constants;

class ErrorMessages
{
    // ==================================================
    // AUTHENTICATION & AUTHORIZATION
    // ==================================================
    public const UNAUTHORIZED = 'err.unauthorized';

    public const LOGIN_FAILED = 'err.login_failed';

    public const TOKEN_EXPIRED = 'err.token_expired';

    public const INVALID_CREDENTIALS = 'err.invalid_creds';

    public const FORBIDDEN = 'err.forbidden';

    public const CROSS_ORG_ACCESS = 'err.cross_org';

    public const INSUFFICIENT_PERMISSIONS = 'err.no_perms';

    public const SELF_DELETION_FORBIDDEN = 'err.no_self_delete';

    public const LICENSE_LIMIT_EXCEEDED = 'err.license_exceeded';

    public const SESSION_KEY_REQUIRED = 'err.session_key_required';

    public const ADMIN_ACCESS_DENIED = 'err.admin_access_denied';

    // ==================================================
    // GENERIC DATA & VALIDATION
    // ==================================================
    public const NOT_FOUND = 'err.not_found';

    public const ALREADY_EXISTS = 'err.exists';

    public const RESOURCE_IN_USE = 'err.in_use';

    public const VALIDATION_FAILED = 'err.validation';

    public const FIELD_REQUIRED = 'err.field.required';

    public const INVALID_QUERY_PARAMETER = 'err.invalid_param';

    public const INVALID_RELATION = 'err.invalid_rel';

    public const INVALID_ID = 'err.invalid_id';

    public const INVALID_DATA = 'err.invalid_data';

    public const EMPTY_DATA = 'err.empty_data';

    public const INVALID_DATE_FORMAT = 'err.invalid_date';

    public const INVALID_PUBLIC_ID_FORMAT = 'err.invalid_pub_id';

    public const INVALID_ENTITY_TYPE = 'err.invalid_entity';

    public const SERVER_ERROR = 'err.server';

    // Organization
    public const ORG_REQUIRED = 'err.org_required';

    public const INVALID_ORG = 'err.invalid_org';

    // ==================================================
    // ITEM & INVENTORY MANAGEMENT
    // ==================================================

    // General Item Errors
    public const ITEM_NOT_FOUND = 'err.item_not_found';

    public const ITEM_MOVE_FAILED = 'err.item_move_fail';

    public const ITEM_REQUIRED = 'err.item.required';

    public const ITEM_NOT_EXISTS = 'err.item.not_exists';

    public const ITEM_SERIAL_REQUIRED = 'err.item.serial_required';

    public const ITEM_CODE_EXISTS = 'err.item.code_exists';

    public const ITEM_SERIAL_EXISTS = 'err.item.serial_exists';

    // Item Movement Errors
    public const ITEM_ABSTRACT_NO_LOCATION = 'err.item.abstract_no_location';

    public const ITEM_SERIALIZED_QUANTITY_ONE = 'err.item.serialized_quantity_one';

    public const ITEM_QUANTITY_REQUIRED = 'err.item.quantity_required';

    public const ITEM_LOCATIONS_MUST_DIFFER = 'err.item.locations_must_differ';

    public const ITEM_NOT_IN_SOURCE_LOCATION = 'err.item.not_in_source_location';

    public const ITEM_INSUFFICIENT_QUANTITY = 'err.item.insufficient_quantity';

    public const ITEM_SERIALIZED_NEED_LOCATION = 'err.item.serialized_need_location';

    public const ITEM_STANDARD_NEED_EXPLICIT = 'err.item.standard_need_explicit';

    public const ITEM_MOVEMENT_QUANTITY_POSITIVE = 'err.item.movement_quantity_positive';

    public const ITEM_SERIALIZED_INITIAL_EXISTS = 'err.item.serialized_initial_exists';

    public const ITEM_ADJUSTMENT_QUANTITY_BELOW_ZERO = 'err.item.adjustment_quantity_below_zero';

    public const ITEM_ADJUSTMENT_STANDARD_ONLY = 'err.item.adjustment_standard_only';

    public const ITEM_ADJUSTMENT_LOCATION_REQUIRED = 'err.item.adjustment_location_required';

    // Item Integrity Errors
    public const ITEM_SERIALIZED_MULTIPLE_LOCATIONS = 'err.item.serialized_multiple_locations';

    // Supplier Errors
    public const SUPPLIER_REQUIRED = 'err.supplier.required';

    public const SUPPLIER_NOT_EXISTS = 'err.supplier.not_exists';

    public const SUPPLIER_NAME_REQUIRED = 'err.supplier.name_required';

    public const SUPPLIER_CODE_EXISTS = 'err.supplier.code_exists';

    // Quantity & Pricing Errors
    public const QUANTITY_REQUIRED = 'err.quantity.required';

    public const QUANTITY_NUMERIC = 'err.quantity.numeric';

    public const QUANTITY_MIN = 'err.quantity.min';

    public const QUANTITY_MAX_EXCEEDED = 'err.quantity.max_exceeded';

    public const QUANTITY_INSUFFICIENT = 'err.quantity.insufficient';

    public const NEGATIVE_PRICE = 'err.negative_price';

    public const NEGATIVE_LEAD_TIME = 'err.negative_lead';

    // Batch Errors
    public const BATCH_NUMBER_REQUIRED = 'err.batch.number_required';

    public const BATCH_NUMBER_UNIQUE = 'err.batch.number_unique';

    public const BATCH_NUMBER_EXISTS = 'err.batch.number_exists';

    public const BATCH_NEGATIVE_COST = 'err.batch.negative_cost';

    public const BATCH_INVALID_DATES = 'err.batch.invalid_dates';

    public const BATCH_ID_INVALID = 'err.batch.id_invalid';

    public const BATCH_NOT_EXISTS = 'err.batch.not_exists';

    public const BATCH_INACTIVE = 'err.batch.inactive';

    public const BATCH_EXPIRED = 'err.batch.expired';

    // ==================================================
    // LOCATION MANAGEMENT
    // ==================================================
    public const LOCATION_NAME_REQUIRED = 'err.location.name_required';

    public const LOCATION_CODE_REQUIRED = 'err.location.code_required';

    public const LOCATION_CODE_EXISTS = 'err.location.code_exists';

    public const LOCATION_PARENT_NOT_EXISTS = 'err.location.parent_not_exists';

    public const LOCATION_CIRCULAR_REFERENCE = 'err.location.circular_reference';

    // ==================================================
    // MAINTENANCE MANAGEMENT
    // ==================================================

    // General Maintenance Errors
    public const MAINTENANCE_NOT_FOUND = 'err.maintenance.not_found';

    public const MAINTENANCE_ALREADY_COMPLETED = 'err.maintenance.already_completed';

    public const MAINTENANCE_NOT_ACTIVE = 'err.maintenance.not_active';

    public const MAINTENANCE_ITEM_REQUIRED = 'err.maintenance.item_required';

    public const MAINTENANCE_CONDITION_REQUIRED = 'err.maintenance.condition_required';

    public const MAINTENANCE_CATEGORY_REQUIRED = 'err.maintenance.category_required';

    public const MAINTENANCE_DATE_INVALID = 'err.maintenance.date_invalid';

    public const MAINTENANCE_COST_NEGATIVE = 'err.maintenance.cost_negative';

    // Maintenance Condition Errors
    public const MAINTENANCE_CONDITION_NOT_FOUND = 'err.maintenance_condition.not_found';

    public const MAINTENANCE_CONDITION_ITEM_REQUIRED = 'err.maintenance_condition.item_required';

    public const MAINTENANCE_CONDITION_CATEGORY_REQUIRED = 'err.maintenance_condition.category_required';

    public const MAINTENANCE_CONDITION_UNIT_REQUIRED = 'err.maintenance_condition.unit_required';

    public const MAINTENANCE_CONDITION_INVALID_DATES = 'err.maintenance_condition.invalid_dates';

    public const MAINTENANCE_CONDITION_NEGATIVE_VALUES = 'err.maintenance_condition.negative_values';

    // Maintenance Category Errors
    public const MAINTENANCE_CATEGORY_NOT_FOUND = 'err.maintenance_category.not_found';

    public const MAINTENANCE_CATEGORY_NAME_REQUIRED = 'err.maintenance_category.name_required';

    public const MAINTENANCE_CATEGORY_NAME_EXISTS = 'err.maintenance_category.name_exists';

    // Maintenance Detail Errors
    public const MAINTENANCE_DETAIL_NOT_FOUND = 'err.maintenance_detail.not_found';

    public const MAINTENANCE_DETAIL_VALUE_REQUIRED = 'err.maintenance_detail.value_required';

    public const MAINTENANCE_DETAIL_VALUE_NUMERIC = 'err.maintenance_detail.value_numeric';

    // Unit of Measure Errors
    public const UNIT_OF_MEASURE_NOT_FOUND = 'err.unit_of_measure.not_found';

    public const UNIT_OF_MEASURE_NAME_REQUIRED = 'err.unit_of_measure.name_required';

    public const UNIT_OF_MEASURE_TYPE_REQUIRED = 'err.unit_of_measure.type_required';

    public const UNIT_OF_MEASURE_TYPE_INVALID = 'err.unit_of_measure.type_invalid';

    public const UNIT_OF_MEASURE_CODE_EXISTS = 'err.unit_of_measure.code_exists';

    public const UNIT_OF_MEASURE_ORG_REQUIRED = 'err.unit_of_measure.org_required';

    // ==================================================
    // CHECK-IN/OUT ERRORS
    // ==================================================
    public const CHECKINOUT_NOT_FOUND = 'err.checkinout.not_found';

    public const CHECKINOUT_NO_ACTIVE_CHECKOUT = 'err.checkinout.no_active_checkout';

    public const CHECKINOUT_ITEM_LOCATION_NOT_FOUND = 'err.checkinout.item_location_not_found';

    public const CHECKINOUT_QUANTITY_REQUIRED = 'err.checkinout.quantity_required';

    public const CHECKINOUT_QUANTITY_POSITIVE = 'err.checkinout.quantity_positive';

    public const CHECKINOUT_RETURN_DATE_FUTURE = 'err.checkinout.return_date_future';

    public const CHECKINOUT_CHECKIN_QUANTITY_REQUIRED = 'err.checkinout.checkin_quantity_required';

    public const CHECKINOUT_CHECKIN_QUANTITY_POSITIVE = 'err.checkinout.checkin_quantity_positive';

    // ==================================================
    // STATUS ERRORS
    // ==================================================
    public const STATUS_NAME_REQUIRED = 'err.status.name_required';

    public const STATUS_CODE_REQUIRED = 'err.status.code_required';

    public const STATUS_CODE_EXISTS = 'err.status.code_exists';

    public const STATUS_IN_USE = 'err.status.in_use';

    // ==================================================
    // ATTACHMENT & FILE ERRORS
    // ==================================================
    public const ATTACHMENT_NOT_FOUND = 'err.attachment.not_found';

    public const ATTACHMENT_FILE_REQUIRED = 'err.attachment.file_required';

    public const ATTACHMENT_FILE_INVALID = 'err.attachment.file_invalid';

    public const ATTACHMENT_FILE_TOO_LARGE = 'err.attachment.file_too_large';

    public const ATTACHMENT_FILE_TYPE_INVALID = 'err.attachment.file_type_invalid';

    public const ATTACHMENT_ENTITY_REQUIRED = 'err.attachment.entity_required';

    public const ATTACHMENT_ENTITY_INVALID = 'err.attachment.entity_invalid';

    public const ATTACHMENT_ORG_REQUIRED = 'err.attachment.org_required';
}
