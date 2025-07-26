<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="handleBackdropClick">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            {{ isEdit ? 'Editar Rol' : 'Nuevo Rol' }}
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
              Nombre del rol <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.name ? 'border-red-300' : 'border-gray-300'"
              placeholder="Ej: Administrador, Técnico, etc."
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">
              {{ errors.name[0] }}
            </p>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
              Descripción
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.description ? 'border-red-300' : 'border-gray-300'"
              placeholder="Describe las responsabilidades de este rol..."
            ></textarea>
            <p v-if="errors.description" class="mt-1 text-sm text-red-600">
              {{ errors.description[0] }}
            </p>
          </div>

          <!-- Permissions Selection (Basic) -->
          <div v-if="!isEdit">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Permisos iniciales (opcional)
            </label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="form.includeBasicPermissions"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm text-gray-700">Incluir permisos básicos de visualización</span>
              </label>
              <p class="text-xs text-gray-500 ml-6">
                Incluye permisos para ver dashboard, usuarios y configuraciones básicas.
              </p>
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
              type="submit"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loading"
            >
              <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
              {{ isEdit ? 'Actualizar' : 'Crear' }} Rol
            </button>
          </div>
        </form>

        <!-- Post-creation notice -->
        <div v-if="!isEdit" class="mt-4 p-3 bg-blue-50 rounded-lg">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                Después de crear el rol, podrás asignar permisos específicos desde la vista de roles.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  role: {
    type: Object,
    default: null
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'save'])

// State
const loading = ref(false)
const errors = ref({})

const form = ref({
  name: '',
  description: '',
  includeBasicPermissions: false
})

// Methods
const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    // Client-side validation
    if (!form.value.name.trim()) {
      errors.value.name = ['El nombre del rol es requerido']
      return
    }

    // Prepare data for submission
    const roleData = {
      name: form.value.name.trim(),
      description: form.value.description.trim()
    }

    // Add basic permissions if requested for new roles
    if (!props.isEdit && form.value.includeBasicPermissions) {
      roleData.include_basic_permissions = true
    }

    emit('save', roleData)
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

const handleBackdropClick = (event) => {
  if (event.target === event.currentTarget) {
    emit('close')
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    includeBasicPermissions: false
  }
  errors.value = {}
}

// Watchers
watch(() => props.role, (newRole) => {
  if (newRole && props.isEdit) {
    form.value.name = newRole.name || ''
    form.value.description = newRole.description || ''
    form.value.includeBasicPermissions = false
  } else {
    resetForm()
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  if (props.role && props.isEdit) {
    form.value.name = props.role.name || ''
    form.value.description = props.role.description || ''
  }
})
</script>