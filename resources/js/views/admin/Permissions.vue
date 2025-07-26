<template>
  <MainLayout page-title="Gestión de Permisos">
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Permisos</h1>
            <p class="mt-1 text-sm text-gray-600">
              Gestiona los permisos del sistema para {{ authStore.companyName }}
            </p>
          </div>
          <div class="mt-4 sm:mt-0 flex space-x-3">
            <button
              @click="openCreateCrudModal"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <i class="fas fa-layer-group mr-2"></i>
              Crear CRUD
            </button>
            <button
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <i class="fas fa-plus mr-2"></i>
              Nuevo Permiso
            </button>
          </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-key text-2xl text-blue-500"></i>
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

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-cubes text-2xl text-green-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Módulos
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.total_modules }}
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
                  <i class="fas fa-user-shield text-2xl text-yellow-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Permisos Asignados
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.assigned_permissions }}
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
                  <i class="fas fa-users text-2xl text-purple-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Roles con Permisos
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.roles_with_permissions }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Buscar permisos
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
                Módulo
              </label>
              <select
                v-model="filters.module"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
                <option value="">Todos los módulos</option>
                <option v-for="module in availableModules" :key="module" :value="module">
                  {{ module }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Vista
              </label>
              <select
                v-model="viewMode"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
                <option value="grouped">Agrupado por módulo</option>
                <option value="list">Lista completa</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Permissions Display -->
        <div v-if="loading" class="text-center py-8">
          <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
          <p class="mt-2 text-gray-500">Cargando permisos...</p>
        </div>

        <!-- Grouped View -->
        <div v-else-if="viewMode === 'grouped'" class="space-y-6">
          <div
            v-for="(modulePermissions, module) in groupedPermissions"
            :key="module"
            class="bg-white shadow rounded-lg overflow-hidden"
          >
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900 capitalize">
                {{ module }}
                <span class="ml-2 text-sm text-gray-500">
                  ({{ modulePermissions.length }} permisos)
                </span>
              </h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                  v-for="permission in modulePermissions"
                  :key="permission.id"
                  class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
                >
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-900">
                      {{ permission.name }}
                    </span>
                    <div class="flex space-x-1">
                      <button
                        @click="openEditModal(permission)"
                        class="text-blue-600 hover:text-blue-800"
                        title="Editar"
                      >
                        <i class="fas fa-edit text-xs"></i>
                      </button>
                      <button
                        @click="confirmDelete(permission)"
                        class="text-red-600 hover:text-red-800"
                        title="Eliminar"
                      >
                        <i class="fas fa-trash text-xs"></i>
                      </button>
                    </div>
                  </div>
                  <p v-if="permission.description" class="text-xs text-gray-500 mb-2">
                    {{ permission.description }}
                  </p>
                  <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                      {{ permission.module }}
                    </span>
                    <span v-if="permission.roles_count" class="text-xs text-gray-500">
                      {{ permission.roles_count }} rol(es)
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- List View -->
        <div v-else class="bg-white shadow rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Permiso
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Módulo
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Roles Asignados
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="permission in filteredPermissions" :key="permission.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      {{ permission.name }}
                    </div>
                    <div v-if="permission.description" class="text-sm text-gray-500">
                      {{ permission.description }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                    {{ permission.module }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-900">
                    {{ permission.roles_count || 0 }} rol(es)
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end space-x-2">
                    <button
                      @click="openEditModal(permission)"
                      class="text-blue-600 hover:text-blue-900"
                      title="Editar"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      @click="confirmDelete(permission)"
                      class="text-red-600 hover:text-red-900"
                      title="Eliminar"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Permission Modal -->
    <PermissionModal
      v-if="showPermissionModal"
      :permission="selectedPermission"
      :is-edit="isEditMode"
      :available-modules="availableModules"
      @close="closePermissionModal"
      @save="handlePermissionSave"
    />

    <!-- CRUD Creation Modal -->
    <CreateCrudModal
      v-if="showCrudModal"
      @close="closeCrudModal"
      @save="handleCrudSave"
    />
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import MainLayout from '../../components/Layout/MainLayout.vue'
import PermissionModal from '../../components/Modals/PermissionModal.vue'
import CreateCrudModal from '../../components/Modals/CreateCrudModal.vue'
import axios from 'axios'

const authStore = useAuthStore()

// State
const permissions = ref([])
const loading = ref(false)
const showPermissionModal = ref(false)
const showCrudModal = ref(false)
const selectedPermission = ref(null)
const isEditMode = ref(false)
const viewMode = ref('grouped')

const stats = ref({
  total_permissions: 0,
  total_modules: 0,
  assigned_permissions: 0,
  roles_with_permissions: 0
})

const filters = ref({
  search: '',
  module: ''
})

// Computed
const availableModules = computed(() => {
  const modules = [...new Set(permissions.value.map(p => p.module))]
  return modules.sort()
})

const filteredPermissions = computed(() => {
  let filtered = [...permissions.value]

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(permission =>
      permission.name.toLowerCase().includes(search) ||
      permission.description?.toLowerCase().includes(search)
    )
  }

  if (filters.value.module) {
    filtered = filtered.filter(permission => permission.module === filters.value.module)
  }

  return filtered.sort((a, b) => a.name.localeCompare(b.name))
})

const groupedPermissions = computed(() => {
  const filtered = filteredPermissions.value
  const grouped = {}
  
  filtered.forEach(permission => {
    if (!grouped[permission.module]) {
      grouped[permission.module] = []
    }
    grouped[permission.module].push(permission)
  })

  // Sort permissions within each module
  Object.keys(grouped).forEach(module => {
    grouped[module].sort((a, b) => a.name.localeCompare(b.name))
  })

  return grouped
})

// Methods
const fetchPermissions = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/permissions')
    permissions.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Error fetching permissions:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const response = await axios.get('/api/permissions/stats')
    stats.value = response.data || stats.value
  } catch (error) {
    console.error('Error fetching stats:', error)
  }
}

const openCreateModal = () => {
  selectedPermission.value = null
  isEditMode.value = false
  showPermissionModal.value = true
}

const openEditModal = (permission) => {
  selectedPermission.value = permission
  isEditMode.value = true
  showPermissionModal.value = true
}

const openCreateCrudModal = () => {
  showCrudModal.value = true
}

const closePermissionModal = () => {
  showPermissionModal.value = false
  selectedPermission.value = null
  isEditMode.value = false
}

const closeCrudModal = () => {
  showCrudModal.value = false
}

const handlePermissionSave = async (permissionData) => {
  try {
    if (isEditMode.value) {
      await axios.put(`/api/permissions/${selectedPermission.value.id}`, permissionData)
    } else {
      await axios.post('/api/permissions', permissionData)
    }
    
    closePermissionModal()
    await fetchPermissions()
    await fetchStats()
  } catch (error) {
    console.error('Error saving permission:', error)
  }
}

const handleCrudSave = async (crudData) => {
  try {
    await axios.post('/api/permissions/create-crud', crudData)
    
    closeCrudModal()
    await fetchPermissions()
    await fetchStats()
  } catch (error) {
    console.error('Error creating CRUD permissions:', error)
  }
}

const confirmDelete = (permission) => {
  if (confirm(`¿Estás seguro de que quieres eliminar el permiso "${permission.name}"?`)) {
    deletePermission(permission)
  }
}

const deletePermission = async (permission) => {
  try {
    await axios.delete(`/api/permissions/${permission.id}`)
    await fetchPermissions()
    await fetchStats()
  } catch (error) {
    console.error('Error deleting permission:', error)
  }
}

// Lifecycle
onMounted(() => {
  fetchPermissions()
  fetchStats()
})
</script>