<?php

declare(strict_types = 1);

namespace App\Constants;

final class AppConstants {
    public const ADDRESS_MAX_LENGTH = 500;

    public const API_RATE_LIMIT_MINUTES = 60;

    // ==================================================
    // API CONFIGURATION
    // ==================================================
    public const API_RATE_LIMIT_REQUESTS = 1000;

    public const API_TIMEOUT_SECONDS = 30;

    public const API_VERSION = 'v1';

    public const AUDIT_TRAIL_RETENTION_DAYS = 365;

    // ==================================================
    // DATA RETENTION POLICIES
    // ==================================================
    public const BACKUP_RETENTION_DAYS = 30;

    public const DEFAULT_CACHE_TTL = 3600; // 1 hour

    // ==================================================
    // SYSTEM CONFIGURATION
    // ==================================================
    public const DEFAULT_PAGE_SIZE = 15;

    public const DESCRIPTION_MAX_LENGTH = 1000;

    // ==================================================
    // FIELD LENGTH LIMITS (for validation - commonly reused)
    // ==================================================
    public const EMAIL_MAX_LENGTH = 255;

    public const ITEM_ABSTRACT_TYPE = 'abstract';

    // ==================================================
    // BUSINESS RULES - ITEM MANAGEMENT
    // ==================================================
    public const ITEM_DEFAULT_QUANTITY = 1;

    public const ITEM_DEFAULT_TYPE = 'standard';

    public const ITEM_MAX_PRICE = 999999.99;

    public const ITEM_MAX_QUANTITY = 999999;

    public const ITEM_SERIALIZED_TYPE = 'serialized';

    // ==================================================
    // BUSINESS RULES - LOCATION MANAGEMENT
    // ==================================================
    public const LOCATION_MAX_DEPTH = 10; // Maximum nesting level

    public const LOG_RETENTION_DAYS = 90;

    public const MAINTENANCE_COMPLETED_STATUS = 'completed';

    // ==================================================
    // BUSINESS RULES - MAINTENANCE SYSTEM
    // ==================================================
    public const MAINTENANCE_DEFAULT_REMINDER_DAYS = 7;

    public const MAINTENANCE_DEFAULT_STATUS = 'pending';

    public const MAINTENANCE_MAX_COST = 999999.99;

    public const MAX_PAGE_SIZE = 100;

    public const MAX_UPLOAD_SIZE = 10485760; // 10MB

    public const NAME_MAX_LENGTH = 255;

    // ==================================================
    // NOTIFICATION CONFIGURATION
    // ==================================================
    public const NOTIFICATION_EMAIL_QUEUE = 'emails';

    public const NOTIFICATION_RETRY_ATTEMPTS = 3;

    public const NOTIFICATION_RETRY_DELAY = 300; // 5 minutes

    public const NOTIFICATION_SMS_QUEUE = 'sms';

    public const PASSWORD_MIN_LENGTH = 8;

    public const PHONE_MAX_LENGTH = 20;

    public const POSTAL_CODE_MAX_LENGTH = 20;

    public const REMARKS_MAX_LENGTH = 1000;

    public const SUPPORTED_ATTACHMENT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];

    public const SUPPORTED_ATTACHMENT_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
    ];

    public const SUPPORTED_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    public const SUPPORTED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public const THREAT_AUTH_FAILURES_CRITICAL_THRESHOLD = 100;

    public const THREAT_FAILURE_RATE_HIGH_THRESHOLD = 50;

    // ==================================================
    // SECURITY THRESHOLDS (configurable business rules)
    // ==================================================
    public const THREAT_FAILURE_RATE_MEDIUM_THRESHOLD = 30;

    public const THREAT_SCORE_HIGH_THRESHOLD = 60;

    public const THREAT_TIME_RANGE_MAX_HOURS = 168; // 1 week
}
