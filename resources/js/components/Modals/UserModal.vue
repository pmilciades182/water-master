<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="handleBackdropClick">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white" @click.stop>
      <div class="mt-3">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            {{ isEdit ? 'Editar Usuario' : 'Nuevo Usuario' }}
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
              Nombre completo <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.name ? 'border-red-300' : 'border-gray-300'"
              placeholder="Ingresa el nombre completo"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">
              {{ errors.name[0] }}
            </p>
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
              Correo electrónico <span class="text-red-500">*</span>
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.email ? 'border-red-300' : 'border-gray-300'"
              placeholder="usuario@empresa.com"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">
              {{ errors.email[0] }}
            </p>
          </div>

          <!-- Password (only for new users or when changing) -->
          <div v-if="!isEdit || showPasswordFields">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
              Contraseña <span class="text-red-500" v-if="!isEdit">*</span>
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                :required="!isEdit"
                class="block w-full px-3 py-2 pr-10 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                :class="errors.password ? 'border-red-300' : 'border-gray-300'"
                placeholder="Mínimo 8 caracteres"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-gray-400"></i>
              </button>
            </div>
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password[0] }}
            </p>
          </div>

          <!-- Password Confirmation -->
          <div v-if="(!isEdit || showPasswordFields) && form.password">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
              Confirmar contraseña <span class="text-red-500">*</span>
            </label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              :required="!isEdit && form.password"
              class="block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              :class="errors.password_confirmation ? 'border-red-300' : 'border-gray-300'"
              placeholder="Repite la contraseña"
            />
            <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">
              {{ errors.password_confirmation[0] }}
            </p>
          </div>

          <!-- Change password toggle for edit mode -->
          <div v-if="isEdit && !showPasswordFields">
            <button
              type="button"
              @click="showPasswordFields = true"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              <i class="fas fa-key mr-1"></i>
              Cambiar contraseña
            </button>
          </div>

          <!-- Status -->
          <div>
            <label class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              />
              <span class="ml-2 text-sm text-gray-700">Usuario activo</span>
            </label>
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
              {{ isEdit ? 'Actualizar' : 'Crear' }} Usuario
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  user: {
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
const showPassword = ref(false)
const showPasswordFields = ref(false)
const errors = ref({})

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  is_active: true
})

// Methods
const handleSubmit = async () => {
  loading.value = true
  errors.value = {}

  try {
    // Client-side validation
    if (!form.value.name.trim()) {
      errors.value.name = ['El nombre es requerido']
      return
    }

    if (!form.value.email.trim()) {
      errors.value.email = ['El email es requerido']
      return
    }

    if (!props.isEdit && !form.value.password) {
      errors.value.password = ['La contraseña es requerida']
      return
    }

    if (form.value.password && form.value.password !== form.value.password_confirmation) {
      errors.value.password_confirmation = ['Las contraseñas no coinciden']
      return
    }

    // Prepare data for submission
    const userData = {
      name: form.value.name,
      email: form.value.email,
      is_active: form.value.is_active
    }

    // Only include password if it's provided
    if (form.value.password) {
      userData.password = form.value.password
      userData.password_confirmation = form.value.password_confirmation
    }

    emit('save', userData)
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
    email: '',
    password: '',
    password_confirmation: '',
    is_active: true
  }
  errors.value = {}
  showPasswordFields.value = false
  showPassword.value = false
}

// Watchers
watch(() => props.user, (newUser) => {
  if (newUser && props.isEdit) {
    form.value.name = newUser.name || ''
    form.value.email = newUser.email || ''
    form.value.is_active = newUser.is_active !== false
    form.value.password = ''
    form.value.password_confirmation = ''
  } else {
    resetForm()
  }
}, { immediate: true })

// Lifecycle
onMounted(() => {
  if (props.user && props.isEdit) {
    form.value.name = props.user.name || ''
    form.value.email = props.user.email || ''
    form.value.is_active = props.user.is_active !== false
  }
})
</script>