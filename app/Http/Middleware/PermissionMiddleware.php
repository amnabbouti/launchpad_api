<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Constants\Permissions;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    private const CACHE_PREFIX = 'permission_decision:';
    private const CACHE_TTL = 300;
    
    /**
     * Handle incoming request with automatic permission detection.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }
        
        if ($user->isSuperAdmin()) {
            return $next($request);
        }
        
        $permission = $this->getPermissionForRoute($request);
        
        if (!$permission) {
            return $next($request);
        }
        
        // Check cached permission decision first
        $cacheKey = $this->getCacheKey($user, $permission, $request);
        $cachedDecision = Cache::get($cacheKey);
        
        if ($cachedDecision !== null) {
            if ($cachedDecision['allowed']) {
                return $next($request);
            } else {
                return response()->json($cachedDecision['response'], $cachedDecision['status']);
            }
        }
        
        $authResult = $this->performAuthorizationCheck($request, $user, $permission);
        
        Cache::put($cacheKey, $authResult, self::CACHE_TTL);
        
        if (!$authResult['allowed']) {
            return response()->json($authResult['response'], $authResult['status']);
        }
        
        return $next($request);
    }
    
    private function performAuthorizationCheck(Request $request, $user, string $permission): array
    {
        if ($user->role && $user->role->forbids($permission)) {
            return [
                'allowed' => false,
                'status' => 403,
                'reason' => 'role_forbidden',
                'response' => [
                    'message' => 'This action is forbidden for your role.',
                    'error' => 'forbidden_action',
                    'permission' => $permission,
                    'role' => $user->role->slug
                ]
            ];
        }
        
        if (!$this->canAccessResource($request, $user)) {
            return [
                'allowed' => false,
                'status' => 403,
                'reason' => 'organization_scope',
                'response' => [
                    'message' => 'You can only access resources within your organization.',
                    'error' => 'organization_scope',
                    'permission' => $permission
                ]
            ];
        }
        
        if ($this->isPermissionModificationAttempt($request, $user, $permission)) {
            $violation = $this->validatePermissionModification($request, $user);
            if ($violation) {
                return [
                    'allowed' => false,
                    'status' => 403,
                    'reason' => 'unauthorized_permission_modification',
                    'response' => [
                        'message' => 'You cannot modify permissions you do not have access to.',
                        'error' => 'unauthorized_permission_modification',
                        'violation' => $violation
                    ]
                ];
            }
        }
        
        return [
            'allowed' => true,
            'status' => 200,
            'reason' => 'authorized',
            'response' => null
        ];
    }
    
    private function getPermissionForRoute(Request $request): ?string
    {
        $method = $request->getMethod();
        $path = $request->path();

        $segments = explode('/', $path);
        $resource = null;

        foreach ($segments as $index => $segment) {
            if ($segment === 'v1' && isset($segments[$index + 1])) {
                $resource = $segments[$index + 1];
                break;
            }
        }

        if (!$resource) {
            return null;
        }
        $permission = $this->mapResourceToPermission($resource, $method, $path);
        
        return $permission;
    }

    /**
     * Map resource and method to specific permission.
     */
    private function mapResourceToPermission(string $resource, string $method, string $path): ?string
    {
        if (str_contains($path, 'admin/')) {
            return $this->getAdminPermission($path, $method);
        }

        if ($resource === 'maintenances') {
            if (str_contains($path, '/categories')) {
                $action = $this->getActionFromMethod($method);
                return $action ? "maintenancecategories.{$action}" : null;
            }
            if (str_contains($path, '/conditions')) {
                $action = $this->getActionFromMethod($method);
                return $action ? "maintenanceconditions.{$action}" : null;
            }
            $action = $this->getActionFromMethod($method);
            return $action ? "maintenances.{$action}" : null;
        }

        if ($resource === 'checks') {
            $action = $this->getActionFromMethod($method);
            return $action ? "checkinouts.{$action}" : null;
        }

        if (in_array($resource, ['movements', 'events'])) {
            $action = $this->getActionFromMethod($method);
            return $action ? "{$resource}.{$action}" : null;
        }

        $action = $this->getActionFromMethod($method);
        return $action ? "{$resource}.{$action}" : null;
    }

    private function getAdminPermission(string $path, string $method): ?string
    {
        if (str_contains($path, 'api-keys') || str_contains($path, 'security')) {
            return 'organizations.billing';
        }
        
        return null;
    }
    
    private function getActionFromMethod(string $method): ?string
    {
        return match ($method) {
            'GET' => 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => null,
        };
    }


    
    private function canAccessResource(Request $request, $user): bool
    {
        $path = $request->path();
        $method = $request->getMethod();
        
         if (str_contains($path, '/users/') && preg_match('/\/users\/(\d+)/', $path, $matches)) {
             $targetUserId = (int) $matches[1];
             $targetUser = \App\Models\User::find($targetUserId);
             
             if (!$targetUser || $targetUser->org_id !== $user->org_id) {
                 return false;
             }
             
             if ($method === 'PATCH' || $method === 'PUT') {
                 if ($targetUserId !== $user->id && !$user->role->allows('users.update.others')) {
                     return false;
                 }
             }
         }
        
        // For organization routes, ensure users can only access their own organization
         if (str_contains($path, '/organizations/') && preg_match('/\/organizations\/(\d+)/', $path, $matches)) {
             $targetOrgId = (int) $matches[1];
             
             if ($targetOrgId !== $user->org_id) {
                 return false;
             }
         }
        
         if (str_contains($path, '/roles/') && preg_match('/\/roles\/(\d+)/', $path, $matches)) {
             $targetRoleId = (int) $matches[1];
             $targetRole = \App\Models\Role::find($targetRoleId);
             
             if (!$targetRole) {
                 return false;
             }
             
             // System roles (org_id = null) are accessible to all users
             // Organization-specific roles must match user's organization
             if ($targetRole->org_id !== null && $targetRole->org_id !== $user->org_id) {
                 return false;
             }
         }
        
         if (str_contains($path, '/licenses/') && preg_match('/\/licenses\/(\d+)/', $path, $matches)) {
             $targetLicenseId = (int) $matches[1];
             $targetLicense = \App\Models\License::find($targetLicenseId);
             
             if (!$targetLicense || $targetLicense->org_id !== $user->org_id) {
                 return false;
             }
         }
        
        return true;
    }
    
    private function isPermissionModificationAttempt(Request $request, $user, string $permission): bool
    {
        return in_array($permission, ['roles.create', 'roles.update']) && 
               ($request->has('forbidden') || $request->has('permissions'));
    }
    
    private function validatePermissionModification(Request $request, $user): ?array
    {
        $forbiddenPermissions = $request->input('forbidden', []);
        $permissions = $request->input('permissions', []);
        
        // Combine all permissions being modified
        $allPermissions = array_merge($forbiddenPermissions, $permissions);
        
        if (empty($allPermissions)) {
            return null;
        }
        
        // Validate that all permissions are valid first
        $validPermissions = Permissions::getAvailablePermissionKeys();
        $invalidPermissions = array_diff($allPermissions, $validPermissions);
        if (!empty($invalidPermissions)) {
            return [
                'type' => 'invalid_permissions',
                'permissions' => $invalidPermissions,
                'message' => 'Invalid permissions: ' . implode(', ', $invalidPermissions)
            ];
        }
        
        // For system roles, apply role-specific restrictions
        if ($user->role && $user->role->is_system) {
            $roleSlug = $user->role->slug;
            
            // Super admins can modify any permissions
            if ($roleSlug === 'super_admin') {
                return null;
            }
            
            // Admins have same permissions as super_admin within organization scope
            if ($roleSlug === 'admin') {
                return null;
            }
            
            // Managers have specific restrictions
            if ($roleSlug === 'manager') {
                $managerForbiddenPermissions = Permissions::getRequiredForbiddenKeys();
                
                // Managers cannot grant permissions they are forbidden from having
                $unauthorizedGrants = array_intersect($permissions, $managerForbiddenPermissions);
                if (!empty($unauthorizedGrants)) {
                    return [
                        'type' => 'unauthorized_grant',
                        'permissions' => $unauthorizedGrants,
                        'message' => 'Managers cannot grant these permissions: ' . implode(', ', $unauthorizedGrants)
                    ];
                }
                
                // Managers must always include required forbidden permissions
                $requiredForbidden = Permissions::getRequiredForbiddenKeys();
                $missingRequired = array_diff($requiredForbidden, $forbiddenPermissions);
                if (!empty($missingRequired)) {
                    return [
                        'type' => 'missing_required_forbidden',
                        'permissions' => $missingRequired,
                        'message' => 'These permissions must always be forbidden: ' . implode(', ', $missingRequired)
                    ];
                }
            }
            
            // Employees cannot modify permissions at all
            if ($roleSlug === 'employee') {
                return [
                    'type' => 'insufficient_privileges',
                    'permissions' => [],
                    'message' => 'Employees cannot modify role permissions'
                ];
            }
        }
        
        return null;
    }
    
    private function getCacheKey($user, string $permission, Request $request): string
    {
        $factors = [
            $user->id,
            $user->role?->id ?? 'no_role',
            $permission,
            $request->getMethod(),
            md5($request->path())
        ];
        
        return self::CACHE_PREFIX . md5(implode('|', $factors));
    }
    

}