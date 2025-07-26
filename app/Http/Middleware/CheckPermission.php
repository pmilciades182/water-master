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
use App\Models\Permission;

/**
 * CheckPermission Middleware for Water Utility RBAC System
 * 
 * This middleware enforces permission-based access control with multi-tenant security.
 * It supports:
 * - Dynamic permission checking with flexible parameter handling
 * - Multi-tenant security with company isolation 
 * - Performance optimization through caching
 * - Comprehensive logging for security audit trails
 * - Flexible parameter formats (strings, arrays, pipe-separated)
 * 
 * Usage Examples:
 * - Route::middleware('permission:users.view')->group(function () {...});
 * - Route::middleware('permission:users.create,products.manage')->get(...);
 * - Route::middleware('permission:users.view|users.edit')->post(...);
 * 
 * @package App\Http\Middleware
 * @author Water Management System
 * @version 1.0.0
 */
class CheckPermission
{
    /**
     * Cache TTL for permission checks in seconds (15 minutes)
     */
    private const CACHE_TTL = 900;

    /**
     * Cache key prefix for permission checks
     */
    private const CACHE_PREFIX = 'rbac_permission_check';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Check if user is authenticated
        if (\!Auth::check()) {
            return $this->unauthorizedResponse($request, 'Authentication required');
        }

        /** @var User $user */
        $user = Auth::user();

        // Validate user has active status
        if (\!$user->is_active) {
            Log::warning('Inactive user attempted access', [
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
            Log::error('User without company attempted access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
            
            return $this->unauthorizedResponse($request, 'Invalid company association');
        }

        // Parse permissions from middleware parameters
        $requiredPermissions = $this->parsePermissions($permissions);
        
        if (empty($requiredPermissions)) {
            Log::warning('Permission middleware called without permissions', [
                'user_id' => $user->id,
                'route' => $request->route()?->getName(),
                'raw_permissions' => $permissions,
            ]);
            
            return $next($request);
        }

        // Check if user has any of the required permissions
        $hasPermission = $this->checkUserPermissions($user, $requiredPermissions);

        if (\!$hasPermission) {
            // Log permission denial for security audit
            Log::warning('Permission denied', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'required_permissions' => $requiredPermissions,
                'user_permissions' => $user->getPermissionNames(),
                'user_roles' => $user->getRoleNames(),
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);

            return $this->forbiddenResponse($request, $requiredPermissions);
        }

        // Log successful permission check for high-privilege operations
        if ($this->isHighPrivilegeOperation($requiredPermissions)) {
            Log::info('High privilege operation authorized', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'permissions' => $requiredPermissions,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Update user's last activity (optional, can be disabled for performance)
        if (config('rbac.track_last_activity', false)) {
            $this->updateUserActivity($user);
        }

        return $next($request);
    }

    /**
     * Parse permissions from middleware parameters.
     * Supports multiple formats:
     * - Single permission: 'users.view'
     * - Multiple parameters: 'users.view', 'users.edit'
     * - Comma-separated: 'users.view,users.edit'
     * - Pipe-separated: 'users.view|users.edit'
     * - Mixed formats
     *
     * @param  array  $permissions
     * @return array
     */
    private function parsePermissions(array $permissions): array
    {
        $parsed = [];

        foreach ($permissions as $permission) {
            // Handle comma-separated permissions
            if (str_contains($permission, ',')) {
                $commaSeparated = array_map('trim', explode(',', $permission));
                $parsed = array_merge($parsed, $commaSeparated);
                continue;
            }

            // Handle pipe-separated permissions (OR logic)
            if (str_contains($permission, '|')) {
                $pipeSeparated = array_map('trim', explode('|', $permission));
                $parsed = array_merge($parsed, $pipeSeparated);
                continue;
            }

            // Single permission
            $parsed[] = trim($permission);
        }

        // Remove empty values and duplicates
        return array_unique(array_filter($parsed));
    }

    /**
     * Check if user has any of the required permissions with caching.
     *
     * @param  User  $user
     * @param  array  $requiredPermissions
     * @return bool
     */
    private function checkUserPermissions(User $user, array $requiredPermissions): bool
    {
        // Generate cache key for this permission check
        $cacheKey = $this->generateCacheKey($user->id, $user->company_id, $requiredPermissions);

        // Try to get result from cache first
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult \!== null) {
            return $cachedResult;
        }

        // Super admin bypass (if enabled)
        if (config('rbac.super_admin_bypass', true) && $user->isSuperAdmin()) {
            $result = true;
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;
        }

        // Check if user has any of the required permissions
        $result = $user->hasAnyPermission($requiredPermissions);

        // Cache the result
        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Generate cache key for permission check.
     *
     * @param  int  $userId
     * @param  int  $companyId
     * @param  array  $permissions
     * @return string
     */
    private function generateCacheKey(int $userId, int $companyId, array $permissions): string
    {
        $permissionsHash = md5(implode('|', sort($permissions)));
        return self::CACHE_PREFIX . ":$userId:$companyId:$permissionsHash";
    }

    /**
     * Check if operation requires high privilege logging.
     *
     * @param  array  $permissions
     * @return bool
     */
    private function isHighPrivilegeOperation(array $permissions): bool
    {
        $highPrivilegeActions = [
            'delete',
            'manage',
            'export',
            'system',
            'admin',
        ];

        foreach ($permissions as $permission) {
            foreach ($highPrivilegeActions as $action) {
                if (str_contains(strtolower($permission), $action)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Update user's last activity timestamp.
     *
     * @param  User  $user
     * @return void
     */
    private function updateUserActivity(User $user): void
    {
        // Use cache to prevent database hits on every request
        $lastUpdateKey = "user_activity_updated:{$user->id}";
        
        if (\!Cache::has($lastUpdateKey)) {
            $user->updateLastLogin();
            Cache::put($lastUpdateKey, true, 300); // 5 minutes
        }
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
     * @param  array  $requiredPermissions
     * @return Response
     */
    private function forbiddenResponse(Request $request, array $requiredPermissions): Response
    {
        $message = 'Access denied. Required permissions: ' . implode(', ', $requiredPermissions);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'error' => 'Forbidden',
                'message' => 'Insufficient permissions to access this resource',
                'required_permissions' => $requiredPermissions,
                'code' => 403,
                'timestamp' => now()->toISOString(),
            ], 403);
        }

        // Return 403 view for web requests
        return response()->view('errors.403', [
            'message' => 'You do not have sufficient permissions to access this resource.',
            'required_permissions' => $requiredPermissions,
        ], 403);
    }

    /**
     * Clear permission cache for a user.
     * Useful when user permissions change.
     *
     * @param  int  $userId
     * @param  int  $companyId
     * @return void
     */
    public static function clearUserPermissionCache(int $userId, int $companyId): void
    {
        $pattern = self::CACHE_PREFIX . ":$userId:$companyId:*";
        
        // Get all cache keys matching the pattern
        $cacheKeys = Cache::getRedis()->keys($pattern);
        
        if (\!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }

    /**
     * Clear all permission cache.
     * Useful for system maintenance.
     *
     * @return void
     */
    public static function clearAllPermissionCache(): void
    {
        $pattern = self::CACHE_PREFIX . ':*';
        $cacheKeys = Cache::getRedis()->keys($pattern);
        
        if (\!empty($cacheKeys)) {
            Cache::getRedis()->del($cacheKeys);
        }
    }
}
