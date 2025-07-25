<template>
  <div id="app" class="min-h-screen bg-gradient-to-br from-blue-500 to-cyan-600">
    <div class="container mx-auto px-4 py-16">
      <div class="text-center text-white">
        <h1 class="text-6xl font-bold mb-6">💧 Water Master</h1>
        <p class="text-xl mb-8 opacity-90">Sistema de gestión de agua con Laravel y Vue.js</p>
        
        <div class="grid md:grid-cols-3 gap-8 mt-12">
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 hover:bg-white/20 transition-all">
            <div class="text-4xl mb-4">📊</div>
            <h3 class="text-xl font-semibold mb-2">Monitoreo</h3>
            <p class="opacity-80">Controla el consumo y calidad del agua en tiempo real</p>
          </div>
          
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 hover:bg-white/20 transition-all">
            <div class="text-4xl mb-4">📈</div>
            <h3 class="text-xl font-semibold mb-2">Reportes</h3>
            <p class="opacity-80">Genera informes detallados de uso y eficiencia</p>
          </div>
          
          <div class="bg-white/10 backdrop-blur-sm rounded-lg p-6 hover:bg-white/20 transition-all">
            <div class="text-4xl mb-4">⚙️</div>
            <h3 class="text-xl font-semibold mb-2">Gestión</h3>
            <p class="opacity-80">Administra recursos hídricos de manera inteligente</p>
          </div>
        </div>
        
        <div class="mt-12">
          <button @click="showInfo = !showInfo" 
                  class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold hover:bg-blue-50 transition-colors">
            {{ showInfo ? 'Ocultar' : 'Ver más' }} información
          </button>
        </div>
        
        <div v-if="showInfo" class="mt-8 bg-white/10 backdrop-blur-sm rounded-lg p-6 text-left">
          <h4 class="text-lg font-semibold mb-4">🚀 Tecnologías utilizadas:</h4>
          <ul class="space-y-2">
            <li class="flex items-center"><span class="mr-2">✅</span> Laravel 11</li>
            <li class="flex items-center"><span class="mr-2">✅</span> Vue.js 3</li>
            <li class="flex items-center"><span class="mr-2">✅</span> PostgreSQL</li>
            <li class="flex items-center"><span class="mr-2">✅</span> Docker & Sail</li>
            <li class="flex items-center"><span class="mr-2">✅</span> Tailwind CSS</li>
            <li class="flex items-center"><span class="mr-2">✅</span> Vite</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      showInfo: false
    }
  }
}
</script>