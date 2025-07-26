<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * UserRoleController - Manages user role assignments within the water utility management system
 * 
 * This controller provides comprehensive operations for user-role management including:
 * - Multi-tenant security (company scoping)
 * - Role assignment and removal operations
 * - Input validation and error handling
 * - Audit logging for sensitive operations
 * - Bulk operations for efficiency
 * - User role listing and management
 */
class UserRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get roles for a specific user.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getUserRoles(int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('users.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view user roles'
                ], 403);
            }

            // Get user within the same company
            $user = User::where('company_id', $currentUser->company_id)
                       ->findOrFail($userId);

            // Get user's roles with additional information
            $roles = $user->companyRoles()
                         ->with(['permissions:id,name,description,module,action'])
                         ->get();

            $rolesData = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'is_active' => $role->is_active,
                    'is_system_role' => $role->isSystemRole(),
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'description' => $permission->description,
                            'module' => $permission->module,
                            'action' => $permission->action,
                        ];
                    }),
                    'assigned_at' => $role->pivot->assigned_at,
                    'assigned_by' => $role->pivot->assigned_by,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_active' => $user->is_active,
                    ],
                    'roles' => $rolesData,
                    'roles_count' => $roles->count(),
                    'permissions_count' => $user->permissions()->count(),
                    'has_system_privileges' => $user->hasSystemPrivileges(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user roles', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'target_user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User not found or access denied'
            ], 404);
        }
    }

    /**
     * Get users with a specific role.
     *
     * @param int $roleId
     * @param Request $request
     * @return JsonResponse
     */
    public function getRoleUsers(int $roleId, Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('roles.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view role users'
                ], 403);
            }

            // Get role within the same company
            $role = Role::byCompany($currentUser->company_id)
                       ->findOrFail($roleId);

            $query = $role->companyUsers()
                         ->select('users.id', 'users.name', 'users.email', 'users.is_active', 'users.last_login_at')
                         ->with(['companyRoles:id,name']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'LIKE', "%{$search}%")
                      ->orWhere('users.email', 'LIKE', "%{$search}%");
                });
            }

            // Filter by active status
            if ($request->filled('active')) {
                $active = filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN);
                $query->where('users.is_active', $active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if (in_array($sortBy, ['name', 'email', 'last_login_at', 'assigned_at'])) {
                if ($sortBy === 'assigned_at') {
                    $query->orderBy('user_role.assigned_at', $sortOrder);
                } else {
                    $query->orderBy('users.' . $sortBy, $sortOrder);
                }
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $users = $query->paginate($perPage);

            // Transform data
            $users->getCollection()->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at?->toISOString(),
                    'roles' => $user->companyRoles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                        ];
                    }),
                    'assigned_at' => $user->pivot->assigned_at,
                    'assigned_by' => $user->pivot->assigned_by,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ],
                    'users' => $users->items(),
                    'meta' => [
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'from' => $users->firstItem(),
                        'to' => $users->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching role users', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Role not found or access denied'
            ], 404);
        }
    }

    /**
     * Assign a role to a user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function assignRole(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('users.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to assign roles to users'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            DB::beginTransaction();

            // Get the user within the same company
            $user = User::where('company_id', $currentUser->company_id)
                       ->findOrFail($validated['user_id']);

            // Get the role within the same company
            $role = Role::byCompany($currentUser->company_id)
                       ->active()
                       ->findOrFail($validated['role_id']);

            // Check if user already has this role
            if ($user->hasRole($role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has this role assigned'
                ], 409);
            }

            // Assign the role
            $user->assignRole($role, $currentUser->id);

            DB::commit();

            // Audit log
            Log::info('Role assigned to user successfully', [
                'assigned_by' => $currentUser->id,
                'company_id' => $currentUser->company_id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ],
                    'assigned_at' => now()->toISOString(),
                    'assigned_by' => $currentUser->name,
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
            Log::error('Error assigning role to user', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User or role not found, or error assigning role'
            ], 500);
        }
    }

    /**
     * Remove a role from a user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeRole(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('users.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to remove roles from users'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            DB::beginTransaction();

            // Get the user within the same company
            $user = User::where('company_id', $currentUser->company_id)
                       ->findOrFail($validated['user_id']);

            // Get the role within the same company
            $role = Role::byCompany($currentUser->company_id)
                       ->findOrFail($validated['role_id']);

            // Check if user has this role
            if (!$user->hasRole($role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have this role assigned'
                ], 409);
            }

            // Prevent removing the last role from a user
            if ($user->companyRoles()->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove the last role from a user. Users must have at least one role.'
                ], 409);
            }

            // Remove the role
            $user->removeRole($role);

            DB::commit();

            // Audit log
            Log::info('Role removed from user successfully', [
                'removed_by' => $currentUser->id,
                'company_id' => $currentUser->company_id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ],
                    'removed_at' => now()->toISOString(),
                    'removed_by' => $currentUser->name,
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
            Log::error('Error removing role from user', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User or role not found, or error removing role'
            ], 500);
        }
    }

    /**
     * Sync roles for a user (replaces all existing roles).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncUserRoles(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('users.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to sync user roles'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'integer|exists:roles,id',
            ]);

            DB::beginTransaction();

            // Get the user within the same company
            $user = User::where('company_id', $currentUser->company_id)
                       ->findOrFail($validated['user_id']);

            // Get the roles within the same company
            $roles = Role::byCompany($currentUser->company_id)
                        ->active()
                        ->whereIn('id', $validated['role_ids'])
                        ->get();

            if ($roles->count() !== count($validated['role_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some roles are invalid or not available in your company'
                ], 400);
            }

            $previousRoleIds = $user->companyRoles()->pluck('roles.id')->toArray();

            // Sync roles
            $user->syncRoles($roles->pluck('name')->toArray(), $currentUser->id);

            // Get updated roles
            $updatedRoles = $user->companyRoles()->get();

            DB::commit();

            // Audit log
            Log::info('User roles synchronized successfully', [
                'synchronized_by' => $currentUser->id,
                'company_id' => $currentUser->company_id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'previous_role_ids' => $previousRoleIds,
                'new_role_ids' => $validated['role_ids'],
                'roles_count' => $updatedRoles->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User roles synchronized successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'roles' => $updatedRoles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'description' => $role->description,
                            'is_system_role' => $role->isSystemRole(),
                        ];
                    }),
                    'roles_count' => $updatedRoles->count(),
                    'synchronized_at' => now()->toISOString(),
                    'synchronized_by' => $currentUser->name,
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
            Log::error('Error synchronizing user roles', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'User not found or error synchronizing roles'
            ], 500);
        }
    }

    /**
     * Bulk assign a role to multiple users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAssignRole(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('users.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to bulk assign roles'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'integer|exists:users,id',
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            DB::beginTransaction();

            // Get the role within the same company
            $role = Role::byCompany($currentUser->company_id)
                       ->active()
                       ->findOrFail($validated['role_id']);

            // Get the users within the same company
            $users = User::where('company_id', $currentUser->company_id)
                        ->whereIn('id', $validated['user_ids'])
                        ->get();

            if ($users->count() !== count($validated['user_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are invalid or not available in your company'
                ], 400);
            }

            $assignedCount = 0;
            $alreadyAssignedUsers = [];
            $assignedUsers = [];

            foreach ($users as $user) {
                if ($user->hasRole($role)) {
                    $alreadyAssignedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                } else {
                    $user->assignRole($role, $currentUser->id);
                    $assignedCount++;
                    $assignedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }
            }

            DB::commit();

            // Audit log
            Log::info('Role bulk assigned to users', [
                'assigned_by' => $currentUser->id,
                'company_id' => $currentUser->company_id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'user_ids' => $validated['user_ids'],
                'assigned_count' => $assignedCount,
                'already_assigned_count' => count($alreadyAssignedUsers)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Role assigned to {$assignedCount} user(s) successfully",
                'data' => [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ],
                    'assigned_users' => $assignedUsers,
                    'already_assigned_users' => $alreadyAssignedUsers,
                    'assigned_count' => $assignedCount,
                    'already_assigned_count' => count($alreadyAssignedUsers),
                    'total_requested' => count($validated['user_ids']),
                    'assigned_at' => now()->toISOString(),
                    'assigned_by' => $currentUser->name,
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
            Log::error('Error bulk assigning role to users', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error bulk assigning role to users'
            ], 500);
        }
    }

    /**
     * Bulk remove a role from multiple users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkRemoveRole(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('users.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to bulk remove roles'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'integer|exists:users,id',
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            DB::beginTransaction();

            // Get the role within the same company
            $role = Role::byCompany($currentUser->company_id)
                       ->findOrFail($validated['role_id']);

            // Get the users within the same company
            $users = User::where('company_id', $currentUser->company_id)
                        ->whereIn('id', $validated['user_ids'])
                        ->get();

            if ($users->count() !== count($validated['user_ids'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are invalid or not available in your company'
                ], 400);
            }

            $removedCount = 0;
            $notAssignedUsers = [];
            $cannotRemoveUsers = [];
            $removedUsers = [];

            foreach ($users as $user) {
                if (!$user->hasRole($role)) {
                    $notAssignedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                } elseif ($user->companyRoles()->count() <= 1) {
                    $cannotRemoveUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'reason' => 'Last role cannot be removed',
                    ];
                } else {
                    $user->removeRole($role);
                    $removedCount++;
                    $removedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }
            }

            DB::commit();

            // Audit log
            Log::info('Role bulk removed from users', [
                'removed_by' => $currentUser->id,
                'company_id' => $currentUser->company_id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'user_ids' => $validated['user_ids'],
                'removed_count' => $removedCount,
                'not_assigned_count' => count($notAssignedUsers),
                'cannot_remove_count' => count($cannotRemoveUsers)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Role removed from {$removedCount} user(s) successfully",
                'data' => [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ],
                    'removed_users' => $removedUsers,
                    'not_assigned_users' => $notAssignedUsers,
                    'cannot_remove_users' => $cannotRemoveUsers,
                    'removed_count' => $removedCount,
                    'not_assigned_count' => count($notAssignedUsers),
                    'cannot_remove_count' => count($cannotRemoveUsers),
                    'total_requested' => count($validated['user_ids']),
                    'removed_at' => now()->toISOString(),
                    'removed_by' => $currentUser->name,
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
            Log::error('Error bulk removing role from users', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error bulk removing role from users'
            ], 500);
        }
    }

    /**
     * Get available roles for assignment.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableRoles(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('roles.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view available roles'
                ], 403);
            }

            $query = Role::byCompany($currentUser->company_id)
                        ->active()
                        ->with(['permissions:id,name,module'])
                        ->withUserCount();

            // Filter by role type
            if ($request->filled('type')) {
                if ($request->get('type') === 'system') {
                    $query->systemRoles();
                } else {
                    $query->customRoles();
                }
            }

            // Exclude roles for a specific user
            if ($request->filled('exclude_user_id')) {
                $excludeUserId = $request->get('exclude_user_id');
                $excludeUser = User::where('company_id', $currentUser->company_id)
                                 ->find($excludeUserId);
                
                if ($excludeUser) {
                    $userRoleIds = $excludeUser->companyRoles()->pluck('roles.id')->toArray();
                    if (!empty($userRoleIds)) {
                        $query->whereNotIn('id', $userRoleIds);
                    }
                }
            }

            $roles = $query->orderBy('name')->get();

            $rolesData = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'is_active' => $role->is_active,
                    'is_system_role' => $role->isSystemRole(),
                    'users_count' => $role->users_count,
                    'permissions_count' => $role->permissions->count(),
                    'permissions_by_module' => $role->permissions->groupBy('module')->map(function ($permissions, $module) {
                        return [
                            'module' => $module,
                            'count' => $permissions->count(),
                        ];
                    })->values(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $rolesData
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available roles', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching available roles'
            ], 500);
        }
    }

    /**
     * Get user-role assignment statistics for dashboard.
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('users.view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to view user-role statistics'
                ], 403);
            }

            $companyId = $currentUser->company_id;

            // Get user count by role
            $userCountByRole = User::getUserCountByRole($companyId);

            // Get total stats
            $totalUsers = User::where('company_id', $companyId)->count();
            $activeUsers = User::where('company_id', $companyId)->active()->count();
            $totalRoles = Role::byCompany($companyId)->count();
            $activeRoles = Role::byCompany($companyId)->active()->count();

            // Get users without roles (shouldn't happen, but check anyway)
            $usersWithoutRoles = User::where('company_id', $companyId)
                                   ->doesntHave('roles')
                                   ->count();

            // Get users with multiple roles
            $usersWithMultipleRoles = User::where('company_id', $companyId)
                                        ->has('roles', '>', 1)
                                        ->count();

            // Get recent role assignments
            $recentAssignments = DB::table('user_role')
                ->join('users', 'user_role.user_id', '=', 'users.id')
                ->join('roles', 'user_role.role_id', '=', 'roles.id')
                ->where('user_role.company_id', $companyId)
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'roles.id as role_id',
                    'roles.name as role_name',
                    'user_role.assigned_at'
                )
                ->orderBy('user_role.assigned_at', 'desc')
                ->limit(10)
                ->get();

            $stats = [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $totalUsers - $activeUsers,
                'total_roles' => $totalRoles,
                'active_roles' => $activeRoles,
                'inactive_roles' => $totalRoles - $activeRoles,
                'users_without_roles' => $usersWithoutRoles,
                'users_with_multiple_roles' => $usersWithMultipleRoles,
                'user_count_by_role' => $userCountByRole,
                'recent_assignments' => $recentAssignments->map(function ($assignment) {
                    return [
                        'user_id' => $assignment->user_id,
                        'user_name' => $assignment->user_name,
                        'role_id' => $assignment->role_id,
                        'role_name' => $assignment->role_name,
                        'assigned_at' => $assignment->assigned_at,
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user-role statistics', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching user-role statistics'
            ], 500);
        }
    }

    /**
     * Transfer all users from one role to another.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function transferRoleUsers(Request $request): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            
            // Permission check
            if (!$currentUser->hasPermission('users.edit')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to transfer role users'
                ], 403);
            }

            // Validation
            $validated = $request->validate([
                'from_role_id' => 'required|integer|exists:roles,id',
                'to_role_id' => 'required|integer|exists:roles,id|different:from_role_id',
                'keep_existing_roles' => 'boolean', // If true, adds the new role; if false, replaces
            ]);

            DB::beginTransaction();

            // Get both roles within the same company
            $fromRole = Role::byCompany($currentUser->company_id)
                          ->findOrFail($validated['from_role_id']);
            
            $toRole = Role::byCompany($currentUser->company_id)
                        ->active()
                        ->findOrFail($validated['to_role_id']);

            // Get all users with the source role
            $users = $fromRole->companyUsers()->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found with the source role'
                ], 400);
            }

            $transferredCount = 0;
            $skippedUsers = [];
            $transferredUsers = [];

            $keepExistingRoles = $validated['keep_existing_roles'] ?? false;

            foreach ($users as $user) {
                // Skip if user already has the target role and we're keeping existing roles
                if ($keepExistingRoles && $user->hasRole($toRole)) {
                    $skippedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'reason' => 'Already has target role',
                    ];
                    continue;
                }

                if ($keepExistingRoles) {
                    // Add the new role without removing the old one
                    if (!$user->hasRole($toRole)) {
                        $user->assignRole($toRole, $currentUser->id);
                        $transferredCount++;
                        $transferredUsers[] = [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'action' => 'added_role',
                        ];
                    }
                } else {
                    // Replace the old role with the new one
                    $user->removeRole($fromRole);
                    $user->assignRole($toRole, $currentUser->id);
                    $transferredCount++;
                    $transferredUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'action' => 'replaced_role',
                    ];
                }
            }

            DB::commit();

            // Audit log
            Log::info('Role users transferred successfully', [
                'transferred_by' => $currentUser->id,
                'company_id' => $currentUser->company_id,
                'from_role_id' => $fromRole->id,
                'from_role_name' => $fromRole->name,
                'to_role_id' => $toRole->id,
                'to_role_name' => $toRole->name,
                'keep_existing_roles' => $keepExistingRoles,
                'transferred_count' => $transferredCount,
                'skipped_count' => count($skippedUsers)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Transferred {$transferredCount} user(s) successfully",
                'data' => [
                    'from_role' => [
                        'id' => $fromRole->id,
                        'name' => $fromRole->name,
                    ],
                    'to_role' => [
                        'id' => $toRole->id,
                        'name' => $toRole->name,
                    ],
                    'keep_existing_roles' => $keepExistingRoles,
                    'transferred_users' => $transferredUsers,
                    'skipped_users' => $skippedUsers,
                    'transferred_count' => $transferredCount,
                    'skipped_count' => count($skippedUsers),
                    'total_processed' => count($users),
                    'transferred_at' => now()->toISOString(),
                    'transferred_by' => $currentUser->name,
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
            Log::error('Error transferring role users', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error transferring role users'
            ], 500);
        }
    }
}