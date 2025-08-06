<?php

declare(strict_types=1);

namespace App\Services;

class MessageGeneratorService
{
    /**
     * Generic method to generate any message.
     */
    public static function generate(string $translationKey, array $parameters = []): string
    {
        return __($translationKey, $parameters);
    }

    /**
     * Generate a list message.
     */
    public static function generateListMessage(string $resourceType, int $count): string
    {
        if ($count === 0) {
            $key = "succ.$resourceType.list.empty";

            return self::generate($key);
        }

        $key = "succ.$resourceType.list";

        return self::generate($key, ['count' => $count]);
    }

    /**
     * Generate a contextual list message.
     */
    public static function generateContextualListMessage(string $resourceType, int $count, array $context): string
    {
        // For now, return the basic list message
        // Can be extended for specific contextual messages
        return self::generateListMessage($resourceType, $count);
    }

    /**
     * Generate a creation message.
     */
    public static function generateCreateMessage(string $resourceType, array $data = []): string
    {
        $key = "succ.$resourceType.created";
        $params = self::extractNameFromData($data);

        return self::generate($key, $params);
    }

    /**
     * Generate an update message.
     */
    public static function generateUpdateMessage(string $resourceType, array $data = []): string
    {
        $key = "succ.$resourceType.updated";
        $params = self::extractNameFromData($data);

        return self::generate($key, $params);
    }

    /**
     * Generate a delete message.
     */
    public static function generateDeleteMessage(string $resourceType, array $data = []): string
    {
        $key = "succ.$resourceType.deleted";
        $params = self::extractNameFromData($data);

        return self::generate($key, $params);
    }

    /**
     * Generate a show/retrieved message.
     */
    public static function generateShowMessage(string $resourceType, array $data = []): string
    {
        $key = "succ.$resourceType.retrieved";
        $params = self::extractNameFromData($data);

        return self::generate($key, $params);
    }

    /**
     * Extract name/identifier from data for personalized messages.
     */
    private static function extractNameFromData(array $data): array
    {
        $params = [];

        // Common name fields to check
        $nameFields = ['name', 'batch_number', 'code', 'title'];

        foreach ($nameFields as $field) {
            if (! empty($data[$field])) {
                $params[$field] = $data[$field];
                $params['name'] = $data[$field];
                break;
            }
        }

        return $params;
    }
}
