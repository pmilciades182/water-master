<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Company Model for Water Utility RBAC System
 * 
 * Manages companies within the multi-tenant water utility management system.
 * Each company has users and roles, providing complete tenant isolation.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $subdomain
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $tax_id
 * @property string|null $logo_path
 * @property array|null $settings
 * @property array|null $billing_config
 * @property bool $is_active
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Collection<User> $users
 * @property-read Collection<Role> $roles
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain',
        'email',
        'phone',
        'address',
        'tax_id',
        'logo_path',
        'settings',
        'billing_config',
        'is_active',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'billing_config' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    // RBAC RELATIONSHIPS

    /**
     * Get the roles that belong to this company.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get active roles for this company.
     */
    public function activeRoles(): HasMany
    {
        return $this->roles()->where('is_active', true);
    }

    /**
     * Get active users for this company.
     */
    public function activeUsers(): HasMany
    {
        return $this->users()->where('is_active', true);
    }

    // RBAC SCOPES

    /**
     * Scope to include RBAC relationships.
     */
    public function scopeWithRbac(Builder $query): Builder
    {
        return $query->with(['users', 'roles']);
    }

    /**
     * Scope to include user and role counts.
     */
    public function scopeWithCounts(Builder $query): Builder
    {
        return $query->withCount(['users', 'roles', 'activeUsers', 'activeRoles']);
    }

    /**
     * Scope to filter companies with users.
     */
    public function scopeWithUsers(Builder $query): Builder
    {
        return $query->has('users');
    }

    /**
     * Scope to filter companies with specific role.
     */
    public function scopeWithRole(Builder $query, string $roleName): Builder
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    // USER MANAGEMENT METHODS

    /**
     * Create a new user for this company with a specific role.
     */
    public function createUser(array $userData, string|Role $role, int $assignedBy = null): User
    {
        $userData['company_id'] = $this->id;
        
        return User::createWithRole($userData, $role, $assignedBy);
    }

    /**
     * Get users with a specific role.
     */
    public function getUsersWithRole(string $roleName): Collection
    {
        return $this->users()
                    ->whereHas('roles', function ($query) use ($roleName) {
                        $query->where('name', $roleName)
                              ->where('company_id', $this->id);
                    })
                    ->get();
    }

    /**
     * Get users with any of the given roles.
     */
    public function getUsersWithAnyRole(array $roles): Collection
    {
        return $this->users()
                    ->whereHas('roles', function ($query) use ($roles) {
                        $query->whereIn('name', $roles)
                              ->where('company_id', $this->id);
                    })
                    ->get();
    }

    /**
     * Get users with specific permission through roles.
     */
    public function getUsersWithPermission(string $permission): Collection
    {
        return $this->users()
                    ->whereHas('roles.permissions', function ($query) use ($permission) {
                        $query->where('name', $permission);
                    })
                    ->get();
    }

    /**
     * Get admin users (TenantAdmin and SuperAdmin).
     */
    public function getAdminUsers(): Collection
    {
        return $this->getUsersWithAnyRole([Role::SUPER_ADMIN, Role::TENANT_ADMIN]);
    }

    /**
     * Get tenant admin users.
     */
    public function getTenantAdmins(): Collection
    {
        return $this->getUsersWithRole(Role::TENANT_ADMIN);
    }

    /**
     * Get manager users.
     */
    public function getManagers(): Collection
    {
        return $this->getUsersWithRole(Role::MANAGER);
    }

    /**
     * Get technician users.
     */
    public function getTechnicians(): Collection
    {
        return $this->getUsersWithRole(Role::TECHNICIAN);
    }

    /**
     * Get customer users.
     */
    public function getCustomers(): Collection
    {
        return $this->getUsersWithRole(Role::CUSTOMER);
    }

    // ROLE MANAGEMENT METHODS

    /**
     * Create a new role for this company.
     */
    public function createRole(string $name, string $description = null, array $permissions = []): Role
    {
        return Role::createWithDefaultPermissions($this->id, $name, $description, $permissions);
    }

    /**
     * Get role by name for this company.
     */
    public function getRoleByName(string $name): ?Role
    {
        return $this->roles()->where('name', $name)->first();
    }

    /**
     * Check if company has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Get default roles for this company.
     */
    public function getDefaultRoles(): Collection
    {
        return Role::getDefaultRolesForCompany($this->id);
    }

    /**
     * Get custom (non-system) roles for this company.
     */
    public function getCustomRoles(): Collection
    {
        return $this->roles()->customRoles()->get();
    }

    /**
     * Get system roles for this company.
     */
    public function getSystemRoles(): Collection
    {
        return $this->roles()->systemRoles()->get();
    }

    /**
     * Clone roles from another company.
     */
    public function cloneRolesFrom(Company $sourceCompany): Collection
    {
        $clonedRoles = collect();
        
        foreach ($sourceCompany->roles as $role) {
            if (!$this->hasRole($role->name)) {
                $clonedRole = $role->cloneToCompany($this->id);
                $clonedRoles->push($clonedRole);
            }
        }
        
        return $clonedRoles;
    }

    // UTILITY METHODS

    /**
     * Get user count by role.
     */
    public function getUserCountByRole(): array
    {
        return User::getUserCountByRole($this->id);
    }

    /**
     * Get total active users count.
     */
    public function getActiveUsersCount(): int
    {
        return $this->activeUsers()->count();
    }

    /**
     * Get total active roles count.
     */
    public function getActiveRolesCount(): int
    {
        return $this->activeRoles()->count();
    }

    /**
     * Check if company has any admin users.
     */
    public function hasAdminUsers(): bool
    {
        return $this->getAdminUsers()->isNotEmpty();
    }

    /**
     * Check if company has tenant admin users.
     */
    public function hasTenantAdmins(): bool
    {
        return $this->getTenantAdmins()->isNotEmpty();
    }

    /**
     * Get company's RBAC statistics.
     */
    public function getRbacStats(): array
    {
        $userCountByRole = $this->getUserCountByRole();
        
        return [
            'total_users' => $this->users()->count(),
            'active_users' => $this->getActiveUsersCount(),
            'total_roles' => $this->roles()->count(),
            'active_roles' => $this->getActiveRolesCount(),
            'custom_roles' => $this->getCustomRoles()->count(),
            'system_roles' => $this->getSystemRoles()->count(),
            'users_by_role' => $userCountByRole,
            'has_admin_users' => $this->hasAdminUsers(),
            'has_tenant_admins' => $this->hasTenantAdmins(),
        ];
    }

    /**
     * Initialize default roles for a new company.
     */
    public function initializeDefaultRoles(): Collection
    {
        $defaultRoles = [
            [
                'name' => Role::TENANT_ADMIN,
                'description' => 'Administrador de la empresa con acceso completo dentro de su organización',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit', 'users.delete', 'users.export', 'users.manage',
                    'products.view', 'products.create', 'products.edit', 'products.delete', 'products.export', 'products.manage',
                    'clients.view', 'clients.create', 'clients.edit', 'clients.delete', 'clients.export', 'clients.manage',
                    'services.view', 'services.create', 'services.edit', 'services.delete', 'services.export', 'services.manage',
                    'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.export', 'invoices.manage',
                    'reports.view', 'reports.create', 'reports.edit', 'reports.export', 'reports.manage',
                    'settings.view', 'settings.edit', 'settings.manage',
                    'meters.read', 'payments.process', 'maintenance.schedule', 'dashboard.view'
                ]
            ],
            [
                'name' => Role::MANAGER,
                'description' => 'Gerente con acceso a reportes y gestión de clientes y servicios',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit',
                    'products.view', 'products.create', 'products.edit',
                    'clients.view', 'clients.create', 'clients.edit', 'clients.export', 'clients.manage',
                    'services.view', 'services.create', 'services.edit', 'services.export', 'services.manage',
                    'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.export', 'invoices.manage',
                    'reports.view', 'reports.export', 'reports.manage',
                    'settings.view',
                    'payments.process', 'maintenance.schedule', 'dashboard.view'
                ]
            ],
            [
                'name' => Role::TECHNICIAN,
                'description' => 'Técnico con acceso a servicios, lecturas de medidores y mantenimiento',
                'permissions' => [
                    'clients.view',
                    'services.view', 'services.edit', 'services.manage',
                    'invoices.view',
                    'reports.view',
                    'meters.read', 'maintenance.schedule', 'dashboard.view'
                ]
            ],
            [
                'name' => Role::CUSTOMER,
                'description' => 'Cliente con acceso limitado a su información y facturas',
                'permissions' => [
                    'invoices.view',
                    'services.view',
                    'dashboard.view'
                ]
            ]
        ];

        $createdRoles = collect();
        
        foreach ($defaultRoles as $roleData) {
            if (!$this->hasRole($roleData['name'])) {
                $role = $this->createRole(
                    $roleData['name'],
                    $roleData['description'],
                    $roleData['permissions']
                );
                $createdRoles->push($role);
            }
        }
        
        return $createdRoles;
    }

    /**
     * Setup company with default roles and admin user.
     */
    public function setupWithDefaults(array $adminUserData): array
    {
        // Initialize default roles
        $roles = $this->initializeDefaultRoles();
        
        // Create tenant admin user
        $tenantAdminRole = $this->getRoleByName(Role::TENANT_ADMIN);
        $adminUser = $this->createUser($adminUserData, $tenantAdminRole);
        
        return [
            'roles' => $roles,
            'admin_user' => $adminUser,
            'stats' => $this->getRbacStats(),
        ];
    }

    /**
     * Check if company can be deleted (no active users).
     */
    public function canBeDeleted(): bool
    {
        return $this->getActiveUsersCount() === 0;
    }

    /**
     * Get dashboard data for company overview.
     */
    public function getDashboardData(): array
    {
        return [
            'company_info' => [
                'name' => $this->name,
                'is_active' => $this->is_active,
                'is_on_trial' => $this->isOnTrial(),
                'trial_ends_at' => $this->trial_ends_at,
                'created_at' => $this->created_at,
            ],
            'rbac_stats' => $this->getRbacStats(),
            'recent_users' => $this->users()
                                  ->active()
                                  ->latest('last_login_at')
                                  ->limit(5)
                                  ->get(),
            'user_growth' => $this->users()
                                 ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                 ->where('created_at', '>=', now()->subDays(30))
                                 ->groupBy('date')
                                 ->orderBy('date')
                                 ->get(),
        ];
    }
}