<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="mx-auto h-12 w-12 bg-blue-600 rounded-full flex items-center justify-center">
          <i class="fas fa-tint text-white text-xl"></i>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Water Master
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Crea tu cuenta para aguetería
        </p>
      </div>

      <!-- Alerts -->
      <div v-if="errors.length > 0" class="bg-red-50 border border-red-300 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <i class="fas fa-exclamation-circle text-red-400"></i>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
              {{ errors.length === 1 ? 'Error:' : 'Errores:' }}
            </h3>
            <div class="mt-2 text-sm text-red-700">
              <ul class="list-disc pl-5 space-y-1">
                <li v-for="error in errors" :key="error">{{ error }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Register Form -->
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form class="space-y-6" @submit.prevent="handleRegister">
          <div>
            <label for="company_name" class="block text-sm font-medium text-gray-700">
              Nombre de la Aguetería
            </label>
            <div class="mt-1">
              <input
                id="company_name"
                v-model="form.company_name"
                name="company_name"
                type="text"
                required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Ej: Aguetería San Juan"
              />
            </div>
          </div>

          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
              Nombre completo
            </label>
            <div class="mt-1">
              <input
                id="name"
                v-model="form.name"
                name="name"
                type="text"
                required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
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
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
              Contraseña
            </label>
            <div class="mt-1">
              <input
                id="password"
                v-model="form.password"
                name="password"
                type="password"
                required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
            <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres</p>
          </div>

          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
              Confirmar contraseña
            </label>
            <div class="mt-1">
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                name="password_confirmation"
                type="password"
                required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
              />
            </div>
          </div>

          <div class="flex items-center">
            <input
              id="terms"
              v-model="form.terms"
              name="terms"
              type="checkbox"
              required
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="terms" class="ml-2 block text-sm text-gray-900">
              Acepto los
              <a href="#" class="text-blue-600 hover:text-blue-500">términos y condiciones</a>
              y la
              <a href="#" class="text-blue-600 hover:text-blue-500">política de privacidad</a>
            </label>
          </div>

          <div>
            <button
              type="submit"
              :disabled="loading"
              class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                <i v-if="!loading" class="fas fa-user-plus text-blue-500 group-hover:text-blue-400"></i>
                <i v-else class="fas fa-spinner fa-spin text-blue-500"></i>
              </span>
              {{ loading ? 'Creando cuenta...' : 'Crear Cuenta (30 días gratis)' }}
            </button>
          </div>
        </form>

        <!-- OAuth Section -->
        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">O regístrate con</span>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-2 gap-3">
            <a
              href="/auth/google/redirect"
              class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
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
              class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
            >
              <i class="fab fa-facebook-f text-blue-600 text-lg"></i>
              <span class="ml-2">Facebook</span>
            </a>
          </div>
        </div>

        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600">
            ¿Ya tienes una cuenta?
            <router-link to="/login" class="font-medium text-blue-600 hover:text-blue-500">
              Iniciar sesión
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// Reactive data
const form = ref({
  company_name: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false
})

const errors = ref([])
const loading = ref(false)

// Methods
const handleRegister = async () => {
  errors.value = []
  loading.value = true

  const result = await authStore.register(form.value)

  if (result.success) {
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
})
</script>