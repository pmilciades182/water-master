<template>
  <MainLayout page-title="Dashboard">
    <!-- Main content -->
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Welcome message -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-900">
            ¡Bienvenido, {{ authStore.userName }}!
          </h1>
          <p class="mt-1 text-sm text-gray-600">
            Panel de control de {{ authStore.companyName }}
          </p>
        </div>

        <!-- Success message -->
        <div v-if="showWelcomeMessage" class="mb-6 bg-green-50 border border-green-300 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800">
                ¡Bienvenido a Water Master! Tu cuenta ha sido creada exitosamente.
              </p>
            </div>
          </div>
        </div>

        <!-- Stats overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-users text-2xl text-blue-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Clientes
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.clients }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-clipboard-list text-2xl text-green-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Servicios
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.services }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-file-invoice text-2xl text-yellow-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Facturas
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.invoices }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <i class="fas fa-boxes text-2xl text-red-500"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Productos
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ stats.products }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick actions -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              Acciones rápidas
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <button
                @click="openModal('client')"
                class="flex flex-col items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <i class="fas fa-user-plus text-2xl text-blue-500 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Nuevo Cliente</span>
              </button>
              
              <button
                @click="openModal('service')"
                class="flex flex-col items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <i class="fas fa-tools text-2xl text-green-500 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Nuevo Servicio</span>
              </button>
              
              <button
                @click="openModal('invoice')"
                class="flex flex-col items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <i class="fas fa-file-invoice-dollar text-2xl text-yellow-500 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Nueva Factura</span>
              </button>
              
              <button
                @click="openModal('product')"
                class="flex flex-col items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <i class="fas fa-box text-2xl text-red-500 mb-2"></i>
                <span class="text-sm font-medium text-gray-900">Nuevo Producto</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Trial info -->
        <div v-if="authStore.isOnTrial" class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                Tu período de prueba expira el {{ formatDate(authStore.company?.trial_ends_at) }}.
                <a href="#" class="font-medium underline hover:text-blue-600">
                  Configura tu suscripción aquí
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
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
              Esta funcionalidad estará disponible pronto. Estamos trabajando en el módulo de
              <strong>{{ modalType }}</strong>.
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
  </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import MainLayout from '../components/Layout/MainLayout.vue'

const router = useRouter()
const authStore = useAuthStore()

// State
const showWelcomeMessage = ref(false)
const showModal = ref(false)
const modalType = ref('')

const stats = ref({
  clients: 0,
  services: 0,
  invoices: 0,
  products: 0
})

// Methods

const openModal = (type) => {
  const types = {
    client: 'gestión de clientes',
    service: 'servicios técnicos',
    invoice: 'facturación',
    product: 'inventario de productos'
  }
  modalType.value = types[type] || type
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  modalType.value = ''
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('es-PY')
}

// Check authentication on mount
onMounted(() => {
  if (!authStore.isLoggedIn) {
    router.push('/login')
    return
  }

  // Show welcome message for new users
  if (authStore.user?.created_at) {
    const createdAt = new Date(authStore.user.created_at)
    const now = new Date()
    const diffInHours = (now - createdAt) / (1000 * 60 * 60)
    
    if (diffInHours < 1) {
      showWelcomeMessage.value = true
      setTimeout(() => {
        showWelcomeMessage.value = false
      }, 10000) // Hide after 10 seconds
    }
  }

  // TODO: Load actual stats from API
  // For now using mock data
  stats.value = {
    clients: 0,
    services: 0,
    invoices: 0,
    products: 0
  }
})
</script>