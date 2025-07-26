<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

/**
 * UserPolicy for Water Utility RBAC System
 * 
 * This policy handles authorization for user management operations with multi-tenant security.
 * It ensures:
 * - Multi-tenant isolation (users can only manage users in their company)
 * - Role-based permissions for user operations
 * - System role protection (prevent unauthorized system role assignments)
 * - Comprehensive security logging
 * - Water utility specific business logic
 * 
 * @package App\Policies
 * @author Water Management System
 * @version 1.0.0
 */
class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * Super admins can view all users, others only within their company.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function viewAny(User $user): Response|bool
    {
        // Super admin can view users across all companies
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can view all users');
        }

        // Tenant admin and managers can view users in their company
        if ($user->hasAnyRole([Role::TENANT_ADMIN, Role::MANAGER])) {
            return Response::allow('User can view users in their company');
        }

        // Users with specific permission can view users
        if ($user->hasPermission('users.view')) {
            return Response::allow('User has permission to view users');
        }

        return Response::deny('Insufficient permissions to view users');
    }

    /**
     * Determine whether the user can view a specific user.
     * Users can always view their own profile.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function view(User $user, User $model): Response|bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return Response::allow('User can view their own profile');
        }

        // Super admin can view any user
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can view any user');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $model->company_id) {
            $this->logUnauthorizedAccess($user, 'view', $model, 'Cross-company access attempt');
            return Response::deny('Cannot view users from different companies');
        }

        // Tenant admin can view users in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can view users in their company');
        }

        // Manager can view users in their company
        if ($user->isManager() && $user->hasPermission('users.view')) {
            return Response::allow('Manager can view users in their company');
        }

        // Check specific permission
        if ($user->hasPermission('users.view')) {
            return Response::allow('User has permission to view users');
        }

        return Response::deny('Insufficient permissions to view this user');
    }

    /**
     * Determine whether the user can create new users.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function create(User $user): Response|bool
    {
        // Super admin can create users
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can create users');
        }

        // Tenant admin can create users in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can create users');
        }

        // Manager with permission can create users
        if ($user->isManager() && $user->hasPermission('users.create')) {
            return Response::allow('Manager can create users');
        }

        // Check specific permission
        if ($user->hasPermission('users.create')) {
            return Response::allow('User has permission to create users');
        }

        return Response::deny('Insufficient permissions to create users');
    }

    /**
     * Determine whether the user can update a specific user.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function update(User $user, User $model): Response|bool
    {
        // Users can always update their own profile (with restrictions)
        if ($user->id === $model->id) {
            return Response::allow('User can update their own profile');
        }

        // Super admin can update any user
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can update any user');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $model->company_id) {
            $this->logUnauthorizedAccess($user, 'update', $model, 'Cross-company access attempt');
            return Response::deny('Cannot update users from different companies');
        }

        // Prevent modification of other super admins (unless by super admin)
        if ($model->isSuperAdmin() && \!$user->isSuperAdmin()) {
            $this->logUnauthorizedAccess($user, 'update', $model, 'Attempt to modify super admin');
            return Response::deny('Cannot modify super admin users');
        }

        // Tenant admin can update users in their company (except other tenant admins)
        if ($user->isTenantAdmin() && \!$model->isTenantAdmin()) {
            return Response::allow('Tenant admin can update users in their company');
        }

        // Manager with permission can update non-admin users
        if ($user->isManager() && \!$model->hasSystemPrivileges() && $user->hasPermission('users.edit')) {
            return Response::allow('Manager can update non-admin users');
        }

        // Check specific permission for non-system users
        if (\!$model->hasSystemPrivileges() && $user->hasPermission('users.edit')) {
            return Response::allow('User has permission to edit non-system users');
        }

        return Response::deny('Insufficient permissions to update this user');
    }

    /**
     * Determine whether the user can delete a specific user.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function delete(User $user, User $model): Response|bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return Response::deny('Cannot delete your own account');
        }

        // Super admin can delete any user (except other super admins for safety)
        if ($user->isSuperAdmin() && \!$model->isSuperAdmin()) {
            return Response::allow('Super admin can delete users');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $model->company_id) {
            $this->logUnauthorizedAccess($user, 'delete', $model, 'Cross-company deletion attempt');
            return Response::deny('Cannot delete users from different companies');
        }

        // Prevent deletion of system users
        if ($model->hasSystemPrivileges()) {
            $this->logUnauthorizedAccess($user, 'delete', $model, 'Attempt to delete system user');
            return Response::deny('Cannot delete system users');
        }

        // Tenant admin can delete non-system users in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can delete users');
        }

        // Manager with permission can delete non-admin users
        if ($user->isManager() && $user->hasPermission('users.delete')) {
            return Response::allow('Manager can delete non-admin users');
        }

        // Check specific permission
        if ($user->hasPermission('users.delete')) {
            return Response::allow('User has permission to delete users');
        }

        return Response::deny('Insufficient permissions to delete this user');
    }

    /**
     * Determine whether the user can restore a soft-deleted user.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function restore(User $user, User $model): Response|bool
    {
        // Super admin can restore any user
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can restore users');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $model->company_id) {
            $this->logUnauthorizedAccess($user, 'restore', $model, 'Cross-company restore attempt');
            return Response::deny('Cannot restore users from different companies');
        }

        // Tenant admin can restore users in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can restore users');
        }

        // Check specific permission
        if ($user->hasPermission('users.restore')) {
            return Response::allow('User has permission to restore users');
        }

        return Response::deny('Insufficient permissions to restore this user');
    }

    /**
     * Determine whether the user can permanently delete a user.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function forceDelete(User $user, User $model): Response|bool
    {
        // Only super admin can permanently delete users
        if ($user->isSuperAdmin() && \!$model->isSuperAdmin()) {
            return Response::allow('Super admin can permanently delete users');
        }

        $this->logUnauthorizedAccess($user, 'forceDelete', $model, 'Unauthorized permanent deletion attempt');
        return Response::deny('Only super admin can permanently delete users');
    }

    /**
     * Determine whether the user can assign roles to another user.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function assignRoles(User $user, User $model): Response|bool
    {
        // Super admin can assign any role
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can assign roles');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $model->company_id) {
            $this->logUnauthorizedAccess($user, 'assignRoles', $model, 'Cross-company role assignment attempt');
            return Response::deny('Cannot assign roles to users from different companies');
        }

        // Tenant admin can assign non-system roles in their company
        if ($user->isTenantAdmin() && \!$model->isSuperAdmin()) {
            return Response::allow('Tenant admin can assign roles');
        }

        // Manager with permission can assign non-admin roles
        if ($user->isManager() && \!$model->hasSystemPrivileges() && $user->hasPermission('users.manage')) {
            return Response::allow('Manager can assign non-admin roles');
        }

        // Check specific permission
        if ($user->hasPermission('users.manage') && \!$model->hasSystemPrivileges()) {
            return Response::allow('User has permission to manage user roles');
        }

        return Response::deny('Insufficient permissions to assign roles');
    }

    /**
     * Determine whether the user can remove roles from another user.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function removeRoles(User $user, User $model): Response|bool
    {
        return $this->assignRoles($user, $model);
    }

    /**
     * Determine whether the user can manage user permissions directly.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function managePermissions(User $user, User $model): Response|bool
    {
        // Only super admin can directly manage permissions
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can manage permissions');
        }

        return Response::deny('Only super admin can directly manage user permissions');
    }

    /**
     * Determine whether the user can activate/deactivate another user.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function activate(User $user, User $model): Response|bool
    {
        // Users cannot activate/deactivate themselves
        if ($user->id === $model->id) {
            return Response::deny('Cannot activate/deactivate your own account');
        }

        // Super admin can activate/deactivate any user
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can activate/deactivate users');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $model->company_id) {
            $this->logUnauthorizedAccess($user, 'activate', $model, 'Cross-company activation attempt');
            return Response::deny('Cannot activate/deactivate users from different companies');
        }

        // Prevent activation/deactivation of system users
        if ($model->hasSystemPrivileges()) {
            $this->logUnauthorizedAccess($user, 'activate', $model, 'Attempt to activate/deactivate system user');
            return Response::deny('Cannot activate/deactivate system users');
        }

        // Tenant admin can activate/deactivate users in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can activate/deactivate users');
        }

        // Manager with permission can activate/deactivate non-admin users
        if ($user->isManager() && $user->hasPermission('users.manage')) {
            return Response::allow('Manager can activate/deactivate users');
        }

        return Response::deny('Insufficient permissions to activate/deactivate this user');
    }

    /**
     * Determine whether the user can export user data.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function export(User $user): Response|bool
    {
        // Super admin can export user data
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can export user data');
        }

        // Tenant admin can export users from their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can export user data');
        }

        // Check specific permission
        if ($user->hasPermission('users.export')) {
            return Response::allow('User has permission to export user data');
        }

        return Response::deny('Insufficient permissions to export user data');
    }

    /**
     * Determine whether the user can view user analytics/reports.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function viewAnalytics(User $user): Response|bool
    {
        // Super admin can view all analytics
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can view user analytics');
        }

        // Tenant admin can view analytics for their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can view user analytics');
        }

        // Manager with permission can view analytics
        if ($user->isManager() && $user->hasPermission('reports.view')) {
            return Response::allow('Manager can view user analytics');
        }

        return Response::deny('Insufficient permissions to view user analytics');
    }

    /**
     * Determine whether the user can impersonate another user.
     *
     * @param  User  $user
     * @param  User  $model
     * @return Response|bool
     */
    public function impersonate(User $user, User $model): Response|bool
    {
        // Users cannot impersonate themselves
        if ($user->id === $model->id) {
            return Response::deny('Cannot impersonate yourself');
        }

        // Only super admin can impersonate (for debugging/support)
        if ($user->isSuperAdmin() && \!$model->isSuperAdmin()) {
            $this->logSecurityAction($user, 'impersonate', $model, 'User impersonation initiated');
            return Response::allow('Super admin can impersonate users');
        }

        $this->logUnauthorizedAccess($user, 'impersonate', $model, 'Unauthorized impersonation attempt');
        return Response::deny('Only super admin can impersonate users');
    }

    /**
     * Log unauthorized access attempts for security monitoring.
     *
     * @param  User  $user
     * @param  string  $action
     * @param  User  $model
     * @param  string  $reason
     * @return void
     */
    private function logUnauthorizedAccess(User $user, string $action, User $model, string $reason): void
    {
        Log::warning('Unauthorized user access attempt', [
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_company' => $user->company_id,
            'target_id' => $model->id,
            'target_email' => $model->email,
            'target_company' => $model->company_id,
            'action' => $action,
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log security-related actions for audit trail.
     *
     * @param  User  $user
     * @param  string  $action
     * @param  User  $model
     * @param  string  $message
     * @return void
     */
    private function logSecurityAction(User $user, string $action, User $model, string $message): void
    {
        Log::info('User security action', [
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_company' => $user->company_id,
            'target_id' => $model->id,
            'target_email' => $model->email,
            'target_company' => $model->company_id,
            'action' => $action,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * General authorization check for any user operation.
     * This method provides a centralized authorization check.
     *
     * @param  User  $user
     * @param  string  $ability
     * @param  User|null  $model
     * @return Response|bool
     */
    public function before(User $user, string $ability, ?User $model = null): Response|bool|null
    {
        // Super admin gets enhanced access but still respects some restrictions
        if ($user->isSuperAdmin()) {
            // Even super admin cannot delete themselves or other super admins
            if ($ability === 'delete' && $model && ($model->id === $user->id || $model->isSuperAdmin())) {
                return false;
            }
            
            // Super admin cannot impersonate other super admins
            if ($ability === 'impersonate' && $model && $model->isSuperAdmin()) {
                return false;
            }
        }

        // If user is inactive, deny all operations except viewing own profile
        if (\!$user->is_active) {
            if ($ability === 'view' && $model && $model->id === $user->id) {
                return null; // Let the method handle it
            }
            return Response::deny('Account is inactive');
        }

        return null; // Let individual methods handle authorization
    }
}
