<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Permission Model for Water Utility RBAC System
 * 
 * Manages permissions within the water utility management system.
 * Permissions are global across the system and assigned to roles.
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $module
 * @property string $action
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Collection<Role> $roles
 */
class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'module',
        'action',
    ];

    /**
     * Water utility system modules
     */
    public const MODULE_USERS = 'users';
    public const MODULE_PRODUCTS = 'products';
    public const MODULE_CLIENTS = 'clients';
    public const MODULE_SERVICES = 'services';
    public const MODULE_INVOICES = 'invoices';
    public const MODULE_REPORTS = 'reports';
    public const MODULE_SETTINGS = 'settings';

    /**
     * Standard CRUD actions
     */
    public const ACTION_VIEW = 'view';
    public const ACTION_CREATE = 'create';
    public const ACTION_EDIT = 'edit';
    public const ACTION_DELETE = 'delete';
    public const ACTION_EXPORT = 'export';
    public const ACTION_MANAGE = 'manage';

    /**
     * Special water utility actions
     */
    public const ACTION_READ_METERS = 'read';
    public const ACTION_PROCESS_PAYMENTS = 'process';
    public const ACTION_SCHEDULE_MAINTENANCE = 'schedule';
    public const ACTION_DASHBOARD = 'dashboard';

    /**
     * All available modules
     */
    public const MODULES = [
        self::MODULE_USERS,
        self::MODULE_PRODUCTS,
        self::MODULE_CLIENTS,
        self::MODULE_SERVICES,
        self::MODULE_INVOICES,
        self::MODULE_REPORTS,
        self::MODULE_SETTINGS,
    ];

    /**
     * All available actions
     */
    public const ACTIONS = [
        self::ACTION_VIEW,
        self::ACTION_CREATE,
        self::ACTION_EDIT,
        self::ACTION_DELETE,
        self::ACTION_EXPORT,
        self::ACTION_MANAGE,
        self::ACTION_READ_METERS,
        self::ACTION_PROCESS_PAYMENTS,
        self::ACTION_SCHEDULE_MAINTENANCE,
        self::ACTION_DASHBOARD,
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
                    ->withTimestamps();
    }

    // SCOPES

    /**
     * Scope to filter permissions by module.
     */
    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter permissions by action.
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter permissions by module and action.
     */
    public function scopeByModuleAndAction(Builder $query, string $module, string $action): Builder
    {
        return $query->where('module', $module)->where('action', $action);
    }

    /**
     * Scope to filter permissions by name pattern.
     */
    public function scopeByNamePattern(Builder $query, string $pattern): Builder
    {
        return $query->where('name', 'LIKE', $pattern);
    }

    /**
     * Scope to get permissions for specific modules.
     */
    public function scopeForModules(Builder $query, array $modules): Builder
    {
        return $query->whereIn('module', $modules);
    }

    /**
     * Scope to get permissions for specific actions.
     */
    public function scopeForActions(Builder $query, array $actions): Builder
    {
        return $query->whereIn('action', $actions);
    }

    /**
     * Scope to include role count for this permission.
     */
    public function scopeWithRoleCount(Builder $query): Builder
    {
        return $query->withCount('roles');
    }

    /**
     * Scope to get CRUD permissions only.
     */
    public function scopeCrudPermissions(Builder $query): Builder
    {
        return $query->whereIn('action', [
            self::ACTION_VIEW,
            self::ACTION_CREATE,
            self::ACTION_EDIT,
            self::ACTION_DELETE,
        ]);
    }

    /**
     * Scope to get management permissions.
     */
    public function scopeManagementPermissions(Builder $query): Builder
    {
        return $query->whereIn('action', [
            self::ACTION_MANAGE,
            self::ACTION_EXPORT,
        ]);
    }

    /**
     * Scope to get water utility specific permissions.
     */
    public function scopeWaterUtilityPermissions(Builder $query): Builder
    {
        return $query->whereIn('action', [
            self::ACTION_READ_METERS,
            self::ACTION_PROCESS_PAYMENTS,
            self::ACTION_SCHEDULE_MAINTENANCE,
            self::ACTION_DASHBOARD,
        ]);
    }

    // HELPER METHODS

    /**
     * Check if this permission belongs to a specific module.
     */
    public function belongsToModule(string $module): bool
    {
        return $this->module === $module;
    }

    /**
     * Check if this permission has a specific action.
     */
    public function hasAction(string $action): bool
    {
        return $this->action === $action;
    }

    /**
     * Check if this is a CRUD permission.
     */
    public function isCrudPermission(): bool
    {
        return in_array($this->action, [
            self::ACTION_VIEW,
            self::ACTION_CREATE,
            self::ACTION_EDIT,
            self::ACTION_DELETE,
        ]);
    }

    /**
     * Check if this is a management permission.
     */
    public function isManagementPermission(): bool
    {
        return in_array($this->action, [
            self::ACTION_MANAGE,
            self::ACTION_EXPORT,
        ]);
    }

    /**
     * Check if this is a water utility specific permission.
     */
    public function isWaterUtilityPermission(): bool
    {
        return in_array($this->action, [
            self::ACTION_READ_METERS,
            self::ACTION_PROCESS_PAYMENTS,
            self::ACTION_SCHEDULE_MAINTENANCE,
            self::ACTION_DASHBOARD,
        ]);
    }

    /**
     * Get the display name for the permission.
     */
    public function getDisplayName(): string
    {
        return ucfirst(str_replace(['.', '_'], ' ', $this->name));
    }

    /**
     * Get the module display name.
     */
    public function getModuleDisplayName(): string
    {
        return ucfirst($this->module);
    }

    /**
     * Get the action display name.
     */
    public function getActionDisplayName(): string
    {
        return ucfirst($this->action);
    }

    /**
     * Get permission level (basic, intermediate, advanced).
     */
    public function getPermissionLevel(): string
    {
        return match ($this->action) {
            self::ACTION_VIEW => 'basic',
            self::ACTION_CREATE, self::ACTION_EDIT => 'intermediate',
            self::ACTION_DELETE, self::ACTION_MANAGE, self::ACTION_EXPORT => 'advanced',
            default => 'special'
        };
    }

    /**
     * Get count of roles with this permission.
     */
    public function getRoleCount(): int
    {
        return $this->roles()->count();
    }

    /**
     * Check if permission is assigned to any role.
     */
    public function isAssigned(): bool
    {
        return $this->getRoleCount() > 0;
    }

    /**
     * Get roles that have this permission within a specific company.
     */
    public function getRolesForCompany(int $companyId): Collection
    {
        return $this->roles()->where('company_id', $companyId)->get();
    }

    // STATIC HELPER METHODS

    /**
     * Create a new permission with proper naming convention.
     */
    public static function createPermission(
        string $module,
        string $action,
        string $description = null
    ): self {
        return self::create([
            'name' => $module . '.' . $action,
            'description' => $description ?? ucfirst($action) . ' permission for ' . ucfirst($module) . ' module',
            'module' => $module,
            'action' => $action,
        ]);
    }

    /**
     * Get all permissions grouped by module.
     */
    public static function getAllGroupedByModule(): Collection
    {
        return self::all()->groupBy('module');
    }

    /**
     * Get all permissions grouped by action.
     */
    public static function getAllGroupedByAction(): Collection
    {
        return self::all()->groupBy('action');
    }

    /**
     * Find permission by module and action.
     */
    public static function findByModuleAndAction(string $module, string $action): ?self
    {
        return self::where('module', $module)
                   ->where('action', $action)
                   ->first();
    }

    /**
     * Get permissions for a specific user role combination.
     */
    public static function getPermissionsForRole(string $roleName, int $companyId): Collection
    {
        return self::whereHas('roles', function ($query) use ($roleName, $companyId) {
            $query->where('name', $roleName)
                  ->where('company_id', $companyId);
        })->get();
    }

    /**
     * Get all CRUD permissions for a module.
     */
    public static function getCrudPermissionsForModule(string $module): Collection
    {
        return self::byModule($module)
                   ->crudPermissions()
                   ->get();
    }

    /**
     * Get all management permissions for a module.
     */
    public static function getManagementPermissionsForModule(string $module): Collection
    {
        return self::byModule($module)
                   ->managementPermissions()
                   ->get();
    }

    /**
     * Create standard CRUD permissions for a module.
     */
    public static function createCrudPermissionsForModule(string $module, string $moduleDisplayName = null): Collection
    {
        $moduleDisplayName = $moduleDisplayName ?? ucfirst($module);
        $permissions = collect();

        $crudActions = [
            self::ACTION_VIEW => "View $moduleDisplayName",
            self::ACTION_CREATE => "Create new $moduleDisplayName",
            self::ACTION_EDIT => "Edit existing $moduleDisplayName",
            self::ACTION_DELETE => "Delete $moduleDisplayName",
        ];

        foreach ($crudActions as $action => $description) {
            $permission = self::firstOrCreate(
                [
                    'module' => $module,
                    'action' => $action,
                ],
                [
                    'name' => $module . '.' . $action,
                    'description' => $description,
                ]
            );
            $permissions->push($permission);
        }

        return $permissions;
    }

    /**
     * Get permission statistics for dashboard.
     */
    public static function getStats(): array
    {
        $total = self::count();
        $byModule = self::select('module')
                       ->selectRaw('COUNT(*) as count')
                       ->groupBy('module')
                       ->get()
                       ->pluck('count', 'module')
                       ->toArray();

        $assigned = self::has('roles')->count();
        $unassigned = $total - $assigned;

        return [
            'total' => $total,
            'assigned' => $assigned,
            'unassigned' => $unassigned,
            'by_module' => $byModule,
            'modules_count' => count($byModule),
        ];
    }

    /**
     * Validate if a permission name follows the correct format.
     */
    public static function isValidPermissionName(string $name): bool
    {
        return preg_match('/^[a-z_]+\.[a-z_]+$/', $name) === 1;
    }

    /**
     * Parse permission name to get module and action.
     */
    public static function parsePermissionName(string $name): array
    {
        if (!self::isValidPermissionName($name)) {
            throw new \InvalidArgumentException("Invalid permission name format: $name");
        }

        [$module, $action] = explode('.', $name, 2);
        
        return [
            'module' => $module,
            'action' => $action,
        ];
    }
}