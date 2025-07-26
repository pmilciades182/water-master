<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="handleBackdropClick">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Gestionar Permisos
            </h3>
            <p class="text-sm text-gray-500">
              Rol: {{ role?.name }}
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Search and Filter -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Buscar permisos
            </label>
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Nombre del permiso..."
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Filtrar por módulo
            </label>
            <select
              v-model="selectedModule"
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
              Estado
            </label>
            <select
              v-model="statusFilter"
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            >
              <option value="">Todos</option>
              <option value="assigned">Asignados</option>
              <option value="unassigned">No asignados</option>
            </select>
          </div>
        </div>

        <!-- Statistics -->
        <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-blue-50 p-3 rounded-lg text-center">
            <div class="text-lg font-semibold text-blue-900">{{ assignedPermissions.length }}</div>
            <div class="text-xs text-blue-600">Asignados</div>
          </div>
          <div class="bg-gray-50 p-3 rounded-lg text-center">
            <div class="text-lg font-semibold text-gray-900">{{ availablePermissions.length }}</div>
            <div class="text-xs text-gray-600">Total</div>
          </div>
          <div class="bg-green-50 p-3 rounded-lg text-center">
            <div class="text-lg font-semibold text-green-900">{{ filteredPermissions.length }}</div>
            <div class="text-xs text-green-600">Mostrados</div>
          </div>
          <div class="bg-yellow-50 p-3 rounded-lg text-center">
            <div class="text-lg font-semibold text-yellow-900">{{ changesCount }}</div>
            <div class="text-xs text-yellow-600">Cambios</div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-6 flex flex-wrap gap-2">
          <button
            @click="selectAllVisible"
            class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs leading-4 font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
          >
            <i class="fas fa-check-square mr-1"></i>
            Seleccionar visibles
          </button>
          <button
            @click="deselectAllVisible"
            class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs leading-4 font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
          >
            <i class="fas fa-square mr-1"></i>
            Deseleccionar visibles
          </button>
          <button
            @click="toggleModulePermissions('users')"
            class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs leading-4 font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
          >
            <i class="fas fa-users mr-1"></i>
            Toggle Usuarios
          </button>
          <button
            @click="toggleModulePermissions('products')"
            class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs leading-4 font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
          >
            <i class="fas fa-boxes mr-1"></i>
            Toggle Productos
          </button>
        </div>

        <!-- Permissions List -->
        <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-md">
          <div v-if="filteredPermissions.length === 0" class="p-8 text-center text-gray-500">
            No se encontraron permisos que coincidan con los filtros.
          </div>
          <div v-else class="divide-y divide-gray-200">
            <div
              v-for="permission in groupedFilteredPermissions"
              :key="permission.module"
              class="p-4"
            >
              <!-- Module Header -->
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-medium text-gray-900 capitalize">
                  {{ permission.module }}
                  <span class="ml-2 text-xs text-gray-500">
                    ({{ permission.permissions.length }} permisos)
                  </span>
                </h4>
                <button
                  @click="toggleModulePermissions(permission.module)"
                  class="text-xs text-blue-600 hover:text-blue-800"
                >
                  {{ isModuleFullySelected(permission.module) ? 'Deseleccionar' : 'Seleccionar' }} todos
                </button>
              </div>

              <!-- Module Permissions -->
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                <label
                  v-for="perm in permission.permissions"
                  :key="perm.id"
                  class="flex items-center p-2 rounded border cursor-pointer transition-colors"
                  :class="isPermissionSelected(perm.id) ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200 hover:bg-gray-100'"
                >
                  <input
                    v-model="selectedPermissionIds"
                    :value="perm.id"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                  />
                  <div class="ml-2 flex-1">
                    <div class="text-sm font-medium text-gray-900">
                      {{ perm.name }}
                    </div>
                    <div v-if="perm.description" class="text-xs text-gray-500">
                      {{ perm.description }}
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
          <div class="text-sm text-gray-500">
            {{ assignedPermissions.length }} de {{ availablePermissions.length }} permisos asignados
          </div>
          <div class="flex items-center space-x-3">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :disabled="loading"
            >
              Cancelar
            </button>
            <button
              type="button"
              @click="handleSave"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loading || !hasChanges"
            >
              <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
              Guardar Cambios
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  role: {
    type: Object,
    required: true
  },
  availablePermissions: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'save'])

// State
const loading = ref(false)
const selectedPermissionIds = ref([])
const originalPermissionIds = ref([])
const searchTerm = ref('')
const selectedModule = ref('')
const statusFilter = ref('')

// Computed
const availableModules = computed(() => {
  const modules = [...new Set(props.availablePermissions.map(p => p.module))]
  return modules.sort()
})

const assignedPermissions = computed(() => {
  return props.availablePermissions.filter(p => selectedPermissionIds.value.includes(p.id))
})

const filteredPermissions = computed(() => {
  let filtered = [...props.availablePermissions]

  // Search filter
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(permission =>
      permission.name.toLowerCase().includes(search) ||
      permission.description?.toLowerCase().includes(search)
    )
  }

  // Module filter
  if (selectedModule.value) {
    filtered = filtered.filter(permission => permission.module === selectedModule.value)
  }

  // Status filter
  if (statusFilter.value === 'assigned') {
    filtered = filtered.filter(permission => selectedPermissionIds.value.includes(permission.id))
  } else if (statusFilter.value === 'unassigned') {
    filtered = filtered.filter(permission => !selectedPermissionIds.value.includes(permission.id))
  }

  return filtered.sort((a, b) => a.name.localeCompare(b.name))
})

const groupedFilteredPermissions = computed(() => {
  const grouped = {}
  
  filteredPermissions.value.forEach(permission => {
    if (!grouped[permission.module]) {
      grouped[permission.module] = []
    }
    grouped[permission.module].push(permission)
  })

  return Object.keys(grouped).sort().map(module => ({
    module,
    permissions: grouped[module].sort((a, b) => a.name.localeCompare(b.name))
  }))
})

const hasChanges = computed(() => {
  const currentIds = [...selectedPermissionIds.value].sort()
  const originalIds = [...originalPermissionIds.value].sort()
  return JSON.stringify(currentIds) !== JSON.stringify(originalIds)
})

const changesCount = computed(() => {
  const added = selectedPermissionIds.value.filter(id => !originalPermissionIds.value.includes(id))
  const removed = originalPermissionIds.value.filter(id => !selectedPermissionIds.value.includes(id))
  return added.length + removed.length
})

// Methods
const loadRolePermissions = () => {
  if (!props.role?.permissions) return
  
  const permissionIds = props.role.permissions.map(p => p.id)
  selectedPermissionIds.value = [...permissionIds]
  originalPermissionIds.value = [...permissionIds]
}

const isPermissionSelected = (permissionId) => {
  return selectedPermissionIds.value.includes(permissionId)
}

const isModuleFullySelected = (module) => {
  const modulePermissions = props.availablePermissions.filter(p => p.module === module)
  return modulePermissions.every(p => selectedPermissionIds.value.includes(p.id))
}

const selectAllVisible = () => {
  const visibleIds = filteredPermissions.value.map(p => p.id)
  const newIds = [...new Set([...selectedPermissionIds.value, ...visibleIds])]
  selectedPermissionIds.value = newIds
}

const deselectAllVisible = () => {
  const visibleIds = filteredPermissions.value.map(p => p.id)
  selectedPermissionIds.value = selectedPermissionIds.value.filter(id => !visibleIds.includes(id))
}

const toggleModulePermissions = (module) => {
  const modulePermissions = props.availablePermissions.filter(p => p.module === module)
  const moduleIds = modulePermissions.map(p => p.id)
  
  const allSelected = moduleIds.every(id => selectedPermissionIds.value.includes(id))
  
  if (allSelected) {
    // Remove all module permissions
    selectedPermissionIds.value = selectedPermissionIds.value.filter(id => !moduleIds.includes(id))
  } else {
    // Add all module permissions
    const newIds = [...new Set([...selectedPermissionIds.value, ...moduleIds])]
    selectedPermissionIds.value = newIds
  }
}

const handleSave = () => {
  if (!hasChanges.value) return
  
  emit('save', { permissions: selectedPermissionIds.value })
}

const handleBackdropClick = (event) => {
  if (event.target === event.currentTarget) {
    emit('close')
  }
}

// Watchers
watch(() => props.role, (newRole) => {
  if (newRole) {
    loadRolePermissions()
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  if (props.role) {
    loadRolePermissions()
  }
})
</script>