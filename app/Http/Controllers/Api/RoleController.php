<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * RoleController - Manages RBAC roles within the water utility management system
 * 
 * This controller provides comprehensive CRUD operations for roles including:
 * - Multi-tenant security (company scoping)
 * - Permission management for roles
 * - Input validation and error handling
 * - Audit logging for sensitive operations
 * - Search, filtering and pagination
 * - Bulk operations where appropriate
 */
class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a paginated listing of roles with search and filtering.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view roles'
                ], 403);
            }

            $query = Role::byCompany($user->company_id)
                        ->with(['permissions:id,name,description,module', 'company:id,name'])
                        ->withPermissionCount()
                        ->withUserCount();

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Filter by active status
            if ($request->filled('active')) {
                $active = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN);
                if ($active) {
                    $query->active();
                } else {
                    $query->where('is_active', false);
                }
            }

            // Filter by role type
            if ($request->filled('type')) {
                if ($request->get('type') === 'system') {
                    $query->systemRoles();
                } else {
                    $query->customRoles();
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if (in_array($sortBy, ['name', 'created_at', 'updated_at', 'permissions_count', 'users_count'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $roles = $query->paginate($perPage);

            // Transform data
            $roles->getCollection()->transform(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'is_active' => $role->is_active,
                    'is_system_role' => $role->isSystemRole(),
                    'can_be_deleted' => $role->canBeDeleted(),
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'description' => $permission->description,
                            'module' => $permission->module,
                        ];
                    }),
                    'created_at' => $role->created_at->toISOString(),
                    'updated_at' => $role->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $roles->items(),
                'meta' => [
                    'current_page' => $roles->currentPage(),
                    'last_page' => $roles->lastPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'from' => $roles->firstItem(),
                    'to' => $roles->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching roles', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching roles'
            ], 500);
        }
    }

    /**
     * Display the specified role with detailed information.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view role details'
                ], 403);
            }

            $role = Role::byCompany($user->company_id)
                       ->with([
                           'permissions:id,name,description,module,action',
                           'users:id,name,email',
                           'company:id,name'
                       ])
                       ->findOrFail($id);

            $data = [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_active' => $role->is_active,
                'is_system_role' => $role->isSystemRole(),
                'can_be_deleted' => $role->canBeDeleted(),
                'company' => [
                    'id' => $role->company->id,
                    'name' => $role->company->name,
                ],
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description,
                        'module' => $permission->module,
                        'action' => $permission->action,
                        'display_name' => $permission->getDisplayName(),
                    ];
                }),
                'permissions_by_module' => $role->getPermissionsByModule()->map(function ($permissions, $module) {
                    return [
                        'module' => $module,
                        'permissions' => $permissions->map(function ($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'action' => $permission->action,
                                'description' => $permission->description,
                            ];
                        }),
                    ];
                }),
                'users' => $role->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'assigned_at' => $user->pivot->assigned_at,
                    ];
                }),
                'stats' => $role->getStats(),
                'created_at' => $role->created_at->toISOString(),
                'updated_at' => $role->updated_at->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching role details', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'role_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Role not found or access denied'
            ], 404);
        }
    }

    /**
     * Store a newly created role.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.create')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to create roles'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,NULL,id,company_id,' . $user->company_id,
                'description' => 'nullable|string|max:1000',
                'is_active' => 'boolean',
                'permissions' => 'nullable|array',
                'permissions.*' => 'integer|exists:permissions,id'
            ]);

            DB::beginTransaction();

            // Create role
            $role = Role::create([
                'company_id' => $user->company_id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Assign permissions if provided
            if (!empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            // Load relationships for response
            $role->load(['permissions:id,name,description,module', 'company:id,name']);

            DB::commit();

            // Audit log
            Log::info('Role created successfully', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_assigned' => count($validated['permissions'] ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'is_active' => $role->is_active,
                    'is_system_role' => $role->isSystemRole(),
                    'permissions' => $role->permissions,
                    'created_at' => $role->created_at->toISOString(),
                ]
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating role', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating role'
            ], 500);
        }
    }

    /**
     * Update the specified role.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to update roles'
                ], 403);
            }

            $role = Role::byCompany($user->company_id)->findOrFail($id);

            // Prevent modification of system roles
            if ($role->isSystemRole() && !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be modified'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $id . ',id,company_id,' . $user->company_id,
                'description' => 'nullable|string|max:1000',
                'is_active' => 'boolean',
                'permissions' => 'nullable|array',
                'permissions.*' => 'integer|exists:permissions,id'
            ]);

            DB::beginTransaction();

            $originalData = $role->toArray();

            // Update role
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? $role->description,
                'is_active' => $validated['is_active'] ?? $role->is_active,
            ]);

            // Update permissions if provided
            if (array_key_exists('permissions', $validated)) {
                $role->permissions()->sync($validated['permissions'] ?? []);
            }

            // Load relationships for response
            $role->load(['permissions:id,name,description,module', 'company:id,name']);

            DB::commit();

            // Audit log
            Log::info('Role updated successfully', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'original_data' => $originalData,
                'updated_data' => $role->toArray(),
                'permissions_count' => $role->permissions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'is_active' => $role->is_active,
                    'is_system_role' => $role->isSystemRole(),
                    'permissions' => $role->permissions,
                    'updated_at' => $role->updated_at->toISOString(),
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating role', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'role_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Role not found or error updating role'
            ], 500);
        }
    }

    /**
     * Remove the specified role from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to delete roles'
                ], 403);
            }

            $role = Role::byCompany($user->company_id)->findOrFail($id);

            // Check if role can be deleted
            if (!$role->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete system roles or roles with assigned users'
                ], 409);
            }

            DB::beginTransaction();

            $roleName = $role->name;
            
            // Remove all permissions from role
            $role->permissions()->detach();
            
            // Delete the role
            $role->delete();

            DB::commit();

            // Audit log
            Log::info('Role deleted successfully', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role_id' => $id,
                'role_name' => $roleName
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting role', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'role_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Role not found or error deleting role'
            ], 500);
        }
    }

    /**
     * Get available permissions for role assignment.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availablePermissions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view permissions'
                ], 403);
            }

            $query = Permission::query();

            // Filter by module if specified
            if ($request->filled('module')) {
                $query->byModule($request->get('module'));
            }

            // Filter by action if specified
            if ($request->filled('action')) {
                $query->byAction($request->get('action'));
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            $permissions = $query->orderBy('module')
                                ->orderBy('action')
                                ->get();

            // Group by module
            $groupedPermissions = $permissions->groupBy('module')->map(function ($modulePermissions, $module) {
                return [
                    'module' => $module,
                    'display_name' => ucfirst($module),
                    'permissions' => $modulePermissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'description' => $permission->description,
                            'action' => $permission->action,
                            'display_name' => $permission->getDisplayName(),
                            'level' => $permission->getPermissionLevel(),
                        ];
                    }),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $groupedPermissions
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available permissions', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching permissions'
            ], 500);
        }
    }

    /**
     * Assign permissions to a role.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assignPermissions(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to assign permissions to roles'
                ], 403);
            }

            $role = Role::byCompany($user->company_id)->findOrFail($id);

            // Prevent modification of system roles
            if ($role->isSystemRole() && !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be modified'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'integer|exists:permissions,id'
            ]);

            DB::beginTransaction();

            $previousPermissionIds = $role->permissions->pluck('id')->toArray();
            
            // Assign permissions (adds new ones without removing existing)
            $role->permissions()->syncWithoutDetaching($validated['permissions']);

            $role->load('permissions:id,name,description,module');

            DB::commit();

            // Audit log
            Log::info('Permissions assigned to role', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'previous_permissions' => $previousPermissionIds,
                'new_permissions' => $validated['permissions'],
                'total_permissions' => $role->permissions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permissions assigned successfully',
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permissions' => $role->permissions,
                    'permissions_count' => $role->permissions->count()
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning permissions to role', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'role_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Role not found or error assigning permissions'
            ], 500);
        }
    }

    /**
     * Remove permissions from a role.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function removePermissions(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to remove permissions from roles'
                ], 403);
            }

            $role = Role::byCompany($user->company_id)->findOrFail($id);

            // Prevent modification of system roles
            if ($role->isSystemRole() && !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be modified'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'integer|exists:permissions,id'
            ]);

            DB::beginTransaction();

            $previousPermissionIds = $role->permissions->pluck('id')->toArray();
            
            // Remove permissions
            $role->permissions()->detach($validated['permissions']);

            $role->load('permissions:id,name,description,module');

            DB::commit();

            // Audit log
            Log::info('Permissions removed from role', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'previous_permissions' => $previousPermissionIds,
                'removed_permissions' => $validated['permissions'],
                'remaining_permissions' => $role->permissions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permissions removed successfully',
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permissions' => $role->permissions,
                    'permissions_count' => $role->permissions->count()
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing permissions from role', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'role_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Role not found or error removing permissions'
            ], 500);
        }
    }

    /**
     * Sync permissions for a role (replaces all existing permissions).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to sync permissions for roles'
                ], 403);
            }

            $role = Role::byCompany($user->company_id)->findOrFail($id);

            // Prevent modification of system roles
            if ($role->isSystemRole() && !$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be modified'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'permissions' => 'nullable|array',
                'permissions.*' => 'integer|exists:permissions,id'
            ]);

            DB::beginTransaction();

            $previousPermissionIds = $role->permissions->pluck('id')->toArray();
            
            // Sync permissions (replaces all existing permissions)
            $role->permissions()->sync($validated['permissions'] ?? []);

            $role->load('permissions:id,name,description,module');

            DB::commit();

            // Audit log
            Log::info('Permissions synced for role', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'previous_permissions' => $previousPermissionIds,
                'new_permissions' => $validated['permissions'] ?? [],
                'total_permissions' => $role->permissions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permissions synchronized successfully',
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permissions' => $role->permissions,
                    'permissions_count' => $role->permissions->count()
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing permissions for role', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'role_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Role not found or error syncing permissions'
            ], 500);
        }
    }

    /**
     * Clone a role to create a new one with same permissions.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function clone(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.create')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to clone roles'
                ], 403);
            }

            $sourceRole = Role::byCompany($user->company_id)->findOrFail($id);

            // Validation
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,NULL,id,company_id,' . $user->company_id,
                'description' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Clone the role
            $newRole = $sourceRole->cloneToCompany($user->company_id, $validated['name']);
            
            if (!empty($validated['description'])) {
                $newRole->update(['description' => $validated['description']]);
            }

            $newRole->load(['permissions:id,name,description,module', 'company:id,name']);

            DB::commit();

            // Audit log
            Log::info('Role cloned successfully', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'source_role_id' => $sourceRole->id,
                'source_role_name' => $sourceRole->name,
                'new_role_id' => $newRole->id,
                'new_role_name' => $newRole->name,
                'permissions_cloned' => $newRole->permissions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role cloned successfully',
                'data' => [
                    'id' => $newRole->id,
                    'name' => $newRole->name,
                    'description' => $newRole->description,
                    'is_active' => $newRole->is_active,
                    'permissions' => $newRole->permissions,
                    'permissions_count' => $newRole->permissions->count(),
                    'created_at' => $newRole->created_at->toISOString(),
                ]
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cloning role', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'source_role_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Role not found or error cloning role'
            ], 500);
        }
    }

    /**
     * Get role statistics for dashboard.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('roles.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view role statistics'
                ], 403);
            }

            $companyId = $user->company_id;

            $stats = [
                'total_roles' => Role::byCompany($companyId)->count(),
                'active_roles' => Role::byCompany($companyId)->active()->count(),
                'inactive_roles' => Role::byCompany($companyId)->where('is_active', false)->count(),
                'system_roles' => Role::byCompany($companyId)->systemRoles()->count(),
                'custom_roles' => Role::byCompany($companyId)->customRoles()->count(),
                'roles_with_users' => Role::byCompany($companyId)->has('users')->count(),
                'total_permissions' => Permission::count(),
                'recent_roles' => Role::byCompany($companyId)
                    ->latest()
                    ->limit(5)
                    ->get(['id', 'name', 'created_at'])
                    ->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'created_at' => $role->created_at->toISOString(),
                        ];
                    }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching role statistics', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching role statistics'
            ], 500);
        }
    }
}