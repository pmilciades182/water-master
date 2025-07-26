<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Role Model for Water Utility RBAC System
 * 
 * Manages roles within the multi-tenant water utility management system.
 * Each role belongs to a company and can have multiple permissions assigned.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Company $company
 * @property-read Collection<Permission> $permissions
 * @property-read Collection<User> $users
 */
class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Default system roles for water utility management
     */
    public const SUPER_ADMIN = 'SuperAdmin';
    public const TENANT_ADMIN = 'TenantAdmin';
    public const MANAGER = 'Manager';
    public const TECHNICIAN = 'Technician';
    public const CUSTOMER = 'Customer';

    /**
     * System-level roles that have special privileges
     */
    public const SYSTEM_ROLES = [
        self::SUPER_ADMIN,
        self::TENANT_ADMIN,
    ];

    /**
     * Get the company that owns the role.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
                    ->withTimestamps();
    }

    /**
     * Get the users that have this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role')
                    ->withPivot('company_id', 'assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    /**
     * Get users with this role within the role's company context.
     */
    public function companyUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('company_id', $this->company_id);
    }

    // SCOPES

    /**
     * Scope to filter active roles.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter roles by company.
     */
    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter system-level roles.
     */
    public function scopeSystemRoles(Builder $query): Builder
    {
        return $query->whereIn('name', self::SYSTEM_ROLES);
    }

    /**
     * Scope to filter non-system roles.
     */
    public function scopeCustomRoles(Builder $query): Builder
    {
        return $query->whereNotIn('name', self::SYSTEM_ROLES);
    }

    /**
     * Scope to filter roles by name.
     */
    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

    /**
     * Scope to include role permission count.
     */
    public function scopeWithPermissionCount(Builder $query): Builder
    {
        return $query->withCount('permissions');
    }

    /**
     * Scope to include user count for this role.
     */
    public function scopeWithUserCount(Builder $query): Builder
    {
        return $query->withCount('users');
    }

    // PERMISSION MANAGEMENT METHODS

    /**
     * Check if the role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
                    ->where('name', $permission)
                    ->exists();
    }

    /**
     * Check if the role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->permissions()
                    ->whereIn('name', $permissions)
                    ->exists();
    }

    /**
     * Check if the role has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $rolePermissions = $this->permissions()->pluck('name')->toArray();
        return count(array_intersect($permissions, $rolePermissions)) === count($permissions);
    }

    /**
     * Assign a permission to the role.
     */
    public function givePermissionTo(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);

        return $this;
    }

    /**
     * Assign multiple permissions to the role.
     */
    public function givePermissionsTo(array $permissions): self
    {
        $permissionIds = [];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::where('name', $permission)->first();
                if ($permissionModel) {
                    $permissionIds[] = $permissionModel->id;
                }
            } elseif ($permission instanceof Permission) {
                $permissionIds[] = $permission->id;
            }
        }

        if (!empty($permissionIds)) {
            $this->permissions()->syncWithoutDetaching($permissionIds);
        }

        return $this;
    }

    /**
     * Revoke a permission from the role.
     */
    public function revokePermissionTo(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        if ($permission) {
            $this->permissions()->detach($permission->id);
        }

        return $this;
    }

    /**
     * Sync permissions for the role (replaces all existing permissions).
     */
    public function syncPermissions(array $permissions): self
    {
        $permissionIds = [];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::where('name', $permission)->first();
                if ($permissionModel) {
                    $permissionIds[] = $permissionModel->id;
                }
            } elseif ($permission instanceof Permission) {
                $permissionIds[] = $permission->id;
            } elseif (is_numeric($permission)) {
                $permissionIds[] = $permission;
            }
        }

        $this->permissions()->sync($permissionIds);

        return $this;
    }

    // UTILITY METHODS

    /**
     * Check if this is a system-level role.
     */
    public function isSystemRole(): bool
    {
        return in_array($this->name, self::SYSTEM_ROLES);
    }

    /**
     * Check if this is a SuperAdmin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->name === self::SUPER_ADMIN;
    }

    /**
     * Check if this is a TenantAdmin role.
     */
    public function isTenantAdmin(): bool
    {
        return $this->name === self::TENANT_ADMIN;
    }

    /**
     * Check if this is a Customer role.
     */
    public function isCustomer(): bool
    {
        return $this->name === self::CUSTOMER;
    }

    /**
     * Get all permissions grouped by module.
     */
    public function getPermissionsByModule(): Collection
    {
        return $this->permissions->groupBy('module');
    }

    /**
     * Get count of users with this role in the role's company.
     */
    public function getUserCount(): int
    {
        return $this->companyUsers()->count();
    }

    /**
     * Check if the role can be deleted (not a system role and has no users).
     */
    public function canBeDeleted(): bool
    {
        return !$this->isSystemRole() && $this->getUserCount() === 0;
    }

    /**
     * Create a new role for a specific company with default permissions.
     */
    public static function createWithDefaultPermissions(
        int $companyId,
        string $name,
        string $description = null,
        array $permissions = []
    ): self {
        $role = self::create([
            'company_id' => $companyId,
            'name' => $name,
            'description' => $description,
            'is_active' => true,
        ]);

        if (!empty($permissions)) {
            $role->givePermissionsTo($permissions);
        }

        return $role;
    }

    /**
     * Get default system roles for a company.
     */
    public static function getDefaultRolesForCompany(int $companyId): Collection
    {
        return self::byCompany($companyId)
                   ->whereIn('name', [
                       self::TENANT_ADMIN,
                       self::MANAGER,
                       self::TECHNICIAN,
                       self::CUSTOMER
                   ])
                   ->active()
                   ->get();
    }

    /**
     * Clone this role to another company.
     */
    public function cloneToCompany(int $targetCompanyId, string $newName = null): self
    {
        $newRole = self::create([
            'company_id' => $targetCompanyId,
            'name' => $newName ?? $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        // Copy permissions
        $permissionIds = $this->permissions()->pluck('permissions.id')->toArray();
        $newRole->permissions()->sync($permissionIds);

        return $newRole;
    }

    /**
     * Get role statistics for dashboard.
     */
    public function getStats(): array
    {
        return [
            'user_count' => $this->getUserCount(),
            'permission_count' => $this->permissions()->count(),
            'is_system_role' => $this->isSystemRole(),
            'can_be_deleted' => $this->canBeDeleted(),
            'created_days_ago' => $this->created_at->diffInDays(now()),
        ];
    }
}