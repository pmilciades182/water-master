import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createPinia } from 'pinia';
import App from './components/App.vue';

// Import views
import Login from './views/auth/Login.vue';
import Register from './views/auth/Register.vue';
import Dashboard from './views/Dashboard.vue';

// Import store
import { useAuthStore } from './stores/auth';

// Create router
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: Login,
            meta: { requiresGuest: true }
        },
        {
            path: '/register',
            name: 'register',
            component: Register,
            meta: { requiresGuest: true }
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: Dashboard,
            meta: { requiresAuth: true }
        },
        {
            path: '/',
            redirect: '/dashboard'
        }
    ]
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()
    
    // Initialize auth state if not already done
    if (!authStore.isAuthenticated && !authStore.user) {
        await authStore.initializeAuth()
    }

    if (to.meta.requiresAuth && !authStore.isLoggedIn) {
        next('/login')
    } else if (to.meta.requiresGuest && authStore.isLoggedIn) {
        next('/dashboard')
    } else {
        next()
    }
});

// Create Pinia store
const pinia = createPinia();

// Create and mount Vue app
const app = createApp(App);
app.use(router);
app.use(pinia);
app.mount('#app');
