<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
      <div class="flex flex-col flex-grow pt-5 bg-white overflow-y-auto border-r border-gray-200">
        <!-- Logo -->
        <div class="flex items-center flex-shrink-0 px-4">
          <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center">
            <i class="fas fa-tint text-white"></i>
          </div>
          <span class="ml-2 text-xl font-semibold text-gray-900">Water Master</span>
        </div>

        <!-- Company info -->
        <div class="mt-5 px-4 py-3 bg-gray-50 border-y border-gray-200">
          <p class="text-sm font-medium text-gray-900">{{ authStore.companyName }}</p>
          <p class="text-xs text-gray-500">{{ authStore.company?.subdomain }}.watermaster.com</p>
        </div>

        <!-- Navigation -->
        <nav class="mt-5 flex-1 px-2 pb-4 space-y-1">
          <!-- Dashboard -->
          <router-link
            to="/dashboard"
            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors"
            :class="isActive('/dashboard') ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
          >
            <i class="fas fa-tachometer-alt mr-3 text-gray-400 group-hover:text-gray-500" :class="isActive('/dashboard') ? 'text-blue-500' : ''"></i>
            Dashboard
          </router-link>

          <!-- Products Section -->
          <div class="space-y-1">
            <h3 class="px-2 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Inventario
            </h3>
            <router-link
              to="/products"
              class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors"
              :class="isActive('/products') ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <i class="fas fa-boxes mr-3 text-gray-400 group-hover:text-gray-500" :class="isActive('/products') ? 'text-blue-500' : ''"></i>
              Productos
            </router-link>
            <router-link
              to="/product-categories"
              class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors"
              :class="isActive('/product-categories') ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <i class="fas fa-tags mr-3 text-gray-400 group-hover:text-gray-500" :class="isActive('/product-categories') ? 'text-blue-500' : ''"></i>
              Categorías
            </router-link>
          </div>

          <!-- Users & Permissions Section -->
          <div class="space-y-1">
            <h3 class="px-2 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Administración
            </h3>
            <router-link
              to="/users"
              class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors"
              :class="isActive('/users') ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <i class="fas fa-users mr-3 text-gray-400 group-hover:text-gray-500" :class="isActive('/users') ? 'text-blue-500' : ''"></i>
              Usuarios
            </router-link>
            <router-link
              to="/roles"
              class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors"
              :class="isActive('/roles') ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <i class="fas fa-user-shield mr-3 text-gray-400 group-hover:text-gray-500" :class="isActive('/roles') ? 'text-blue-500' : ''"></i>
              Roles
            </router-link>
            <router-link
              to="/permissions"
              class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors"
              :class="isActive('/permissions') ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <i class="fas fa-key mr-3 text-gray-400 group-hover:text-gray-500" :class="isActive('/permissions') ? 'text-blue-500' : ''"></i>
              Permisos
            </router-link>
          </div>

          <!-- Future Sections -->
          <div class="space-y-1">
            <h3 class="px-2 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Próximamente
            </h3>
            <button
              @click="showComingSoon('Clientes')"
              class="group flex items-center w-full px-2 py-2 text-sm font-medium rounded-md text-gray-400 cursor-not-allowed"
            >
              <i class="fas fa-address-book mr-3 text-gray-300"></i>
              Clientes
            </button>
            <button
              @click="showComingSoon('Servicios')"
              class="group flex items-center w-full px-2 py-2 text-sm font-medium rounded-md text-gray-400 cursor-not-allowed"
            >
              <i class="fas fa-tools mr-3 text-gray-300"></i>
              Servicios
            </button>
            <button
              @click="showComingSoon('Facturación')"
              class="group flex items-center w-full px-2 py-2 text-sm font-medium rounded-md text-gray-400 cursor-not-allowed"
            >
              <i class="fas fa-file-invoice mr-3 text-gray-300"></i>
              Facturación
            </button>
          </div>
        </nav>
      </div>
    </div>

    <!-- Mobile menu button -->
    <div class="md:hidden">
      <div class="flex items-center justify-between p-4 bg-white border-b border-gray-200">
        <div class="flex items-center">
          <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center">
            <i class="fas fa-tint text-white"></i>
          </div>
          <span class="ml-2 text-xl font-semibold text-gray-900">Water Master</span>
        </div>
        <button
          @click="toggleMobileMenu"
          class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
        >
          <i class="fas fa-bars" v-if="!showMobileMenu"></i>
          <i class="fas fa-times" v-else></i>
        </button>
      </div>

      <!-- Mobile menu -->
      <div v-show="showMobileMenu" class="bg-white border-b border-gray-200">
        <nav class="px-2 pt-2 pb-3 space-y-1">
          <!-- Mobile navigation items -->
          <router-link
            to="/dashboard"
            @click="closeMobileMenu"
            class="block px-3 py-2 rounded-md text-base font-medium"
            :class="isActive('/dashboard') ? 'bg-blue-100 text-blue-900' : 'text-gray-600 hover:bg-gray-50'"
          >
            <i class="fas fa-tachometer-alt mr-2"></i>
            Dashboard
          </router-link>
          <!-- Add more mobile menu items as needed -->
        </nav>
      </div>
    </div>

    <!-- Main content -->
    <div class="md:pl-64 flex flex-col flex-1">
      <!-- Top header -->
      <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16">
            <div class="flex items-center">
              <!-- Page title will be injected here -->
              <slot name="header">
                <h1 class="text-2xl font-semibold text-gray-900">{{ pageTitle }}</h1>
              </slot>
            </div>

            <div class="flex items-center space-x-4">
              <!-- User menu -->
              <div class="relative">
                <div class="flex items-center space-x-3">
                  <div v-if="authStore.user?.avatar" class="h-8 w-8">
                    <img
                      class="h-8 w-8 rounded-full object-cover"
                      :src="authStore.user.avatar"
                      :alt="authStore.userName"
                    />
                  </div>
                  <div v-else class="h-8 w-8 bg-gray-300 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-gray-600 text-sm"></i>
                  </div>
                  <div class="hidden md:block">
                    <p class="text-sm font-medium text-gray-900">{{ authStore.userName }}</p>
                    <p class="text-xs text-gray-500">{{ authStore.userEmail }}</p>
                  </div>
                  <button
                    @click="handleLogout"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                    title="Cerrar sesión"
                  >
                    <i class="fas fa-sign-out-alt"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1">
        <slot />
      </main>
    </div>

    <!-- Coming Soon Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="closeModal">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" @click.stop>
        <div class="mt-3 text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
            <i class="fas fa-tools text-blue-600"></i>
          </div>
          <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Próximamente</h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-gray-500">
              El módulo de <strong>{{ modalType }}</strong> estará disponible pronto.
            </p>
          </div>
          <div class="items-center px-4 py-3">
            <button
              @click="closeModal"
              class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Props
const props = defineProps({
  pageTitle: {
    type: String,
    default: ''
  }
})

// State
const showMobileMenu = ref(false)
const showModal = ref(false)
const modalType = ref('')

// Methods
const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}

const toggleMobileMenu = () => {
  showMobileMenu.value = !showMobileMenu.value
}

const closeMobileMenu = () => {
  showMobileMenu.value = false
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const showComingSoon = (type) => {
  modalType.value = type
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  modalType.value = ''
}
</script>