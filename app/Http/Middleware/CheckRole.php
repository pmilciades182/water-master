<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Role;

/**
 * CheckRole Middleware for Water Utility RBAC System
 * 
 * This middleware enforces role-based access control with multi-tenant security.
 * It supports:
 * - Dynamic role checking with flexible parameter handling
 * - Multi-tenant security with company scoping
 * - Performance optimization through caching
 * - Comprehensive logging for security audit trails
 * - Flexible role parameter formats (strings, arrays, pipe-separated)
 * 
 * Usage Examples:
 * - Route::middleware('role:admin')->group(function () {...});
 * - Route::middleware('role:manager,technician')->get(...);
 * - Route::middleware('role:admin|manager')->post(...);
 * 
 * @package App\Http\Middleware
 * @author Water Management System
 * @version 1.0.0
 */
class CheckRole
{
    /**
     * Cache TTL for role checks in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;

    /**
     * Cache key prefix for role checks
     */
    private const CACHE_PREFIX = 'rbac_role_check';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if user is authenticated
        if (\!Auth::check()) {
            return $this->unauthorizedResponse($request, 'Authentication required');
        }

        /** @var User $user */
        $user = Auth::user();

        // Validate user has active status
        if (\!$user->is_active) {
            Log::warning('Inactive user attempted role-based access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return $this->unauthorizedResponse($request, 'Account is inactive');
        }

        // Validate user has company association
        if (\!$user->company_id) {
            Log::error('User without company attempted role-based access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
            
            return $this->unauthorizedResponse($request, 'Invalid company association');
        }

        // Parse roles from middleware parameters
        $requiredRoles = $this->parseRoles($roles);
        
        if (empty($requiredRoles)) {
            Log::warning('Role middleware called without roles', [
                'user_id' => $user->id,
                'route' => $request->route()?->getName(),
                'raw_roles' => $roles,
            ]);
            
            return $next($request);
        }

        // Check if user has any of the required roles
        $hasRole = $this->checkUserRoles($user, $requiredRoles);

        if (\!$hasRole) {
            // Log role denial for security audit
            Log::warning('Role access denied', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'required_roles' => $requiredRoles,
                'user_roles' => $user->getRoleNames(),
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);

            return $this->forbiddenResponse($request, $requiredRoles);
        }

        // Log successful role check for system-level operations
        if ($this->isSystemLevelOperation($requiredRoles)) {
            Log::info('System-level role operation authorized', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'roles' => $requiredRoles,
                'user_roles' => $user->getRoleNames(),
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        return $next($request);
    }

    /**
     * Parse roles from middleware parameters.
     * Supports multiple formats:
     * - Single role: 'admin'
     * - Multiple parameters: 'admin', 'manager'
     * - Comma-separated: 'admin,manager'
     * - Pipe-separated: 'admin|manager'
     * - Mixed formats
     *
     * @param  array  $roles
     * @return array
     */
    private function parseRoles(array $roles): array
    {
        $parsed = [];

        foreach ($roles as $role) {
            // Handle comma-separated roles
            if (str_contains($role, ',')) {
                $commaSeparated = array_map('trim', explode(',', $role));
                $parsed = array_merge($parsed, $commaSeparated);
                continue;
            }

            // Handle pipe-separated roles (OR logic)
            if (str_contains($role, '|')) {
                $pipeSeparated = array_map('trim', explode('|', $role));
                $parsed = array_merge($parsed, $pipeSeparated);
                continue;
            }

            // Single role
            $parsed[] = trim($role);
        }

        // Remove empty values and duplicates
        return array_unique(array_filter($parsed));
    }

    /**
     * Check if user has any of the required roles with caching and company scoping.
     *
     * @param  User  $user
     * @param  array  $requiredRoles
     * @return bool
     */
    private function checkUserRoles(User $user, array $requiredRoles): bool
    {
        // Generate cache key for this role check
        $cacheKey = $this->generateCacheKey($user->id, $user->company_id, $requiredRoles);

        // Try to get result from cache first
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult \!== null) {
            return $cachedResult;
        }

        // Super admin always has access (unless specifically restricted)
        if (config('rbac.super_admin_bypass', true) && $user->isSuperAdmin()) {
            // Check if super admin is specifically restricted from certain roles
            $restrictedFromSuperAdmin = config('rbac.super_admin_restricted_roles', []);
            $hasRestrictedRole = \!empty(array_intersect($requiredRoles, $restrictedFromSuperAdmin));
            
            if (\!$hasRestrictedRole) {
                $result = true;
                Cache::put($cacheKey, $result, self::CACHE_TTL);
                return $result;
            }
        }

        // Check if user has any of the required roles within their company context
        $result = $user->hasAnyRole($requiredRoles);

        // Additional validation for system roles
        if ($result && $this->hasSystemRoles($requiredRoles)) {
            $result = $this->validateSystemRoleAccess($user, $requiredRoles);
        }

        // Cache the result
        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Validate system role access with additional security checks.
     *
     * @param  User  $user
     * @param  array  $requiredRoles
     * @return bool
     */
    private function validateSystemRoleAccess(User $user, array $requiredRoles): bool
    {
        $systemRoles = array_intersect($requiredRoles, Role::SYSTEM_ROLES);
        
        if (empty($systemRoles)) {
            return true;
        }

        // Additional validation for system roles
        foreach ($systemRoles as $systemRole) {
            if (\!$user->hasRole($systemRole)) {
                continue;
            }

            // Validate role is active and properly assigned
            $roleModel = $user->companyRoles()
                               ->where('name', $systemRole)
                               ->where('is_active', true)
                               ->first();

            if (\!$roleModel) {
                Log::warning('User has inactive system role', [
                    'user_id' => $user->id,
                    'role' => $systemRole,
                    'company_id' => $user->company_id,
                ]);
                return false;
            }

            // For SuperAdmin, validate it's assigned correctly
            if ($systemRole === Role::SUPER_ADMIN) {
                return $this->validateSuperAdminRole($user);
            }

            // For TenantAdmin, validate company context
            if ($systemRole === Role::TENANT_ADMIN) {
                return $this->validateTenantAdminRole($user);
            }
        }

        return true;
    }

    /**
     * Validate SuperAdmin role assignment.
     *
     * @param  User  $user
     * @return bool
     */
    private function validateSuperAdminRole(User $user): bool
    {
        // SuperAdmin should have minimal restrictions
        // Additional validation can be added here if needed
        return true;
    }

    /**
     * Validate TenantAdmin role assignment.
     *
     * @param  User  $user
     * @return bool
     */
    private function validateTenantAdminRole(User $user): bool
    {
        // TenantAdmin should be properly associated with their company
        return $user->company_id && $user->company()->exists();
    }

    /**
     * Check if any of the required roles are system-level roles.
     *
     * @param  array  $roles
     * @return bool
     */
    private function hasSystemRoles(array $roles): bool
    {
        return \!empty(array_intersect($roles, Role::SYSTEM_ROLES));
    }

    /**
     * Generate cache key for role check.
     *
     * @param  int  $userId
     * @param  int  $companyId
     * @param  array  $roles
     * @return string
     */
    private function generateCacheKey(int $userId, int $companyId, array $roles): string
    {
        $rolesHash = md5(implode('|', sort($roles)));
        return self::CACHE_PREFIX . ":$userId:$companyId:$rolesHash";
    }

    /**
     * Check if operation requires system-level logging.
     *
     * @param  array  $roles
     * @return bool
     */
    private function isSystemLevelOperation(array $roles): bool
    {
        $systemLevelRoles = [
            Role::SUPER_ADMIN,
            Role::TENANT_ADMIN,
            'admin',
            'system',
        ];

        foreach ($roles as $role) {
            if (in_array(strtolower($role), array_map('strtolower', $systemLevelRoles))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return unauthorized response (401).
     *
     * @param  Request  $request
     * @param  string  $message
     * @return Response
     */
    private function unauthorizedResponse(Request $request, string $message = 'Unauthorized'): Response
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'message' => $message,
                'code' => 401,
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        // Redirect to login for web requests
        return redirect()->guest(route('login'))
                        ->with('error', $message);
    }

    /**
     * Return forbidden response (403).
     *
     * @param  Request  $request
     * @param  array  $requiredRoles
     * @return Response
     */
    private function forbiddenResponse(Request $request, array $requiredRoles): Response
    {
        $message = 'Access denied. Required roles: ' . implode(', ', $requiredRoles);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'error' => 'Forbidden',
                'message' => 'Insufficient role privileges to access this resource',
                'required_roles' => $requiredRoles,
                'code' => 403,
                'timestamp' => now()->toISOString(),
            ], 403);
        }

        // Return 403 view for web requests
        return response()->view('errors.403', [
            'message' => 'You do not have sufficient role privileges to access this resource.',
            'required_roles' => $requiredRoles,
        ], 403);
    }

    /**
     * Clear role cache for a user.
     * Useful when user roles change.
     *
     * @param  int  $userId
     * @param  int  $companyId
     * @return void
     */
    public static function clearUserRoleCache(int $userId, int $companyId): void
    {
        $pattern = self::CACHE_PREFIX . ":$userId:$companyId:*";
        
        // Get all cache keys matching the pattern
        $cacheKeys = Cache::getRedis()->keys($pattern);
        
        if (\!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }

    /**
     * Clear all role cache.
     * Useful for system maintenance.
     *
     * @return void
     */
    public static function clearAllRoleCache(): void
    {
        $pattern = self::CACHE_PREFIX . ':*';
        $cacheKeys = Cache::getRedis()->keys($pattern);
        
        if (\!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }

    /**
     * Clear both role and permission cache for a user.
     * Convenience method for when user's RBAC data changes.
     *
     * @param  int  $userId
     * @param  int  $companyId
     * @return void
     */
    public static function clearUserRBACCache(int $userId, int $companyId): void
    {
        self::clearUserRoleCache($userId, $companyId);
        
        // Also clear permission cache if CheckPermission middleware is available
        if (class_exists(CheckPermission::class)) {
            CheckPermission::clearUserPermissionCache($userId, $companyId);
        }
    }
}
