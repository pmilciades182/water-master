<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * User Model for Water Utility RBAC System
 * 
 * Manages users within the multi-tenant water utility management system.
 * Each user belongs to a company and can have multiple roles assigned.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $provider
 * @property string|null $provider_id
 * @property string|null $avatar
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property Carbon|null $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Company $company
 * @property-read Collection<Role> $roles
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isOAuthUser(): bool
    {
        return !is_null($this->provider) && $this->provider !== 'local';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar; // URL externa de OAuth
            }
            return asset('storage/' . $this->avatar); // Avatar local
        }
        return null;
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    // RBAC RELATIONSHIPS

    /**
     * Get the roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
                    ->withPivot('company_id', 'assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    /**
     * Get roles for this user within their company context.
     */
    public function companyRoles(): BelongsToMany
    {
        return $this->roles()->wherePivot('company_id', $this->company_id);
    }

    /**
     * Get all permissions through roles for this user.
     */
    public function permissions(): Collection
    {
        return $this->companyRoles()
                    ->with('permissions')
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->unique('id');
    }

    // RBAC SCOPES

    /**
     * Scope to filter users with specific role.
     */
    public function scopeWithRole(Builder $query, string $roleName): Builder
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope to filter users with any of the given roles.
     */
    public function scopeWithAnyRole(Builder $query, array $roles): Builder
    {
        return $query->whereHas('roles', function ($q) use ($roles) {
            $q->whereIn('name', $roles);
        });
    }

    /**
     * Scope to filter users with specific permission.
     */
    public function scopeWithPermission(Builder $query, string $permission): Builder
    {
        return $query->whereHas('roles.permissions', function ($q) use ($permission) {
            $q->where('name', $permission);
        });
    }

    /**
     * Scope to filter users by company and role.
     */
    public function scopeByCompanyAndRole(Builder $query, int $companyId, string $roleName): Builder
    {
        return $query->where('company_id', $companyId)
                     ->whereHas('roles', function ($q) use ($roleName, $companyId) {
                         $q->where('name', $roleName)
                           ->where('company_id', $companyId);
                     });
    }

    /**
     * Scope to filter super admin users.
     */
    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->withRole(Role::SUPER_ADMIN);
    }

    /**
     * Scope to filter tenant admin users.
     */
    public function scopeTenantAdmins(Builder $query): Builder
    {
        return $query->withRole(Role::TENANT_ADMIN);
    }

    /**
     * Scope to filter customer users.
     */
    public function scopeCustomers(Builder $query): Builder
    {
        return $query->withRole(Role::CUSTOMER);
    }

    // PERMISSION CHECKING METHODS

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string|Role $role): bool
    {
        if ($role instanceof Role) {
            $roleName = $role->name;
            $companyId = $role->company_id;
        } else {
            $roleName = $role;
            $companyId = $this->company_id;
        }

        return $this->roles()
                    ->where('name', $roleName)
                    ->where('company_id', $companyId)
                    ->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        $roleNames = array_map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        }, $roles);

        return $this->companyRoles()
                    ->whereIn('name', $roleNames)
                    ->exists();
    }

    /**
     * Check if user has all of the given roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        $roleNames = array_map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        }, $roles);

        $userRoles = $this->companyRoles()->pluck('name')->toArray();
        
        return count(array_intersect($roleNames, $userRoles)) === count($roleNames);
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->companyRoles()
                    ->whereHas('permissions', function ($query) use ($permission) {
                        $query->where('name', $permission);
                    })
                    ->exists();
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->companyRoles()
                    ->whereHas('permissions', function ($query) use ($permissions) {
                        $query->whereIn('name', $permissions);
                    })
                    ->exists();
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->permissions()->pluck('name')->toArray();
        
        return count(array_intersect($permissions, $userPermissions)) === count($permissions);
    }

    /**
     * Check if user can perform action on module.
     */
    public function canPerform(string $action, string $module): bool
    {
        return $this->hasPermission($module . '.' . $action);
    }

    /**
     * Check if user cannot perform action on module.
     */
    public function cannotPerform(string $action, string $module): bool
    {
        return !$this->canPerform($action, $module);
    }

    // ROLE MANAGEMENT METHODS

    /**
     * Assign a role to the user.
     */
    public function assignRole(string|Role $role, int $assignedBy = null): self
    {
        if (is_string($role)) {
            $roleModel = Role::byCompany($this->company_id)
                            ->where('name', $role)
                            ->active()
                            ->firstOrFail();
        } else {
            $roleModel = $role;
        }

        // Check if role belongs to same company
        if ($roleModel->company_id !== $this->company_id) {
            throw new \InvalidArgumentException('Role must belong to the same company as the user');
        }

        $this->roles()->syncWithoutDetaching([
            $roleModel->id => [
                'company_id' => $this->company_id,
                'assigned_at' => now(),
                'assigned_by' => $assignedBy,
            ]
        ]);

        return $this;
    }

    /**
     * Assign multiple roles to the user.
     */
    public function assignRoles(array $roles, int $assignedBy = null): self
    {
        foreach ($roles as $role) {
            $this->assignRole($role, $assignedBy);
        }

        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string|Role $role): self
    {
        if (is_string($role)) {
            $roleModel = Role::byCompany($this->company_id)
                            ->where('name', $role)
                            ->first();
        } else {
            $roleModel = $role;
        }

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }

        return $this;
    }

    /**
     * Sync roles for the user (replaces all existing roles).
     */
    public function syncRoles(array $roles, int $assignedBy = null): self
    {
        $roleIds = [];
        
        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::byCompany($this->company_id)
                                ->where('name', $role)
                                ->active()
                                ->first();
            } elseif ($role instanceof Role) {
                $roleModel = $role;
            } else {
                continue;
            }

            if ($roleModel && $roleModel->company_id === $this->company_id) {
                $roleIds[$roleModel->id] = [
                    'company_id' => $this->company_id,
                    'assigned_at' => now(),
                    'assigned_by' => $assignedBy,
                ];
            }
        }

        $this->roles()->sync($roleIds);

        return $this;
    }

    // UTILITY METHODS

    /**
     * Check if user is a Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    /**
     * Check if user is a Tenant Admin.
     */
    public function isTenantAdmin(): bool
    {
        return $this->hasRole(Role::TENANT_ADMIN);
    }

    /**
     * Check if user is a Manager.
     */
    public function isManager(): bool
    {
        return $this->hasRole(Role::MANAGER);
    }

    /**
     * Check if user is a Technician.
     */
    public function isTechnician(): bool
    {
        return $this->hasRole(Role::TECHNICIAN);
    }

    /**
     * Check if user is a Customer.
     */
    public function isCustomer(): bool
    {
        return $this->hasRole(Role::CUSTOMER);
    }

    /**
     * Check if user has system-level privileges.
     */
    public function hasSystemPrivileges(): bool
    {
        return $this->isSuperAdmin() || $this->isTenantAdmin();
    }

    /**
     * Get user's primary role (first active role).
     */
    public function getPrimaryRole(): ?Role
    {
        return $this->companyRoles()->active()->first();
    }

    /**
     * Get user's role names.
     */
    public function getRoleNames(): array
    {
        return $this->companyRoles()->pluck('name')->toArray();
    }

    /**
     * Get user's permission names.
     */
    public function getPermissionNames(): array
    {
        return $this->permissions()->pluck('name')->toArray();
    }

    /**
     * Get permissions grouped by module.
     */
    public function getPermissionsByModule(): Collection
    {
        return $this->permissions()->groupBy('module');
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(): bool
    {
        return $this->hasAnyPermission([
            'users.manage',
            'users.create',
            'users.edit',
            'users.delete'
        ]);
    }

    /**
     * Check if user can access dashboard.
     */
    public function canAccessDashboard(): bool
    {
        return $this->hasPermission('dashboard.view');
    }

    /**
     * Check if user can read water meters.
     */
    public function canReadMeters(): bool
    {
        return $this->hasPermission('meters.read');
    }

    /**
     * Check if user can process payments.
     */
    public function canProcessPayments(): bool
    {
        return $this->hasPermission('payments.process');
    }

    /**
     * Get user statistics for dashboard.
     */
    public function getStats(): array
    {
        return [
            'roles_count' => $this->companyRoles()->count(),
            'permissions_count' => $this->permissions()->count(),
            'is_system_user' => $this->hasSystemPrivileges(),
            'primary_role' => $this->getPrimaryRole()?->name,
            'last_login_days_ago' => $this->last_login_at ? $this->last_login_at->diffInDays(now()) : null,
            'account_age_days' => $this->created_at->diffInDays(now()),
        ];
    }

    /**
     * Create a new user with default role assignment.
     */
    public static function createWithRole(
        array $userData,
        string|Role $role,
        int $assignedBy = null
    ): self {
        $user = self::create($userData);
        $user->assignRole($role, $assignedBy);
        
        return $user;
    }

    /**
     * Get all users with specific role in company.
     */
    public static function getUsersWithRole(string $roleName, int $companyId): Collection
    {
        return self::byCompanyAndRole($companyId, $roleName)->get();
    }

    /**
     * Get user count by role for a company.
     */
    public static function getUserCountByRole(int $companyId): array
    {
        return self::where('company_id', $companyId)
                   ->active()
                   ->with('roles')
                   ->get()
                   ->groupBy(function ($user) {
                       return $user->getPrimaryRole()?->name ?? 'No Role';
                   })
                   ->map(function ($users) {
                       return $users->count();
                   })
                   ->toArray();
    }
}
