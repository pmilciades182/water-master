<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="handleBackdropClick">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Crear Permisos CRUD
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Description -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                Esta herramienta creará automáticamente los 4 permisos básicos CRUD (Crear, Leer, Actualizar, Eliminar) 
                para el módulo especificado.
              </p>
            </div>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Module Name -->
          <div>
            <label for="module" class="block text-sm font-medium text-gray-700 mb-1">
              Nombre del módulo <span class="text-red-500">*</span>
            </label>
            <input
              id="module"
              v-model="form.module"
              type="text"
              required
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.module ? 'border-red-300' : 'border-gray-300'"
              placeholder="ej: invoices, clients, services"
            />
            <p class="mt-1 text-xs text-gray-500">
              Solo minúsculas, sin espacios. Usa el plural (ej: invoices, clients)
            </p>
            <p v-if="errors.module" class="mt-1 text-sm text-red-600">
              {{ errors.module[0] }}
            </p>
          </div>

          <!-- Module Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
              Descripción del módulo <span class="text-red-500">*</span>
            </label>
            <input
              id="description"
              v-model="form.description"
              type="text"
              required
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.description ? 'border-red-300' : 'border-gray-300'"
              placeholder="ej: Gestión de facturas, Gestión de clientes"
            />
            <p v-if="errors.description" class="mt-1 text-sm text-red-600">
              {{ errors.description[0] }}
            </p>
          </div>

          <!-- Permissions Preview -->
          <div v-if="form.module" class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Permisos que se crearán:</h4>
            <div class="space-y-2">
              <div
                v-for="permission in previewPermissions"
                :key="permission.name"
                class="flex items-center justify-between p-2 bg-white rounded border"
              >
                <div>
                  <span class="text-sm font-medium text-gray-900">{{ permission.name }}</span>
                  <p class="text-xs text-gray-500">{{ permission.description }}</p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                  {{ permission.action }}
                </span>
              </div>
            </div>
          </div>

          <!-- Custom Actions -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Acciones personalizadas (opcional)
            </label>
            <div class="space-y-2">
              <div
                v-for="(action, index) in customActions"
                :key="index"
                class="flex items-center space-x-2"
              >
                <input
                  v-model="action.name"
                  type="text"
                  placeholder="Nombre de la acción"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                />
                <input
                  v-model="action.description"
                  type="text"
                  placeholder="Descripción"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                />
                <button
                  type="button"
                  @click="removeCustomAction(index)"
                  class="text-red-600 hover:text-red-800"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <button
                type="button"
                @click="addCustomAction"
                class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs leading-4 font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
              >
                <i class="fas fa-plus mr-1"></i>
                Agregar acción
              </button>
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
              :disabled="loading || !form.module || !form.description"
            >
              <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
              Crear {{ totalPermissions }} Permisos
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const emit = defineEmits(['close', 'save'])

// State
const loading = ref(false)
const errors = ref({})

const form = ref({
  module: '',
  description: ''
})

const customActions = ref([])

// Computed
const previewPermissions = computed(() => {
  if (!form.value.module) return []
  
  const module = form.value.module.toLowerCase()
  const basePermissions = [
    {
      name: `${module}.view`,
      description: `Ver y listar ${form.value.description.toLowerCase()}`,
      action: 'Ver'
    },
    {
      name: `${module}.create`,
      description: `Crear nuevos ${form.value.description.toLowerCase()}`,
      action: 'Crear'
    },
    {
      name: `${module}.edit`,
      description: `Editar ${form.value.description.toLowerCase()} existentes`,
      action: 'Editar'
    },
    {
      name: `${module}.delete`,
      description: `Eliminar ${form.value.description.toLowerCase()}`,
      action: 'Eliminar'
    }
  ]

  const customPerms = customActions.value
    .filter(action => action.name && action.description)
    .map(action => ({
      name: `${module}.${action.name.toLowerCase().replace(/\s+/g, '_')}`,
      description: action.description,
      action: action.name
    }))

  return [...basePermissions, ...customPerms]
})

const totalPermissions = computed(() => {
  return previewPermissions.value.length
})

// Methods
const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    // Client-side validation
    if (!form.value.module.trim()) {
      errors.value.module = ['El nombre del módulo es requerido']
      return
    }

    if (!form.value.description.trim()) {
      errors.value.description = ['La descripción del módulo es requerida']
      return
    }

    // Validate module name format
    const modulePattern = /^[a-z_]+$/
    if (!modulePattern.test(form.value.module)) {
      errors.value.module = ['El nombre del módulo solo puede contener minúsculas y guiones bajos']
      return
    }

    // Prepare data for submission
    const crudData = {
      module: form.value.module.toLowerCase().trim(),
      description: form.value.description.trim(),
      custom_actions: customActions.value
        .filter(action => action.name && action.description)
        .map(action => ({
          name: action.name.toLowerCase().replace(/\s+/g, '_'),
          description: action.description
        }))
    }

    emit('save', crudData)
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

const addCustomAction = () => {
  customActions.value.push({ name: '', description: '' })
}

const removeCustomAction = (index) => {
  customActions.value.splice(index, 1)
}

const handleBackdropClick = (event) => {
  if (event.target === event.currentTarget) {
    emit('close')
  }
}

// Watchers
watch(() => form.value.module, (newModule) => {
  if (newModule) {
    form.value.module = newModule.toLowerCase().replace(/[^a-z_]/g, '')
  }
})
</script>