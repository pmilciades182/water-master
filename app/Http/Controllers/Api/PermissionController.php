<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * PermissionController - Manages RBAC permissions within the water utility management system
 * 
 * This controller provides comprehensive operations for permissions including:
 * - Multi-tenant security considerations
 * - Module-based permission grouping
 * - Input validation and error handling
 * - Audit logging for sensitive operations
 * - Search, filtering and pagination
 * - Assignment operations to roles
 */
class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a paginated listing of permissions with search and filtering.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('permissions.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view permissions'
                ], 403);
            }

            $query = Permission::query()->withRoleCount();

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('module', 'LIKE', "%{$search}%")
                      ->orWhere('action', 'LIKE', "%{$search}%");
                });
            }

            // Filter by module
            if ($request->filled('module')) {
                $query->byModule($request->get('module'));
            }

            // Filter by action
            if ($request->filled('action')) {
                $query->byAction($request->get('action'));
            }

            // Filter by permission type
            if ($request->filled('type')) {
                switch ($request->get('type')) {
                    case 'crud':
                        $query->crudPermissions();
                        break;
                    case 'management':
                        $query->managementPermissions();
                        break;
                    case 'water_utility':
                        $query->waterUtilityPermissions();
                        break;
                }
            }

            // Filter by assignment status
            if ($request->filled('assigned')) {
                $assigned = filter_var($request->get('assigned'), FILTER_VALIDATE_BOOLEAN);
                if ($assigned) {
                    $query->has('roles');
                } else {
                    $query->doesntHave('roles');
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'module');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if (in_array($sortBy, ['name', 'module', 'action', 'created_at', 'roles_count'])) {
                if ($sortBy === 'roles_count') {
                    $query->orderBy('roles_count', $sortOrder);
                } else {
                    $query->orderBy($sortBy, $sortOrder);
                }
            }

            // Secondary sort by action within modules
            if ($sortBy !== 'action') {
                $query->orderBy('action', 'asc');
            }

            // Pagination
            $perPage = min($request->get('per_page', 20), 100);
            $permissions = $query->paginate($perPage);

            // Transform data
            $permissions->getCollection()->transform(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'module' => $permission->module,
                    'action' => $permission->action,
                    'display_name' => $permission->getDisplayName(),
                    'module_display_name' => $permission->getModuleDisplayName(),
                    'action_display_name' => $permission->getActionDisplayName(),
                    'level' => $permission->getPermissionLevel(),
                    'is_crud' => $permission->isCrudPermission(),
                    'is_management' => $permission->isManagementPermission(),
                    'is_water_utility' => $permission->isWaterUtilityPermission(),
                    'roles_count' => $permission->roles_count,
                    'is_assigned' => $permission->roles_count > 0,
                    'created_at' => $permission->created_at->toISOString(),
                    'updated_at' => $permission->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $permissions->items(),
                'meta' => [
                    'current_page' => $permissions->currentPage(),
                    'last_page' => $permissions->lastPage(),
                    'per_page' => $permissions->perPage(),
                    'total' => $permissions->total(),
                    'from' => $permissions->firstItem(),
                    'to' => $permissions->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching permissions', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching permissions'
            ], 500);
        }
    }

    /**
     * Display the specified permission with detailed information.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('permissions.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view permission details'
                ], 403);
            }

            $permission = Permission::with(['roles' => function ($query) use ($user) {
                $query->byCompany($user->company_id)
                      ->select('roles.id', 'roles.name', 'roles.description', 'roles.is_active');
            }])->findOrFail($id);

            $data = [
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'module' => $permission->module,
                'action' => $permission->action,
                'display_name' => $permission->getDisplayName(),
                'module_display_name' => $permission->getModuleDisplayName(),
                'action_display_name' => $permission->getActionDisplayName(),
                'level' => $permission->getPermissionLevel(),
                'is_crud' => $permission->isCrudPermission(),
                'is_management' => $permission->isManagementPermission(),
                'is_water_utility' => $permission->isWaterUtilityPermission(),
                'roles' => $permission->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                        'is_active' => $role->is_active,
                        'is_system_role' => $role->isSystemRole(),
                    ];
                }),
                'roles_count' => $permission->roles->count(),
                'is_assigned' => $permission->roles->count() > 0,
                'created_at' => $permission->created_at->toISOString(),
                'updated_at' => $permission->updated_at->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching permission details', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'permission_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        }
    }

    /**
     * Store a newly created permission.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check - only super admins can create new permissions
            if (!$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only super administrators can create new permissions'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name|regex:/^[a-z_]+\.[a-z_]+$/',
                'description' => 'required|string|max:1000',
                'module' => 'required|string|max:100|in:' . implode(',', Permission::MODULES),
                'action' => 'required|string|max:100|in:' . implode(',', Permission::ACTIONS),
            ]);

            // Validate that the name matches module.action format
            $parsedName = Permission::parsePermissionName($validated['name']);
            if ($parsedName['module'] !== $validated['module'] || $parsedName['action'] !== $validated['action']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission name must match module.action format',
                    'errors' => [
                        'name' => ['Permission name must be in format: ' . $validated['module'] . '.' . $validated['action']]
                    ]
                ], 422);
            }

            DB::beginTransaction();

            // Create permission
            $permission = Permission::create($validated);

            DB::commit();

            // Audit log
            Log::info('Permission created successfully', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'module' => $permission->module,
                'action' => $permission->action
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'module' => $permission->module,
                    'action' => $permission->action,
                    'display_name' => $permission->getDisplayName(),
                    'level' => $permission->getPermissionLevel(),
                    'created_at' => $permission->created_at->toISOString(),
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
            Log::error('Error creating permission', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating permission'
            ], 500);
        }
    }

    /**
     * Update the specified permission.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check - only super admins can update permissions
            if (!$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only super administrators can update permissions'
                ], 403);
            }

            $permission = Permission::findOrFail($id);

            // Validation
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name,' . $id . '|regex:/^[a-z_]+\.[a-z_]+$/',
                'description' => 'required|string|max:1000',
                'module' => 'required|string|max:100|in:' . implode(',', Permission::MODULES),
                'action' => 'required|string|max:100|in:' . implode(',', Permission::ACTIONS),
            ]);

            // Validate that the name matches module.action format
            $parsedName = Permission::parsePermissionName($validated['name']);
            if ($parsedName['module'] !== $validated['module'] || $parsedName['action'] !== $validated['action']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission name must match module.action format',
                    'errors' => [
                        'name' => ['Permission name must be in format: ' . $validated['module'] . '.' . $validated['action']]
                    ]
                ], 422);
            }

            DB::beginTransaction();

            $originalData = $permission->toArray();

            // Update permission
            $permission->update($validated);

            DB::commit();

            // Audit log
            Log::info('Permission updated successfully', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'original_data' => $originalData,
                'updated_data' => $permission->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'module' => $permission->module,
                    'action' => $permission->action,
                    'display_name' => $permission->getDisplayName(),
                    'level' => $permission->getPermissionLevel(),
                    'updated_at' => $permission->updated_at->toISOString(),
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
            Log::error('Error updating permission', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'permission_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permission not found or error updating permission'
            ], 500);
        }
    }

    /**
     * Remove the specified permission from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check - only super admins can delete permissions
            if (!$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only super administrators can delete permissions'
                ], 403);
            }

            $permission = Permission::findOrFail($id);

            // Check if permission is assigned to any roles
            if ($permission->isAssigned()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete permission that is assigned to roles. Remove from all roles first.',
                    'data' => [
                        'assigned_roles_count' => $permission->getRoleCount()
                    ]
                ], 409);
            }

            DB::beginTransaction();

            $permissionName = $permission->name;
            
            // Delete the permission
            $permission->delete();

            DB::commit();

            // Audit log
            Log::info('Permission deleted successfully', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'permission_id' => $id,
                'permission_name' => $permissionName
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting permission', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'permission_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permission not found or error deleting permission'
            ], 500);
        }
    }

    /**
     * Get permissions grouped by module.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function groupedByModule(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('permissions.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view permissions'
                ], 403);
            }

            $query = Permission::query();

            // Filter by specific modules if provided
            if ($request->filled('modules')) {
                $modules = is_array($request->get('modules')) 
                    ? $request->get('modules') 
                    : explode(',', $request->get('modules'));
                $query->forModules($modules);
            }

            // Filter by permission level
            if ($request->filled('level')) {
                $level = $request->get('level');
                switch ($level) {
                    case 'basic':
                        $query->byAction(Permission::ACTION_VIEW);
                        break;
                    case 'intermediate':
                        $query->forActions([Permission::ACTION_CREATE, Permission::ACTION_EDIT]);
                        break;
                    case 'advanced':
                        $query->forActions([Permission::ACTION_DELETE, Permission::ACTION_MANAGE, Permission::ACTION_EXPORT]);
                        break;
                }
            }

            $permissions = $query->orderBy('module')
                                ->orderBy('action')
                                ->get();

            // Group by module
            $groupedPermissions = $permissions->groupBy('module')->map(function ($modulePermissions, $module) {
                return [
                    'module' => $module,
                    'display_name' => ucfirst($module),
                    'permissions_count' => $modulePermissions->count(),
                    'permissions' => $modulePermissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'description' => $permission->description,
                            'action' => $permission->action,
                            'display_name' => $permission->getDisplayName(),
                            'action_display_name' => $permission->getActionDisplayName(),
                            'level' => $permission->getPermissionLevel(),
                            'is_crud' => $permission->isCrudPermission(),
                            'is_management' => $permission->isManagementPermission(),
                            'is_water_utility' => $permission->isWaterUtilityPermission(),
                            'roles_count' => $permission->getRoleCount(),
                        ];
                    }),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $groupedPermissions
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching grouped permissions', [
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
     * Get permissions for a specific module.
     *
     * @param string $module
     * @param Request $request
     * @return JsonResponse
     */
    public function byModule(string $module, Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('permissions.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view permissions'
                ], 403);
            }

            // Validate module
            if (!in_array($module, Permission::MODULES)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid module specified',
                    'available_modules' => Permission::MODULES
                ], 400);
            }

            $query = Permission::byModule($module);

            // Filter by permission type
            if ($request->filled('type')) {
                switch ($request->get('type')) {
                    case 'crud':
                        $query->crudPermissions();
                        break;
                    case 'management':
                        $query->managementPermissions();
                        break;
                }
            }

            $permissions = $query->orderBy('action')->get();

            $data = [
                'module' => $module,
                'display_name' => ucfirst($module),
                'permissions_count' => $permissions->count(),
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description,
                        'action' => $permission->action,
                        'display_name' => $permission->getDisplayName(),
                        'action_display_name' => $permission->getActionDisplayName(),
                        'level' => $permission->getPermissionLevel(),
                        'is_crud' => $permission->isCrudPermission(),
                        'is_management' => $permission->isManagementPermission(),
                        'is_water_utility' => $permission->isWaterUtilityPermission(),
                        'roles_count' => $permission->getRoleCount(),
                        'is_assigned' => $permission->isAssigned(),
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching module permissions', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'module' => $module,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching module permissions'
            ], 500);
        }
    }

    /**
     * Assign permission to multiple roles.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assignToRoles(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('permissions.manage')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to assign permissions to roles'
                ], 403);
            }

            $permission = Permission::findOrFail($id);

            // Validation
            $validated = $request->validate([
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'integer|exists:roles,id'
            ]);

            DB::beginTransaction();

            // Get roles that belong to the user's company
            $roles = Role::byCompany($user->company_id)
                        ->whereIn('id', $validated['role_ids'])
                        ->get();

            if ($roles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid roles found for your company'
                ], 400);
            }

            $assignedCount = 0;
            foreach ($roles as $role) {
                // Prevent modification of system roles unless user is super admin
                if ($role->isSystemRole() && !$user->isSuperAdmin()) {
                    continue;
                }

                $role->givePermissionTo($permission);
                $assignedCount++;
            }

            DB::commit();

            // Audit log
            Log::info('Permission assigned to roles', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'role_ids' => $validated['role_ids'],
                'assigned_count' => $assignedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Permission assigned to {$assignedCount} role(s) successfully",
                'data' => [
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name,
                    'assigned_roles_count' => $assignedCount,
                    'total_roles_count' => $permission->getRoleCount()
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
            Log::error('Error assigning permission to roles', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'permission_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permission not found or error assigning to roles'
            ], 500);
        }
    }

    /**
     * Remove permission from multiple roles.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function removeFromRoles(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('permissions.manage')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to remove permissions from roles'
                ], 403);
            }

            $permission = Permission::findOrFail($id);

            // Validation
            $validated = $request->validate([
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'integer|exists:roles,id'
            ]);

            DB::beginTransaction();

            // Get roles that belong to the user's company
            $roles = Role::byCompany($user->company_id)
                        ->whereIn('id', $validated['role_ids'])
                        ->get();

            if ($roles->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid roles found for your company'
                ], 400);
            }

            $removedCount = 0;
            foreach ($roles as $role) {
                // Prevent modification of system roles unless user is super admin
                if ($role->isSystemRole() && !$user->isSuperAdmin()) {
                    continue;
                }

                $role->revokePermissionTo($permission);
                $removedCount++;
            }

            DB::commit();

            // Audit log
            Log::info('Permission removed from roles', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'role_ids' => $validated['role_ids'],
                'removed_count' => $removedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Permission removed from {$removedCount} role(s) successfully",
                'data' => [
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name,
                    'removed_roles_count' => $removedCount,
                    'remaining_roles_count' => $permission->getRoleCount()
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
            Log::error('Error removing permission from roles', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'permission_id' => $id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permission not found or error removing from roles'
            ], 500);
        }
    }

    /**
     * Create CRUD permissions for a specific module.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createCrudPermissions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check - only super admins can create permissions
            if (!$user->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only super administrators can create permissions'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'module' => 'required|string|max:100|in:' . implode(',', Permission::MODULES),
                'module_display_name' => 'nullable|string|max:100',
            ]);

            DB::beginTransaction();

            $permissions = Permission::createCrudPermissionsForModule(
                $validated['module'],
                $validated['module_display_name'] ?? null
            );

            DB::commit();

            // Audit log
            Log::info('CRUD permissions created for module', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'module' => $validated['module'],
                'permissions_created' => $permissions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => "CRUD permissions created successfully for {$validated['module']} module",
                'data' => [
                    'module' => $validated['module'],
                    'permissions_count' => $permissions->count(),
                    'permissions' => $permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'description' => $permission->description,
                            'action' => $permission->action,
                            'display_name' => $permission->getDisplayName(),
                        ];
                    }),
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
            Log::error('Error creating CRUD permissions', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating CRUD permissions'
            ], 500);
        }
    }

    /**
     * Get available modules and actions for permission creation.
     *
     * @return JsonResponse
     */
    public function availableOptions(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('permissions.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view permission options'
                ], 403);
            }

            $data = [
                'modules' => collect(Permission::MODULES)->map(function ($module) {
                    return [
                        'value' => $module,
                        'label' => ucfirst($module),
                        'permissions_count' => Permission::byModule($module)->count(),
                    ];
                }),
                'actions' => collect(Permission::ACTIONS)->map(function ($action) {
                    return [
                        'value' => $action,
                        'label' => ucfirst($action),
                        'permissions_count' => Permission::byAction($action)->count(),
                        'type' => match ($action) {
                            Permission::ACTION_VIEW => 'basic',
                            Permission::ACTION_CREATE, Permission::ACTION_EDIT => 'intermediate',
                            Permission::ACTION_DELETE, Permission::ACTION_MANAGE, Permission::ACTION_EXPORT => 'advanced',
                            default => 'special'
                        }
                    ];
                }),
                'permission_types' => [
                    [
                        'value' => 'crud',
                        'label' => 'CRUD Operations',
                        'description' => 'Basic Create, Read, Update, Delete operations',
                        'actions' => [Permission::ACTION_VIEW, Permission::ACTION_CREATE, Permission::ACTION_EDIT, Permission::ACTION_DELETE]
                    ],
                    [
                        'value' => 'management',
                        'label' => 'Management Operations',
                        'description' => 'Advanced management and export operations',
                        'actions' => [Permission::ACTION_MANAGE, Permission::ACTION_EXPORT]
                    ],
                    [
                        'value' => 'water_utility',
                        'label' => 'Water Utility Specific',
                        'description' => 'Water utility specific operations',
                        'actions' => [Permission::ACTION_READ_METERS, Permission::ACTION_PROCESS_PAYMENTS, Permission::ACTION_SCHEDULE_MAINTENANCE, Permission::ACTION_DASHBOARD]
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching permission options', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching permission options'
            ], 500);
        }
    }

    /**
     * Get permission statistics for dashboard.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Permission check
            if (!$user->hasPermission('permissions.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view permission statistics'
                ], 403);
            }

            $stats = Permission::getStats();

            // Add user company specific stats
            $companyRoles = Role::byCompany($user->company_id)->pluck('id');
            $companyUsedPermissions = Permission::whereHas('roles', function ($query) use ($companyRoles) {
                $query->whereIn('role_id', $companyRoles);
            })->count();

            $stats['company_used_permissions'] = $companyUsedPermissions;
            $stats['company_unused_permissions'] = $stats['total'] - $companyUsedPermissions;

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching permission statistics', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching permission statistics'
            ], 500);
        }
    }
}