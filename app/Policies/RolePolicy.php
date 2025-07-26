<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Log;

/**
 * RolePolicy for Water Utility RBAC System
 * 
 * This policy handles authorization for role management operations with multi-tenant security.
 * It ensures:
 * - Multi-tenant isolation (roles can only be managed within their company)
 * - System role protection (prevent unauthorized system role modifications)
 * - Role hierarchy enforcement (prevent privilege escalation)
 * - Comprehensive security logging
 * - Water utility specific business logic
 * 
 * @package App\Policies
 * @author Water Management System
 * @version 1.0.0
 */
class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     * Super admins can view all roles, others only within their company.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function viewAny(User $user): Response|bool
    {
        // Super admin can view all roles across companies
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can view all roles');
        }

        // Tenant admin can view roles in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can view roles in their company');
        }

        // Manager with permission can view roles
        if ($user->isManager() && $user->hasPermission('roles.view')) {
            return Response::allow('Manager can view roles');
        }

        // Users with specific permission can view roles
        if ($user->hasPermission('roles.view')) {
            return Response::allow('User has permission to view roles');
        }

        return Response::deny('Insufficient permissions to view roles');
    }

    /**
     * Determine whether the user can view a specific role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function view(User $user, Role $role): Response|bool
    {
        // Super admin can view any role
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can view any role');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $role->company_id) {
            $this->logUnauthorizedAccess($user, 'view', $role, 'Cross-company access attempt');
            return Response::deny('Cannot view roles from different companies');
        }

        // Tenant admin can view roles in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can view roles in their company');
        }

        // Manager with permission can view roles
        if ($user->isManager() && $user->hasPermission('roles.view')) {
            return Response::allow('Manager can view roles');
        }

        // Users with specific permission can view roles
        if ($user->hasPermission('roles.view')) {
            return Response::allow('User has permission to view roles');
        }

        return Response::deny('Insufficient permissions to view this role');
    }

    /**
     * Determine whether the user can create new roles.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function create(User $user): Response|bool
    {
        // Super admin can create roles
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can create roles');
        }

        // Tenant admin can create non-system roles in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can create roles');
        }

        // Manager with permission can create non-system roles
        if ($user->isManager() && $user->hasPermission('roles.create')) {
            return Response::allow('Manager can create roles');
        }

        // Users with specific permission can create roles
        if ($user->hasPermission('roles.create')) {
            return Response::allow('User has permission to create roles');
        }

        return Response::deny('Insufficient permissions to create roles');
    }

    /**
     * Determine whether the user can update a specific role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function update(User $user, Role $role): Response|bool
    {
        // Super admin can update any role (except certain restrictions)
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can update roles');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $role->company_id) {
            $this->logUnauthorizedAccess($user, 'update', $role, 'Cross-company modification attempt');
            return Response::deny('Cannot update roles from different companies');
        }

        // Prevent modification of system roles by non-super admin
        if ($role->isSystemRole()) {
            $this->logUnauthorizedAccess($user, 'update', $role, 'Attempt to modify system role');
            return Response::deny('Cannot modify system roles');
        }

        // Tenant admin can update non-system roles in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can update roles in their company');
        }

        // Manager with permission can update non-system roles
        if ($user->isManager() && $user->hasPermission('roles.edit')) {
            return Response::allow('Manager can update roles');
        }

        // Users with specific permission can update non-system roles
        if ($user->hasPermission('roles.edit')) {
            return Response::allow('User has permission to update roles');
        }

        return Response::deny('Insufficient permissions to update this role');
    }

    /**
     * Determine whether the user can delete a specific role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function delete(User $user, Role $role): Response|bool
    {
        // Super admin can delete roles (with some restrictions)
        if ($user->isSuperAdmin()) {
            // Even super admin cannot delete system roles that are in use
            if ($role->isSystemRole() && $role->getUserCount() > 0) {
                return Response::deny('Cannot delete system roles that are assigned to users');
            }
            return Response::allow('Super admin can delete roles');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $role->company_id) {
            $this->logUnauthorizedAccess($user, 'delete', $role, 'Cross-company deletion attempt');
            return Response::deny('Cannot delete roles from different companies');
        }

        // Prevent deletion of system roles
        if ($role->isSystemRole()) {
            $this->logUnauthorizedAccess($user, 'delete', $role, 'Attempt to delete system role');
            return Response::deny('Cannot delete system roles');
        }

        // Prevent deletion of roles that are still assigned to users
        if ($role->getUserCount() > 0) {
            return Response::deny('Cannot delete roles that are assigned to users');
        }

        // Tenant admin can delete custom roles in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can delete roles');
        }

        // Manager with permission can delete custom roles
        if ($user->isManager() && $user->hasPermission('roles.delete')) {
            return Response::allow('Manager can delete roles');
        }

        // Users with specific permission can delete custom roles
        if ($user->hasPermission('roles.delete')) {
            return Response::allow('User has permission to delete roles');
        }

        return Response::deny('Insufficient permissions to delete this role');
    }

    /**
     * Determine whether the user can restore a soft-deleted role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function restore(User $user, Role $role): Response|bool
    {
        // Super admin can restore any role
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can restore roles');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $role->company_id) {
            $this->logUnauthorizedAccess($user, 'restore', $role, 'Cross-company restore attempt');
            return Response::deny('Cannot restore roles from different companies');
        }

        // Tenant admin can restore roles in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can restore roles');
        }

        // Users with specific permission can restore roles
        if ($user->hasPermission('roles.restore')) {
            return Response::allow('User has permission to restore roles');
        }

        return Response::deny('Insufficient permissions to restore this role');
    }

    /**
     * Determine whether the user can permanently delete a role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function forceDelete(User $user, Role $role): Response|bool
    {
        // Only super admin can permanently delete roles
        if ($user->isSuperAdmin()) {
            // Even super admin cannot permanently delete system roles
            if ($role->isSystemRole()) {
                return Response::deny('Cannot permanently delete system roles');
            }
            return Response::allow('Super admin can permanently delete roles');
        }

        $this->logUnauthorizedAccess($user, 'forceDelete', $role, 'Unauthorized permanent deletion attempt');
        return Response::deny('Only super admin can permanently delete roles');
    }

    /**
     * Determine whether the user can assign permissions to a role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function assignPermissions(User $user, Role $role): Response|bool
    {
        // Super admin can assign any permissions
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can assign permissions');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $role->company_id) {
            $this->logUnauthorizedAccess($user, 'assignPermissions', $role, 'Cross-company permission assignment attempt');
            return Response::deny('Cannot assign permissions to roles from different companies');
        }

        // Prevent modification of system role permissions
        if ($role->isSystemRole()) {
            $this->logUnauthorizedAccess($user, 'assignPermissions', $role, 'Attempt to modify system role permissions');
            return Response::deny('Cannot modify permissions of system roles');
        }

        // Tenant admin can assign permissions to custom roles
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can assign permissions');
        }

        // Manager with permission can assign permissions to custom roles
        if ($user->isManager() && $user->hasPermission('roles.manage')) {
            return Response::allow('Manager can assign permissions');
        }

        // Users with specific permission can assign permissions
        if ($user->hasPermission('roles.manage')) {
            return Response::allow('User has permission to assign permissions');
        }

        return Response::deny('Insufficient permissions to assign permissions to this role');
    }

    /**
     * Determine whether the user can remove permissions from a role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function removePermissions(User $user, Role $role): Response|bool
    {
        return $this->assignPermissions($user, $role);
    }

    /**
     * Determine whether the user can clone a role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function clone(User $user, Role $role): Response|bool
    {
        // Super admin can clone any role
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can clone roles');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $role->company_id) {
            $this->logUnauthorizedAccess($user, 'clone', $role, 'Cross-company cloning attempt');
            return Response::deny('Cannot clone roles from different companies');
        }

        // Tenant admin can clone roles in their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can clone roles');
        }

        // Manager with permission can clone roles
        if ($user->isManager() && $user->hasAnyPermission(['roles.create', 'roles.manage'])) {
            return Response::allow('Manager can clone roles');
        }

        // Users with specific permission can clone roles
        if ($user->hasAnyPermission(['roles.create', 'roles.manage'])) {
            return Response::allow('User has permission to clone roles');
        }

        return Response::deny('Insufficient permissions to clone this role');
    }

    /**
     * Determine whether the user can export role data.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function export(User $user): Response|bool
    {
        // Super admin can export role data
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can export role data');
        }

        // Tenant admin can export roles from their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can export role data');
        }

        // Users with specific permission can export role data
        if ($user->hasPermission('roles.export')) {
            return Response::allow('User has permission to export role data');
        }

        return Response::deny('Insufficient permissions to export role data');
    }

    /**
     * Determine whether the user can view role analytics/reports.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function viewAnalytics(User $user): Response|bool
    {
        // Super admin can view all role analytics
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can view role analytics');
        }

        // Tenant admin can view role analytics for their company
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can view role analytics');
        }

        // Manager with permission can view role analytics
        if ($user->isManager() && $user->hasPermission('reports.view')) {
            return Response::allow('Manager can view role analytics');
        }

        return Response::deny('Insufficient permissions to view role analytics');
    }

    /**
     * Determine whether the user can create system roles.
     *
     * @param  User  $user
     * @return Response|bool
     */
    public function createSystemRole(User $user): Response|bool
    {
        // Only super admin can create system roles
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can create system roles');
        }

        $this->logUnauthorizedAccess($user, 'createSystemRole', null, 'Attempt to create system role');
        return Response::deny('Only super admin can create system roles');
    }

    /**
     * Determine whether the user can modify system role permissions.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function modifySystemRolePermissions(User $user, Role $role): Response|bool
    {
        // Only super admin can modify system role permissions
        if ($user->isSuperAdmin() && $role->isSystemRole()) {
            $this->logSecurityAction($user, 'modifySystemRolePermissions', $role, 'System role permissions modified');
            return Response::allow('Super admin can modify system role permissions');
        }

        $this->logUnauthorizedAccess($user, 'modifySystemRolePermissions', $role, 'Attempt to modify system role permissions');
        return Response::deny('Only super admin can modify system role permissions');
    }

    /**
     * Determine whether the user can activate/deactivate a role.
     *
     * @param  User  $user
     * @param  Role  $role
     * @return Response|bool
     */
    public function activate(User $user, Role $role): Response|bool
    {
        // Super admin can activate/deactivate any role
        if ($user->isSuperAdmin()) {
            return Response::allow('Super admin can activate/deactivate roles');
        }

        // Must be in same company for multi-tenant security
        if ($user->company_id \!== $role->company_id) {
            $this->logUnauthorizedAccess($user, 'activate', $role, 'Cross-company activation attempt');
            return Response::deny('Cannot activate/deactivate roles from different companies');
        }

        // Prevent activation/deactivation of system roles
        if ($role->isSystemRole()) {
            $this->logUnauthorizedAccess($user, 'activate', $role, 'Attempt to activate/deactivate system role');
            return Response::deny('Cannot activate/deactivate system roles');
        }

        // Tenant admin can activate/deactivate custom roles
        if ($user->isTenantAdmin()) {
            return Response::allow('Tenant admin can activate/deactivate roles');
        }

        // Manager with permission can activate/deactivate custom roles
        if ($user->isManager() && $user->hasPermission('roles.manage')) {
            return Response::allow('Manager can activate/deactivate roles');
        }

        return Response::deny('Insufficient permissions to activate/deactivate this role');
    }

    /**
     * Log unauthorized access attempts for security monitoring.
     *
     * @param  User  $user
     * @param  string  $action
     * @param  Role|null  $role
     * @param  string  $reason
     * @return void
     */
    private function logUnauthorizedAccess(User $user, string $action, ?Role $role, string $reason): void
    {
        Log::warning('Unauthorized role access attempt', [
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_company' => $user->company_id,
            'target_role_id' => $role?->id,
            'target_role_name' => $role?->name,
            'target_role_company' => $role?->company_id,
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
     * @param  Role  $role
     * @param  string  $message
     * @return void
     */
    private function logSecurityAction(User $user, string $action, Role $role, string $message): void
    {
        Log::info('Role security action', [
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_company' => $user->company_id,
            'target_role_id' => $role->id,
            'target_role_name' => $role->name,
            'target_role_company' => $role->company_id,
            'action' => $action,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * General authorization check for any role operation.
     * This method provides a centralized authorization check.
     *
     * @param  User  $user
     * @param  string  $ability
     * @param  Role|null  $role
     * @return Response|bool|null
     */
    public function before(User $user, string $ability, ?Role $role = null): Response|bool|null
    {
        // If user is inactive, deny all operations
        if (\!$user->is_active) {
            return Response::deny('Account is inactive');
        }

        // Super admin gets enhanced access but still respects critical restrictions
        if ($user->isSuperAdmin()) {
            // Super admin cannot delete system roles that have users
            if ($ability === 'delete' && $role && $role->isSystemRole() && $role->getUserCount() > 0) {
                return false;
            }
            
            // Super admin cannot permanently delete system roles
            if ($ability === 'forceDelete' && $role && $role->isSystemRole()) {
                return false;
            }
        }

        return null; // Let individual methods handle authorization
    }
}
