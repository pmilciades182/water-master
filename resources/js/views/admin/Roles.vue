<template>
  <MainLayout page-title="Gestión de Roles">
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Roles</h1>
            <p class="mt-1 text-sm text-gray-600">
              Gestiona los roles y permisos de {{ authStore.companyName }}
            </p>
          </div>
          <div class="mt-4 sm:mt-0 flex space-x-3">
            <button
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <i class="fas fa-plus mr-2"></i>
              Nuevo Rol
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-user-shield text-2xl text-blue-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Total Roles
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.total_roles }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-users text-2xl text-green-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Usuarios con Roles
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.users_with_roles }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-key text-2xl text-yellow-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Total Permisos
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.total_permissions }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Search and Filter -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Buscar roles
              </label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Nombre o descripción..."
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Ordenar por
              </label>
              <select
                v-model="filters.sort"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
                <option value="name">Nombre</option>
                <option value="users_count">Cantidad de usuarios</option>
                <option value="permissions_count">Cantidad de permisos</option>
                <option value="created_at">Fecha de creación</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Roles Grid -->
        <div v-if="loading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
          <p class="mt-2 text-gray-500">Cargando roles...</p>
        </div>

        <div v-else-if="roles.length === 0" class="text-center py-8">
          <i class="fas fa-user-shield text-4xl text-gray-300 mb-4"></i>
          <h3 class="text-lg font-medium text-gray-900">No hay roles</h3>
          <p class="text-gray-500">Comienza creando tu primer rol.</p>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
          <div
            v-for="role in filteredRoles"
            :key="role.id"
            class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow"
          >
            <div class="p-6">
              <!-- Role Header -->
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                  <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-shield text-blue-600"></i>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-lg font-medium text-gray-900">{{ role.name }}</h3>
                    <p v-if="role.description" class="text-sm text-gray-500">
                      {{ role.description }}
                    </p>
                  </div>
                </div>
                <div class="relative">
                  <button
                    @click="toggleDropdown(role.id)"
                    class="text-gray-400 hover:text-gray-600"
                  >
                    <i class="fas fa-ellipsis-v"></i>
                  </button>
                  <div
                    v-show="activeDropdown === role.id"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200"
                  >
                    <div class="py-1">
                      <button
                        @click="openEditModal(role)"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        <i class="fas fa-edit mr-2"></i>
                        Editar
                      </button>
                      <button
                        @click="openPermissionsModal(role)"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        <i class="fas fa-key mr-2"></i>
                        Gestionar Permisos
                      </button>
                      <button
                        @click="cloneRole(role)"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                      >
                        <i class="fas fa-copy mr-2"></i>
                        Clonar
                      </button>
                      <div class="border-t border-gray-100"></div>
                      <button
                        @click="confirmDelete(role)"
                        class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                      >
                        <i class="fas fa-trash mr-2"></i>
                        Eliminar
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Role Stats -->
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ role.users_count || 0 }}</div>
                  <div class="text-xs text-gray-500">Usuarios</div>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                  <div class="text-2xl font-bold text-gray-900">{{ role.permissions_count || 0 }}</div>
                  <div class="text-xs text-gray-500">Permisos</div>
                </div>
              </div>

              <!-- Permissions Preview -->
              <div v-if="role.permissions?.length" class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Permisos principales:</h4>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="permission in role.permissions.slice(0, 3)"
                    :key="permission.id"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                  >
                    {{ permission.name }}
                  </span>
                  <span
                    v-if="role.permissions.length > 3"
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                  >
                    +{{ role.permissions.length - 3 }} más
                  </span>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-2">
                <button
                  @click="openEditModal(role)"
                  class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <i class="fas fa-edit mr-1"></i>
                  Editar
                </button>
                <button
                  @click="openPermissionsModal(role)"
                  class="flex-1 inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <i class="fas fa-key mr-1"></i>
                  Permisos
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Modal -->
    <RoleModal
      v-if="showRoleModal"
      :role="selectedRole"
      :is-edit="isEditMode"
      @close="closeRoleModal"
      @save="handleRoleSave"
    />

    <!-- Permissions Modal -->
    <RolePermissionsModal
      v-if="showPermissionsModal"
      :role="selectedRole"
      :available-permissions="availablePermissions"
      @close="closePermissionsModal"
      @save="handlePermissionsSave"
    />
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import MainLayout from '../../components/Layout/MainLayout.vue'
import RoleModal from '../../components/Modals/RoleModal.vue'
import RolePermissionsModal from '../../components/Modals/RolePermissionsModal.vue'
import axios from 'axios'

const authStore = useAuthStore()

// State
const roles = ref([])
const availablePermissions = ref([])
const loading = ref(false)
const showRoleModal = ref(false)
const showPermissionsModal = ref(false)
const selectedRole = ref(null)
const isEditMode = ref(false)
const activeDropdown = ref(null)

const stats = ref({
  total_roles: 0,
  users_with_roles: 0,
  total_permissions: 0
})

const filters = ref({
  search: '',
  sort: 'name'
})

// Computed
const filteredRoles = computed(() => {
  let filtered = [...roles.value]

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(role =>
      role.name.toLowerCase().includes(search) ||
      role.description?.toLowerCase().includes(search)
    )
  }

  // Sort
  filtered.sort((a, b) => {
    switch (filters.value.sort) {
      case 'users_count':
        return (b.users_count || 0) - (a.users_count || 0)
      case 'permissions_count':
        return (b.permissions_count || 0) - (a.permissions_count || 0)
      case 'created_at':
        return new Date(b.created_at) - new Date(a.created_at)
      default:
        return a.name.localeCompare(b.name)
    }
  })

  return filtered
})

// Methods
const fetchRoles = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/roles')
    roles.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Error fetching roles:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/roles/stats')
    stats.value = response.data || stats.value
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const fetchAvailablePermissions = async () => {
  try {
    const response = await axios.get('/api/roles/available-permissions')
    availablePermissions.value = response.data || []
  } catch (error) {
    console.error('Error fetching permissions:', error)
  }
}

const toggleDropdown = (roleId) => {
  activeDropdown.value = activeDropdown.value === roleId ? null : roleId
}

const openCreateModal = () => {
  selectedRole.value = null
  isEditMode.value = false
  showRoleModal.value = true
  activeDropdown.value = null
}

const openEditModal = (role) => {
  selectedRole.value = role
  isEditMode.value = true
  showRoleModal.value = true
  activeDropdown.value = null
}

const openPermissionsModal = (role) => {
  selectedRole.value = role
  showPermissionsModal.value = true
  activeDropdown.value = null
}

const closeRoleModal = () => {
  showRoleModal.value = false
  selectedRole.value = null
  isEditMode.value = false
}

const closePermissionsModal = () => {
  showPermissionsModal.value = false
  selectedRole.value = null
}

const handleRoleSave = async (roleData) => {
  try {
    if (isEditMode.value) {
      await axios.put(`/api/roles/${selectedRole.value.id}`, roleData)
    } else {
      await axios.post('/api/roles', roleData)
    }
    
    closeRoleModal()
    await fetchRoles()
    await fetchStats()
  } catch (error) {
    console.error('Error saving role:', error)
  }
}

const handlePermissionsSave = async (permissionData) => {
  try {
    await axios.post(`/api/roles/${selectedRole.value.id}/permissions/sync`, permissionData)
    
    closePermissionsModal()
    await fetchRoles()
  } catch (error) {
    console.error('Error saving permissions:', error)
  }
}

const cloneRole = async (role) => {
  try {
    await axios.post(`/api/roles/${role.id}/clone`, {
      name: `${role.name} - Copia`
    })
    
    await fetchRoles()
    await fetchStats()
  } catch (error) {
    console.error('Error cloning role:', error)
  }
}

const confirmDelete = (role) => {
  if (confirm(`¿Estás seguro de que quieres eliminar el rol "${role.name}"?`)) {
    deleteRole(role)
  }
  activeDropdown.value = null
}

const deleteRole = async (role) => {
  try {
    await axios.delete(`/api/roles/${role.id}`)
    await fetchRoles()
    await fetchStats()
  } catch (error) {
    console.error('Error deleting role:', error)
  }
}

// Click outside to close dropdown
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    activeDropdown.value = null
  }
}

// Lifecycle
onMounted(() => {
  fetchRoles()
  fetchStats()
  fetchAvailablePermissions()
  document.addEventListener('click', handleClickOutside)
})

// Cleanup
onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>