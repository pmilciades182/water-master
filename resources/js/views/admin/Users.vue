<template>
  <MainLayout page-title="Gestión de Usuarios">
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
            <p class="mt-1 text-sm text-gray-600">
              Gestiona los usuarios de {{ authStore.companyName }}
            </p>
          </div>
          <div class="mt-4 sm:mt-0">
            <button
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <i class="fas fa-plus mr-2"></i>
              Nuevo Usuario
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Buscar
              </label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Nombre o email..."
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Estado
              </label>
              <select
                v-model="filters.status"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
                <option value="">Todos</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Rol
              </label>
              <select
                v-model="filters.role"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              >
                <option value="">Todos los roles</option>
                <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <div v-if="loading" class="p-8 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
            <p class="mt-2 text-gray-500">Cargando usuarios...</p>
          </div>

          <div v-else-if="users.length === 0" class="p-8 text-center">
            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">No hay usuarios</h3>
            <p class="text-gray-500">Comienza creando tu primer usuario.</p>
          </div>

          <div v-else>
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Usuario
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Roles
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Último acceso
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="h-10 w-10 bg-gray-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-600"></i>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">
                          {{ user.name }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ user.email }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="role in user.roles"
                        :key="role.id"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                      >
                        {{ role.name }}
                      </span>
                      <span v-if="!user.roles?.length" class="text-sm text-gray-400">
                        Sin roles
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    >
                      {{ user.is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(user.last_login_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end space-x-2">
                      <button
                        @click="openRolesModal(user)"
                        class="text-blue-600 hover:text-blue-900"
                        title="Gestionar roles"
                      >
                        <i class="fas fa-user-shield"></i>
                      </button>
                      <button
                        @click="openEditModal(user)"
                        class="text-indigo-600 hover:text-indigo-900"
                        title="Editar usuario"
                      >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button
                        @click="confirmDelete(user)"
                        class="text-red-600 hover:text-red-900"
                        title="Eliminar usuario"
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

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="mt-6 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }} usuarios
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
            >
              Anterior
            </button>
            <span class="px-3 py-1 text-sm">
              Página {{ pagination.current_page }} de {{ pagination.last_page }}
            </span>
            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
            >
              Siguiente
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit User Modal -->
    <UserModal
      v-if="showUserModal"
      :user="selectedUser"
      :is-edit="isEditMode"
      @close="closeUserModal"
      @save="handleUserSave"
    />

    <!-- Roles Management Modal -->
    <UserRolesModal
      v-if="showRolesModal"
      :user="selectedUser"
      :available-roles="availableRoles"
      @close="closeRolesModal"
      @save="handleRolesSave"
    />
  </MainLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import MainLayout from '../../components/Layout/MainLayout.vue'
import UserModal from '../../components/Modals/UserModal.vue'
import UserRolesModal from '../../components/Modals/UserRolesModal.vue'
import axios from 'axios'

const authStore = useAuthStore()

// State
const users = ref([])
const availableRoles = ref([])
const loading = ref(false)
const showUserModal = ref(false)
const showRolesModal = ref(false)
const selectedUser = ref(null)
const isEditMode = ref(false)

const filters = ref({
  search: '',
  status: '',
  role: ''
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0
})

// Computed
const filteredUsers = computed(() => {
  // This will be handled by API in real implementation
  return users.value
})

// Methods
const fetchUsers = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
      ...filters.value
    }
    
    const response = await axios.get('/api/users', { params })
    users.value = response.data.data || []
    
    if (response.data.meta) {
      pagination.value = response.data.meta
    }
  } catch (error) {
    console.error('Error fetching users:', error)
    // Show error notification
  } finally {
    loading.value = false
  }
}

const fetchAvailableRoles = async () => {
  try {
    const response = await axios.get('/api/user-roles/available-roles')
    availableRoles.value = response.data || []
  } catch (error) {
    console.error('Error fetching roles:', error)
  }
}

const openCreateModal = () => {
  selectedUser.value = null
  isEditMode.value = false
  showUserModal.value = true
}

const openEditModal = (user) => {
  selectedUser.value = user
  isEditMode.value = true
  showUserModal.value = true
}

const openRolesModal = (user) => {
  selectedUser.value = user
  showRolesModal.value = true
}

const closeUserModal = () => {
  showUserModal.value = false
  selectedUser.value = null
  isEditMode.value = false
}

const closeRolesModal = () => {
  showRolesModal.value = false
  selectedUser.value = null
}

const handleUserSave = async (userData) => {
  try {
    if (isEditMode.value) {
      await axios.put(`/api/users/${selectedUser.value.id}`, userData)
    } else {
      await axios.post('/api/users', userData)
    }
    
    closeUserModal()
    await fetchUsers(pagination.value.current_page)
    // Show success notification
  } catch (error) {
    console.error('Error saving user:', error)
    // Show error notification
  }
}

const handleRolesSave = async (roleData) => {
  try {
    await axios.post('/api/user-roles/sync', {
      user_id: selectedUser.value.id,
      roles: roleData.roles
    })
    
    closeRolesModal()
    await fetchUsers(pagination.value.current_page)
    // Show success notification
  } catch (error) {
    console.error('Error saving user roles:', error)
    // Show error notification
  }
}

const confirmDelete = (user) => {
  if (confirm(`¿Estás seguro de que quieres eliminar al usuario ${user.name}?`)) {
    deleteUser(user)
  }
}

const deleteUser = async (user) => {
  try {
    await axios.delete(`/api/users/${user.id}`)
    await fetchUsers(pagination.value.current_page)
    // Show success notification
  } catch (error) {
    console.error('Error deleting user:', error)
    // Show error notification
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchUsers(page)
  }
}

const formatDate = (date) => {
  if (!date) return 'Nunca'
  return new Date(date).toLocaleDateString('es-PY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Watchers
watch(filters, () => {
  // Debounce search
  setTimeout(() => {
    fetchUsers(1)
  }, 300)
}, { deep: true })

// Lifecycle
onMounted(() => {
  fetchUsers()
  fetchAvailableRoles()
})
</script>