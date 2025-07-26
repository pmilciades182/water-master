<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="w-full max-w-md space-y-8">
      <div>
        <img class="mx-auto h-12 w-auto" src="https://tailwindui.com/img/logos/workflow-mark-indigo-600.svg" alt="Workflow">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Crea una cuenta nueva
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          O
          <router-link to="/login" class="font-medium text-indigo-600 hover:text-indigo-500">
            inicia sesión en tu cuenta
          </router-link>
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="company_name" class="sr-only">Nombre de la empresa</label>
            <input id="company_name" name="company_name" type="text" required v-model="form.company_name" class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Nombre de la empresa">
          </div>
          <div>
            <label for="name" class="sr-only">Nombre</label>
            <input id="name" name="name" type="text" required v-model="form.name" class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Nombre">
          </div>
          <div>
            <label for="email-address" class="sr-only">Correo electrónico</label>
            <input id="email-address" name="email" type="email" autocomplete="email" required v-model="form.email" class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Correo electrónico">
          </div>
          <div>
            <label for="password" class="sr-only">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required v-model="form.password" class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Contraseña">
          </div>
          <div>
            <label for="password-confirm" class="sr-only">Confirmar contraseña</label>
            <input id="password-confirm" name="password_confirmation" type="password" autocomplete="new-password" required v-model="form.password_confirmation" class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Confirmar contraseña">
          </div>
        </div>

        <div class="flex items-center">
          <input id="terms" name="terms" type="checkbox" required v-model="form.terms" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
          <label for="terms" class="ml-2 block text-sm text-gray-900">
            Acepto los <a href="#" class="text-indigo-600 hover:text-indigo-500">términos y condiciones</a>
          </label>
        </div>

        <div>
          <button type="submit" :disabled="loading" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
              </svg>
            </span>
            {{ loading ? 'Creando cuenta...' : 'Crear Cuenta' }}
          </button>
        </div>
      </form>
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
