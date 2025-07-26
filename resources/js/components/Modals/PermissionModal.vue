<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="handleBackdropClick">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            {{ isEdit ? 'Editar Permiso' : 'Nuevo Permiso' }}
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
              Nombre del permiso <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.name ? 'border-red-300' : 'border-gray-300'"
              placeholder="ej: users.create, products.view"
            />
            <p class="mt-1 text-xs text-gray-500">
              Usa el formato: modulo.accion (ej: users.create, products.edit)
            </p>
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">
              {{ errors.name[0] }}
            </p>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
              Descripción <span class="text-red-500">*</span>
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              required
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.description ? 'border-red-300' : 'border-gray-300'"
              placeholder="Describe qué permite hacer este permiso..."
            ></textarea>
            <p v-if="errors.description" class="mt-1 text-sm text-red-600">
              {{ errors.description[0] }}
            </p>
          </div>

          <!-- Module -->
          <div>
            <label for="module" class="block text-sm font-medium text-gray-700 mb-1">
              Módulo <span class="text-red-500">*</span>
            </label>
            <div class="flex space-x-2">
              <select
                id="module"
                v-model="form.module"
                required
                class="flex-1 px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                :class="errors.module ? 'border-red-300' : 'border-gray-300'"
              >
                <option value="">Seleccionar módulo</option>
                <option v-for="module in availableModules" :key="module" :value="module">
                  {{ module }}
                </option>
                <option value="__custom__">Otro (personalizado)</option>
              </select>
              <input
                v-if="form.module === '__custom__'"
                v-model="form.customModule"
                type="text"
                placeholder="Nombre del módulo"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
            <p v-if="errors.module" class="mt-1 text-sm text-red-600">
              {{ errors.module[0] }}
            </p>
          </div>

          <!-- Permission Templates -->
          <div v-if="!isEdit" class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Plantillas de permisos</h4>
            <div class="space-y-2">
              <button
                type="button"
                @click="applyTemplate('view')"
                class="w-full text-left px-3 py-2 text-sm bg-white border border-gray-200 rounded hover:bg-gray-50"
              >
                <strong>Ver:</strong> Permite ver/listar elementos
              </button>
              <button
                type="button"
                @click="applyTemplate('create')"
                class="w-full text-left px-3 py-2 text-sm bg-white border border-gray-200 rounded hover:bg-gray-50"
              >
                <strong>Crear:</strong> Permite crear nuevos elementos
              </button>
              <button
                type="button"
                @click="applyTemplate('edit')"
                class="w-full text-left px-3 py-2 text-sm bg-white border border-gray-200 rounded hover:bg-gray-50"
              >
                <strong>Editar:</strong> Permite modificar elementos existentes
              </button>
              <button
                type="button"
                @click="applyTemplate('delete')"
                class="w-full text-left px-3 py-2 text-sm bg-white border border-gray-200 rounded hover:bg-gray-50"
              >
                <strong>Eliminar:</strong> Permite eliminar elementos
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
              :disabled="loading"
            >
              <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
              {{ isEdit ? 'Actualizar' : 'Crear' }} Permiso
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  permission: {
    type: Object,
    default: null
  },
  isEdit: {
    type: Boolean,
    default: false
  },
  availableModules: {
    type: Array,
    default: () => ['users', 'roles', 'permissions', 'products', 'categories', 'clients', 'services', 'invoices']
  }
})

const emit = defineEmits(['close', 'save'])

// State
const loading = ref(false)
const errors = ref({})

const form = ref({
  name: '',
  description: '',
  module: '',
  customModule: ''
})

// Computed
const finalModule = computed(() => {
  return form.value.module === '__custom__' ? form.value.customModule : form.value.module
})

// Methods
const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    // Client-side validation
    if (!form.value.name.trim()) {
      errors.value.name = ['El nombre del permiso es requerido']
      return
    }

    if (!form.value.description.trim()) {
      errors.value.description = ['La descripción es requerida']
      return
    }

    if (!finalModule.value.trim()) {
      errors.value.module = ['El módulo es requerido']
      return
    }

    // Validate permission name format
    const namePattern = /^[a-z_]+\.[a-z_]+$/
    if (!namePattern.test(form.value.name)) {
      errors.value.name = ['El nombre debe tener el formato: modulo.accion (ej: users.create)']
      return
    }

    // Prepare data for submission
    const permissionData = {
      name: form.value.name.trim(),
      description: form.value.description.trim(),
      module: finalModule.value.trim()
    }

    emit('save', permissionData)
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    }
  } finally {
    loading.value = false
  }
}

const applyTemplate = (action) => {
  const moduleBase = finalModule.value || 'module'
  
  const templates = {
    view: {
      name: `${moduleBase}.view`,
      description: `Ver y listar ${moduleBase}`
    },
    create: {
      name: `${moduleBase}.create`,
      description: `Crear nuevos ${moduleBase}`
    },
    edit: {
      name: `${moduleBase}.edit`,
      description: `Editar ${moduleBase} existentes`
    },
    delete: {
      name: `${moduleBase}.delete`,
      description: `Eliminar ${moduleBase}`
    }
  }

  if (templates[action]) {
    form.value.name = templates[action].name
    form.value.description = templates[action].description
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
    module: '',
    customModule: ''
  }
  errors.value = {}
}

// Watchers
watch(() => props.permission, (newPermission) => {
  if (newPermission && props.isEdit) {
    form.value.name = newPermission.name || ''
    form.value.description = newPermission.description || ''
    form.value.module = newPermission.module || ''
    form.value.customModule = ''
  } else {
    resetForm()
  }
}, { immediate: true })

watch(() => form.value.module, (newModule) => {
  if (newModule && newModule !== '__custom__' && !props.isEdit) {
    // Auto-update name when module changes
    if (form.value.name.includes('.')) {
      const action = form.value.name.split('.')[1]
      form.value.name = `${newModule}.${action}`
    }
  }
})

// Lifecycle
onMounted(() => {
  if (props.permission && props.isEdit) {
    form.value.name = props.permission.name || ''
    form.value.description = props.permission.description || ''
    form.value.module = props.permission.module || ''
  }
})
</script>