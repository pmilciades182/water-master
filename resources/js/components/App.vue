<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

onMounted(() => {
  // Initialize auth state from server
  authStore.initializeAuth()
})
</script>

<style>
/* Tailwind base styles */
@import '../../css/app.css';
</style>