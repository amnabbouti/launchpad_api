<?php

namespace App\Constants;

/**
 * Success Messages Constants
 *
 * This class contains translation keys for success messages that are NOT handled
 * by MessageGeneratorService. The MessageGeneratorService handles standard CRUD
 * operations (created, updated, deleted, retrieved, list).
 *
 * Only include success messages here that are:
 * - Special operations not covered by CRUD
 * - Custom business logic operations
 * - Non-standard naming patterns
 */
class SuccessMessages
{
    // ==================================================
    // AUTHENTICATION & AUTHORIZATION
    // ==================================================
    public const LOGIN_SUCCESS = 'succ.auth.login';

    public const LOGOUT_SUCCESS = 'succ.auth.logout';

    public const ACCESS_GRANTED = 'succ.auth.access_granted';

    public const ACTION_FORBIDDEN = 'succ.auth.action_forbidden';

    public const ACTION_ALLOWED = 'succ.auth.action_allowed';

    // ==================================================
    // SPECIAL ITEM OPERATIONS (not standard CRUD)
    // ==================================================
    public const ITEM_MOVED = 'succ.item.moved';

    public const ITEM_INITIAL_PLACEMENT = 'succ.item.initial_placement';

    public const ITEM_QUANTITY_ADJUSTED = 'succ.item.quantity_adjusted';

    public const ITEM_SCANNED = 'succ.item.scanned';

    public const ITEM_MAINTENANCE_IN = 'succ.item.maintenance.in';

    public const ITEM_MAINTENANCE_OUT = 'succ.item.maintenance.out';

    public const ITEM_CHECKOUT = 'succ.item.checkout';

    public const ITEM_CHECKIN = 'succ.item.checkin';

    // ==================================================
    // SPECIAL MAINTENANCE OPERATIONS (not standard CRUD)
    // ==================================================
    public const MAINTENANCE_COMPLETED = 'succ.maintenance.completed';

    public const MAINTENANCE_STARTED = 'succ.maintenance.started';

    // ==================================================
    // ORGANIZATION (non-standard naming pattern)
    // ==================================================
    public const ORG_CREATED = 'succ.org.created';

    public const ORG_UPDATED = 'succ.org.updated';

    // ==================================================
    // CONFIGURATION & OPTIONS
    // ==================================================
    public const OPTIONS_RETRIEVED = 'succ.config.options_retrieved';

    // ==================================================
    // GENERIC FALLBACK MESSAGES
    // ==================================================
    // Note: These should rarely be used. Prefer MessageGeneratorService for CRUD operations.
    public const RESOURCE_CREATED = 'succ.resource.created';

    public const RESOURCE_RETRIEVED = 'succ.resource.retrieved';

    public const RESOURCE_UPDATED = 'succ.resource.updated';

    public const RESOURCE_DELETED = 'succ.resource.deleted';

    public const RESOURCES_RETRIEVED = 'succ.resource.list';
}
