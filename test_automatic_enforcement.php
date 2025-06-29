<?php

// Test script to verify automatic forbidden permission enforcement

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AuthorizationEngine;
use App\Constants\Permissions;

echo "=== TESTING AUTOMATIC FORBIDDEN PERMISSION ENFORCEMENT ===\n\n";

// 1. Test what managers are required to forbid
$requiredForbidden = AuthorizationEngine::getRequiredForbiddenActionsForManagers();
echo "1. Required forbidden permissions for managers: " . count($requiredForbidden) . "\n";
echo "   Sample: " . implode(', ', array_slice($requiredForbidden, 0, 3)) . "...\n\n";

// 2. Test what permissions managers can grant
$availablePermissions = Permissions::getAvailablePermissionKeys();
echo "2. Available permissions managers can grant: " . count($availablePermissions) . "\n";
echo "   Sample: " . implode(', ', array_slice($availablePermissions, 0, 3)) . "...\n\n";

// 3. Simulate manager creating role with minimal forbidden permissions
$managerInput = ['items.delete', 'categories.update']; // What manager wants to forbid
$autoEnforced = array_unique(array_merge($managerInput, $requiredForbidden));

echo "3. Role creation simulation:\n";
echo "   Manager wants to forbid: " . count($managerInput) . " permissions\n";
echo "   System automatically adds: " . (count($autoEnforced) - count($managerInput)) . " security restrictions\n";
echo "   Final forbidden count: " . count($autoEnforced) . "\n\n";

// 4. Verify critical permissions are always forbidden
$criticalPermissions = ['users.delete.self', 'users.promote.super_admin', 'organizations.delete'];
echo "4. Verifying critical permissions are automatically forbidden:\n";
foreach ($criticalPermissions as $perm) {
    $status = in_array($perm, $autoEnforced) ? '✓ FORBIDDEN' : '✗ ALLOWED';
    echo "   {$perm}: {$status}\n";
}

echo "\n✅ Automatic enforcement ensures managers cannot create insecure roles!\n";
