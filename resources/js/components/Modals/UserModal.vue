<template>
  <div class="ms-modal-overlay" @click="handleBackdropClick">
    <div class="ms-modal" @click.stop>
      <div class="ms-modal-header">
        <h3 class="ms-modal-title">
          {{ isEdit ? 'Editar Usuario' : 'Nuevo Usuario' }}
        </h3>
        <button
          @click="$emit('close')"
          class="ms-button-subtle"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="ms-modal-body">

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Name -->
          <div class="ms-form-group">
            <label for="name" class="ms-label ms-label-required">
              Nombre completo
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="ms-input"
              :class="errors.name ? 'ms-input-error' : ''"
              placeholder="Ingresa el nombre completo"
            />
            <p v-if="errors.name" class="ms-form-error">
              {{ errors.name[0] }}
            </p>
          </div>

          <!-- Email -->
          <div class="ms-form-group">
            <label for="email" class="ms-label ms-label-required">
              Correo electrónico
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              class="ms-input"
              :class="errors.email ? 'ms-input-error' : ''"
              placeholder="usuario@empresa.com"
            />
            <p v-if="errors.email" class="ms-form-error">
              {{ errors.email[0] }}
            </p>
          </div>

          <!-- Password (only for new users or when changing) -->
          <div v-if="!isEdit || showPasswordFields" class="ms-form-group">
            <label for="password" class="ms-label" :class="!isEdit ? 'ms-label-required' : ''">
              Contraseña
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                :required="!isEdit"
                class="ms-input pr-10"
                :class="errors.password ? 'ms-input-error' : ''"
                placeholder="Mínimo 8 caracteres"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center ms-button-subtle"
              >
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
            <p v-if="errors.password" class="ms-form-error">
              {{ errors.password[0] }}
            </p>
          </div>

          <!-- Password Confirmation -->
          <div v-if="(!isEdit || showPasswordFields) && form.password" class="ms-form-group">
            <label for="password_confirmation" class="ms-label ms-label-required">
              Confirmar contraseña
            </label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              :required="!isEdit && form.password"
              class="ms-input"
              :class="errors.password_confirmation ? 'ms-input-error' : ''"
              placeholder="Repite la contraseña"
            />
            <p v-if="errors.password_confirmation" class="ms-form-error">
              {{ errors.password_confirmation[0] }}
            </p>
          </div>

          <!-- Change password toggle for edit mode -->
          <div v-if="isEdit && !showPasswordFields">
            <button
              type="button"
              @click="showPasswordFields = true"
              class="ms-button-subtle ms-text-primary"
            >
              <i class="fas fa-key"></i>
              Cambiar contraseña
            </button>
          </div>

          <!-- Status -->
          <div class="ms-form-group">
            <label class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="border-color: var(--color-neutral-60); color: var(--color-primary-500); box-shadow: 0 0 0 1px var(--color-primary-500);"
              />
              <span class="ml-2 ms-font-body">Usuario activo</span>
            </label>
          </div>

        </form>
      </div>
      <div class="ms-modal-footer">
        <button
          type="button"
          @click="$emit('close')"
          class="ms-button ms-button-secondary"
          :disabled="loading"
        >
          Cancelar
        </button>
        <button
          type="submit"
          @click="handleSubmit"
          class="ms-button ms-button-primary"
          :disabled="loading"
        >
          <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
          {{ isEdit ? 'Actualizar' : 'Crear' }} Usuario
        </button>
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