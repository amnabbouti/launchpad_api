<?php

namespace App\Constants;

class SuccessMessages
{
    // Authentication
    public const LOGIN_SUCCESS = 'Login successful';
    public const LOGOUT_SUCCESS = 'Logout successful';

    // Authorization
    public const ACCESS_GRANTED = 'Access granted';

    // Resources
    public const RESOURCE_CREATED = 'Resource created successfully';
    public const RESOURCE_RETRIEVED = 'Resource retrieved successfully';
    public const RESOURCE_UPDATED = 'Resource updated successfully';
    public const RESOURCE_DELETED = 'Resource deleted successfully';
    public const RESOURCES_RETRIEVED = 'Resources retrieved successfully';

    // Organization
    public const ORG_CREATED = 'Organization created successfully';
    public const ORG_UPDATED = 'Organization updated successfully';

    // Items
    public const ITEM_MOVED = 'Item moved successfully';
    public const ITEM_SCANNED = 'Item scanned successfully';

    // Roles & Permissions
    public const ACTION_FORBIDDEN = 'Action forbidden successfully';
    public const ACTION_ALLOWED = 'Action allowed successfully';

    // Options & Config
    public const OPTIONS_RETRIEVED = 'Options retrieved successfully';
}
