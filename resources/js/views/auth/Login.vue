<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" style="background-color: var(--color-neutral-10);">
    <div class="w-full max-w-lg space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="mx-auto h-12 w-12 rounded-full flex items-center justify-center" style="background-color: var(--color-primary-500);">
          <i class="fas fa-tint text-xl" style="color: var(--color-neutral-0);"></i>
        </div>
        <h2 class="mt-6 text-center ms-font-large-title" style="color: var(--color-neutral-160);">
          Water Master
        </h2>
        <p class="mt-2 text-center ms-font-body" style="color: var(--color-neutral-100);">
          Sistema de Gestión para Aguetería
        </p>
      </div>

      <!-- Alerts -->
      <div v-if="errors.length > 0" class="p-4 rounded-lg" style="background-color: var(--color-error-tint-20); border: 1px solid var(--color-error-primary);">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle" style="color: var(--color-error-primary);"></i>
          </div>
          <div class="ml-3">
            <h3 class="ms-font-body-strong" style="color: var(--color-error-shade-20);">
              {{ errors.length === 1 ? 'Error:' : 'Errores:' }}
            </h3>
            <div class="mt-2 ms-font-body" style="color: var(--color-error-shade-10);">
              <ul class="list-disc pl-5 space-y-1">
                <li v-for="error in errors" :key="error">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div v-if="successMessage" class="p-4 rounded-lg" style="background-color: var(--color-success-tint-20); border: 1px solid var(--color-success-primary);">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-check-circle" style="color: var(--color-success-primary);"></i>
          </div>
          <div class="ml-3">
            <p class="ms-font-body-strong" style="color: var(--color-success-shade-20);">
              {{ successMessage }}
            </p>
          </div>
        </div>
      </div>

      <!-- Login Form -->
      <div class="ms-card py-8 px-6 sm:px-10">
        <form class="space-y-6" @submit.prevent="handleLogin">
          <div class="ms-form-group">
            <label for="email" class="ms-label">
              Correo electrónico
            </label>
            <div class="mt-1">
              <input
                id="email"
                v-model="form.email"
                name="email"
                type="email"
                autocomplete="email"
                required
                class="ms-input"
                placeholder="usuario@empresa.com"
              />
            </div>
          </div>

          <div class="ms-form-group">
            <label for="password" class="ms-label">
              Contraseña
            </label>
            <div class="mt-1">
              <input
                id="password"
                v-model="form.password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                class="ms-input"
                placeholder="Ingresa tu contraseña"
              />
            </div>
          </div>

          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember"
                v-model="form.remember"
                name="remember"
                type="checkbox"
                class="h-4 w-4 rounded focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="border-color: var(--color-neutral-60); color: var(--color-primary-500);"
              />
              <label for="remember" class="ml-2 ms-font-body">
                Recordarme
              </label>
            </div>

            <div>
              <a href="#" class="ms-font-body ms-text-primary hover:underline">
                ¿Olvidaste tu contraseña?
              </a>
            </div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="loading"
              class="ms-button ms-button-primary w-full justify-center relative"
            >
              <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                <i v-if="!loading" class="fas fa-sign-in-alt" style="color: var(--color-neutral-0);"></i>
                <i v-else class="fas fa-spinner fa-spin" style="color: var(--color-neutral-0);"></i>
              </span>
              {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
            </button>
          </div>
        </form>

        <!-- OAuth Section -->
        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t" style="border-color: var(--color-neutral-40);"></div>
            </div>
            <div class="relative flex justify-center ms-font-body">
              <span class="px-2" style="background-color: var(--color-neutral-0); color: var(--color-neutral-100);">O continúa con</span>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-2 gap-3">
            <a
              href="/auth/google/redirect"
              class="ms-button ms-button-secondary w-full justify-center"
            >
              <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24">
                <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
              <span class="ml-2">Google</span>
            </a>

            <a
              href="/auth/facebook/redirect"
              class="ms-button ms-button-secondary w-full justify-center"
            >
              <i class="fab fa-facebook-f text-blue-600 text-lg"></i>
              <span class="ml-2">Facebook</span>
            </a>
          </div>
        </div>

        <div class="mt-6 text-center">
          <p class="ms-font-body" style="color: var(--color-neutral-100);">
            ¿No tienes una cuenta?
            <router-link to="/register" class="ms-font-body-strong ms-text-primary hover:underline">
              Registrarse
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// Reactive data
const form = ref({
  email: '',
  password: '',
  remember: false
})

const errors = ref([])
const successMessage = ref('')
const loading = ref(false)

// Methods
const handleLogin = async () => {
  errors.value = []
  loading.value = true

  const result = await authStore.login(form.value)

  if (result.success) {
    successMessage.value = '¡Inicio de sesión exitoso!'
    router.push('/dashboard')
  } else {
    // Process errors
    const errorList = []
    if (result.errors) {
      Object.values(result.errors).forEach(errorArray => {
        if (Array.isArray(errorArray)) {
          errorList.push(...errorArray)
        } else {
          errorList.push(errorArray)
        }
      })
    }
    errors.value = errorList
  }
  
  loading.value = false
}

// Check if user is already authenticated
onMounted(() => {
  if (authStore.isLoggedIn) {
    router.push('/dashboard')
  }

  // Check for success message from registration
  if (route.query.registered) {
    successMessage.value = '¡Registro exitoso! Puedes iniciar sesión ahora.'
  }
})
</script>