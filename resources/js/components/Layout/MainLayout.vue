<template>
  <div class="min-h-screen" style="background-color: var(--color-neutral-10);">
    <!-- Sidebar -->
    <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
      <div class="flex flex-col flex-grow pt-5 ms-nav overflow-y-auto">
        <!-- Logo -->
        <div class="flex items-center flex-shrink-0 px-4">
          <div class="h-8 w-8 rounded-full flex items-center justify-center" style="background-color: var(--color-primary-500);">
            <i class="fas fa-tint" style="color: var(--color-neutral-0);"></i>
          </div>
          <span class="ml-2 ms-font-title2">Water Master</span>
        </div>

        <!-- Company info -->
        <div class="mt-5 px-4 py-3" style="background-color: var(--color-neutral-20); border-top: 1px solid var(--color-neutral-40); border-bottom: 1px solid var(--color-neutral-40);">
          <p class="ms-font-body-strong" style="color: var(--color-neutral-160);">{{ authStore.companyName }}</p>
          <p class="ms-font-caption" style="color: var(--color-neutral-100);">{{ authStore.company?.subdomain }}.watermaster.com</p>
        </div>

        <!-- Navigation -->
        <nav class="mt-5 flex-1 px-2 pb-4 space-y-1">
          <!-- Dashboard -->
          <router-link
            to="/dashboard"
            class="ms-nav-item ms-font-body"
            :class="isActive('/dashboard') ? 'active' : ''"
          >
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
          </router-link>

          <!-- Products Section -->
          <div class="space-y-1">
            <h3 class="px-2 py-2 ms-font-caption" style="color: var(--color-neutral-100); font-weight: var(--font-weight-medium); letter-spacing: 0.5px; text-transform: uppercase;">
              Inventario
            </h3>
            <router-link
              to="/products"
              class="ms-nav-item ms-font-body"
              :class="isActive('/products') ? 'active' : ''"
            >
              <i class="fas fa-boxes"></i>
              Productos
            </router-link>
            <router-link
              to="/product-categories"
              class="ms-nav-item ms-font-body"
              :class="isActive('/product-categories') ? 'active' : ''"
            >
              <i class="fas fa-tags"></i>
              Categorías
            </router-link>
          </div>

          <!-- Users & Permissions Section -->
          <div class="space-y-1">
            <h3 class="px-2 py-2 ms-font-caption" style="color: var(--color-neutral-100); font-weight: var(--font-weight-medium); letter-spacing: 0.5px; text-transform: uppercase;">
              Administración
            </h3>
            <router-link
              to="/users"
              class="ms-nav-item ms-font-body"
              :class="isActive('/users') ? 'active' : ''"
            >
              <i class="fas fa-users"></i>
              Usuarios
            </router-link>
            <router-link
              to="/roles"
              class="ms-nav-item ms-font-body"
              :class="isActive('/roles') ? 'active' : ''"
            >
              <i class="fas fa-user-shield"></i>
              Roles
            </router-link>
            <router-link
              to="/permissions"
              class="ms-nav-item ms-font-body"
              :class="isActive('/permissions') ? 'active' : ''"
            >
              <i class="fas fa-key"></i>
              Permisos
            </router-link>
          </div>

          <!-- Future Sections -->
          <div class="space-y-1">
            <h3 class="px-2 py-2 ms-font-caption" style="color: var(--color-neutral-100); font-weight: var(--font-weight-medium); letter-spacing: 0.5px; text-transform: uppercase;">
              Próximamente
            </h3>
            <button
              @click="showComingSoon('Clientes')"
              class="ms-nav-item w-full cursor-not-allowed opacity-60"
            >
              <i class="fas fa-address-book"></i>
              Clientes
              <span class="ml-auto ms-badge ms-badge-neutral">Próximo</span>
            </button>
            <button
              @click="showComingSoon('Servicios')"
              class="ms-nav-item w-full cursor-not-allowed opacity-60"
            >
              <i class="fas fa-tools"></i>
              Servicios
              <span class="ml-auto ms-badge ms-badge-neutral">Próximo</span>
            </button>
            <button
              @click="showComingSoon('Facturación')"
              class="ms-nav-item w-full cursor-not-allowed opacity-60"
            >
              <i class="fas fa-file-invoice"></i>
              Facturación
              <span class="ml-auto ms-badge ms-badge-neutral">Próximo</span>
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
      <header class="ms-elevation-2" style="background-color: var(--color-neutral-0); border-bottom: 1px solid var(--color-neutral-30);">
        <div class="px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16">
            <div class="flex items-center">
              <!-- Page title will be injected here -->
              <slot name="header">
                <h1 class="ms-font-title1" style="color: var(--color-neutral-160);">{{ pageTitle }}</h1>
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
                  <div v-else class="h-8 w-8 rounded-full flex items-center justify-center" style="background-color: var(--color-neutral-50);">
                    <i class="fas fa-user text-sm" style="color: var(--color-neutral-100);"></i>
                  </div>
                  <div class="hidden md:block">
                    <p class="ms-font-body-strong" style="color: var(--color-neutral-160);">{{ authStore.userName }}</p>
                    <p class="ms-font-caption" style="color: var(--color-neutral-100);">{{ authStore.userEmail }}</p>
                  </div>
                  <button
                    @click="handleLogout"
                    class="ms-button-subtle"
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
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>

    <!-- Coming Soon Modal -->
    <div v-if="showModal" class="ms-modal-overlay" @click="closeModal">
      <div class="ms-modal ms-modal-sm" @click.stop>
        <div class="ms-modal-header">
          <h3 class="ms-modal-title">Próximamente</h3>
        </div>
        <div class="ms-modal-body text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full mb-4" style="background-color: var(--color-primary-50);">
            <i class="fas fa-tools" style="color: var(--color-primary-500);"></i>
          </div>
          <p class="ms-font-body" style="color: var(--color-neutral-130);">
            El módulo de <span class="ms-font-body-strong">{{ modalType }}</span> estará disponible pronto.
          </p>
        </div>
        <div class="ms-modal-footer">
          <button
            @click="closeModal"
            class="ms-button ms-button-primary w-full"
          >
            Cerrar
          </button>
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