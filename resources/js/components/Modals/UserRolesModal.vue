<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="handleBackdropClick">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Gestionar Roles
            </h3>
            <p class="text-sm text-gray-500">
              Usuario: {{ user?.name }}
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Current Roles -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-gray-900 mb-2">Roles actuales</h4>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="role in currentRoles"
              :key="role.id"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
              {{ role.name }}
              <button
                @click="removeRole(role.id)"
                class="ml-1 text-blue-600 hover:text-blue-800"
                title="Quitar rol"
              >
                <i class="fas fa-times text-xs"></i>
              </button>
            </span>
            <span v-if="currentRoles.length === 0" class="text-sm text-gray-400">
              Sin roles asignados
            </span>
          </div>
        </div>

        <!-- Available Roles -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-gray-900 mb-2">Roles disponibles</h4>
          <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-md">
            <div v-if="availableRolesToAdd.length === 0" class="p-4 text-center text-gray-500">
              Todos los roles están asignados
            </div>
            <div v-else class="divide-y divide-gray-200">
              <div
                v-for="role in availableRolesToAdd"
                :key="role.id"
                class="p-3 hover:bg-gray-50 cursor-pointer flex items-center justify-between"
                @click="addRole(role)"
              >
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ role.name }}</p>
                  <p v-if="role.description" class="text-xs text-gray-500">
                    {{ role.description }}
                  </p>
                  <div v-if="role.permissions_count" class="mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                      {{ role.permissions_count }} permisos
                    </span>
                  </div>
                </div>
                <button
                  class="text-green-600 hover:text-green-800"
                  title="Asignar rol"
                >
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Role Details -->
        <div v-if="selectedRole" class="mb-6 p-4 bg-gray-50 rounded-lg">
          <h5 class="text-sm font-medium text-gray-900 mb-2">
            {{ selectedRole.name }}
          </h5>
          <p v-if="selectedRole.description" class="text-sm text-gray-600 mb-3">
            {{ selectedRole.description }}
          </p>
          <div v-if="selectedRole.permissions?.length" class="space-y-2">
            <h6 class="text-xs font-medium text-gray-700 uppercase tracking-wider">
              Permisos incluidos:
            </h6>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="permission in selectedRole.permissions"
                :key="permission.id"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-white text-gray-700 border"
              >
                {{ permission.name }}
              </span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
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
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  availableRoles: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'save'])

// State
const loading = ref(false)
const currentRoles = ref([])
const originalRoleIds = ref([])
const selectedRole = ref(null)

// Computed
const availableRolesToAdd = computed(() => {
  const currentRoleIds = currentRoles.value.map(role => role.id)
  return props.availableRoles.filter(role => !currentRoleIds.includes(role.id))
})

const hasChanges = computed(() => {
  const currentIds = currentRoles.value.map(role => role.id).sort()
  const originalIds = [...originalRoleIds.value].sort()
  return JSON.stringify(currentIds) !== JSON.stringify(originalIds)
})

// Methods
const loadUserRoles = async () => {
  if (!props.user?.id) return
  
  try {
    loading.value = true
    // In a real app, this would be an API call
    // For now, we'll use the user's roles from props
    currentRoles.value = [...(props.user.roles || [])]
    originalRoleIds.value = currentRoles.value.map(role => role.id)
  } catch (error) {
    console.error('Error loading user roles:', error)
  } finally {
    loading.value = false
  }
}

const addRole = (role) => {
  if (!currentRoles.value.find(r => r.id === role.id)) {
    currentRoles.value.push({ ...role })
  }
  selectedRole.value = role
}

const removeRole = (roleId) => {
  currentRoles.value = currentRoles.value.filter(role => role.id !== roleId)
  if (selectedRole.value?.id === roleId) {
    selectedRole.value = null
  }
}

const handleSave = () => {
  if (!hasChanges.value) return
  
  const roleIds = currentRoles.value.map(role => role.id)
  emit('save', { roles: roleIds })
}

const handleBackdropClick = (event) => {
  if (event.target === event.currentTarget) {
    emit('close')
  }
}

// Watchers
watch(() => props.user, (newUser) => {
  if (newUser) {
    loadUserRoles()
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  if (props.user) {
    loadUserRoles()
  }
})
</script>