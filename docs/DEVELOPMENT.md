# Guía de Desarrollo - Water Master

## 🏗️ Arquitectura del Proyecto

### Estructura de Directorios

```
water-master/
├── app/                    # Aplicación Laravel
│   ├── Http/
│   │   ├── Controllers/    # Controladores API
│   │   └── Middleware/     # Middleware personalizado
│   ├── Models/            # Modelos Eloquent
│   └── Policies/          # Políticas de autorización
├── database/
│   ├── migrations/        # Migraciones de base de datos
│   └── seeders/          # Seeders de datos iniciales
├── resources/
│   ├── js/               # Frontend Vue.js
│   │   ├── components/   # Componentes Vue
│   │   ├── views/        # Vistas/páginas
│   │   └── stores/       # Estado global (Pinia)
│   └── views/            # Templates Blade
├── routes/               # Definición de rutas
├── public/build/         # Assets compilados
├── docs/                 # Documentación
├── Makefile             # Comandos de desarrollo
└── docker-compose.yml   # Configuración Docker
```

## 🛠️ Stack Tecnológico

### Backend
- **Laravel 12.x** - Framework PHP
- **PostgreSQL 15** - Base de datos
- **PHP 8.4** - Lenguaje de programación

### Frontend
- **Vue.js 3** - Framework JavaScript
- **Vite 7.x** - Build tool y dev server
- **Tailwind CSS 4.x** - Framework CSS
- **Pinia** - Gestión de estado
- **Vue Router 4** - Routing

### DevOps
- **Docker** - Contenedorización
- **Docker Compose** - Orquestación de contenedores
- **Make** - Automatización de tareas

## 🔧 Configuración del Entorno de Desarrollo

### Requisitos Previos
```bash
# Verificar versiones
docker --version          # >= 20.10
docker-compose --version  # >= 1.29
node --version            # >= 18.x
npm --version             # >= 8.x
make --version            # Cualquier versión
```

### Setup Inicial
```bash
# 1. Clonar repositorio
git clone <repository-url>
cd water-master

# 2. Copiar configuración
cp .env.example .env

# 3. Setup automático
make install

# 4. Verificar que todo funciona
make info
```

### Variables de Entorno Importantes

```env
# Base
APP_NAME="Water Master"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:9901

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=water
DB_USERNAME=admin
DB_PASSWORD=12345

# OAuth (opcional)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_secret
FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_secret
```

## 🎯 Flujo de Desarrollo

### Creando una Nueva Feature

```bash
# 1. Crear rama
git checkout -b feature/nueva-funcionalidad

# 2. Desarrollo backend (si es necesario)
make artisan CMD="make:model NuevoModelo -m"
make artisan CMD="make:controller Api/NuevoController --api"

# 3. Ejecutar migraciones
make migrate

# 4. Desarrollo frontend
# Editar archivos en resources/js/

# 5. Compilar assets
make build

# 6. Testing
make test

# 7. Commit y push
git add .
git commit -m "feat: nueva funcionalidad"
git push origin feature/nueva-funcionalidad
```

### Comandos de Desarrollo Frecuentes

```bash
# Backend
make shell                # Acceso al contenedor
make tinker               # Laravel Tinker
make migrate              # Ejecutar migraciones
make seed                 # Ejecutar seeders
make cache-clear          # Limpiar cache

# Frontend
make dev                  # Servidor de desarrollo
make build                # Compilar para producción
make watch                # Compilación automática

# Base de datos
make shell-postgres       # Acceso a PostgreSQL
make migrate-fresh        # Migración desde cero

# Logs y debugging
make logs                 # Ver logs de Laravel
make status               # Estado de contenedores
```

## 🏛️ Patrones de Arquitectura

### Backend (Laravel)

#### 1. Modelo Multi-Tenant
Todos los modelos principales incluyen `company_id`:

```php
// En todos los modelos
class Product extends Model
{
    protected $fillable = ['company_id', ...];

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}

// En controladores
public function index(Request $request)
{
    $companyId = $request->user()->company_id;
    $products = Product::forCompany($companyId)->get();
}
```

#### 2. API Resources y Controllers
```php
// Estructura estándar de controlador API
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Validación y filtros
        // Consulta con scope multi-tenant
        // Paginación
        // Retorno JSON
    }
}
```

#### 3. Sistema RBAC
```php
// Middleware de permisos
Route::middleware(['auth', 'permission:products.view'])
    ->get('/api/products', [ProductController::class, 'index']);

// En componentes Vue
if (userStore.hasPermission('products.create')) {
    // Mostrar botón crear
}
```

### Frontend (Vue.js)

#### 1. Composition API
```vue
<script setup>
import { ref, onMounted } from 'vue'
import { useProductStore } from '@/stores/products'

const productStore = useProductStore()
const products = ref([])

onMounted(async () => {
    products.value = await productStore.fetchProducts()
})
</script>
```

#### 2. Gestión de Estado con Pinia
```javascript
// stores/products.js
export const useProductStore = defineStore('products', () => {
    const products = ref([])
    
    const fetchProducts = async () => {
        const response = await axios.get('/api/products')
        products.value = response.data.data
        return products.value
    }
    
    return { products, fetchProducts }
})
```

#### 3. Composables para Reutilización
```javascript
// composables/useApi.js
export function useApi() {
    const loading = ref(false)
    const error = ref(null)
    
    const request = async (callback) => {
        loading.value = true
        error.value = null
        try {
            return await callback()
        } catch (err) {
            error.value = err.response?.data?.message || 'Error'
            throw err
        } finally {
            loading.value = false
        }
    }
    
    return { loading, error, request }
}
```

## 🧪 Testing

### Backend Testing
```bash
# Ejecutar todos los tests
make test

# Tests específicos
make artisan CMD="test --filter=ProductTest"

# Con coverage
make test-coverage
```

### Estructura de Tests
```php
// tests/Feature/ProductTest.php
class ProductTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_create_product()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'Test Product',
                'price' => 100
            ]);
            
        $response->assertStatus(201);
    }
}
```

## 🗄️ Base de Datos

### Convenciones de Migraciones
```php
// Nombre descriptivo con timestamp
2025_07_26_134551_create_products_table.php

// Estructura estándar
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->decimal('price', 10, 2);
    $table->timestamps();
    
    $table->index(['company_id', 'name']);
});
```

### Seeders
```php
// database/seeders/ProductSeeder.php
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        
        foreach ($companies as $company) {
            Product::factory()
                ->count(50)
                ->create(['company_id' => $company->id]);
        }
    }
}
```

## 🎨 Frontend Guidelines

### Estructura de Componentes
```vue
<template>
  <!-- Template limpio y semántico -->
  <div class="product-list">
    <ProductCard 
      v-for="product in products" 
      :key="product.id"
      :product="product"
      @edit="handleEdit"
    />
  </div>
</template>

<script setup>
// Imports
// Props/emits
// Reactive data
// Computed
// Methods
// Lifecycle hooks
</script>

<style scoped>
/* Estilos específicos del componente */
</style>
```

### Naming Conventions
- **Componentes**: PascalCase (`ProductCard.vue`)
- **Props**: camelCase (`productData`)
- **Events**: kebab-case (`@product-updated`)
- **CSS Classes**: Tailwind + kebab-case custom

## 🔐 Seguridad

### Autenticación
- Autenticación basada en sesiones Laravel
- CSRF protection automático
- Rate limiting en rutas sensibles

### Autorización
- Sistema RBAC granular
- Middleware de permisos
- Políticas a nivel de modelo

### Validación
```php
// Backend validation
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'price' => 'required|numeric|min:0'
]);

// Frontend validation
const rules = {
    name: { required, maxLength: maxLength(255) },
    email: { required, email },
    price: { required, minValue: minValue(0) }
}
```

## 📝 Estándares de Código

### PHP (PSR-12)
```php
// Formato de métodos
public function createProduct(Request $request): JsonResponse
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);
    
    $product = Product::create([
        'company_id' => $request->user()->company_id,
        'name' => $validated['name'],
    ]);
    
    return response()->json($product, 201);
}
```

### JavaScript/Vue
```javascript
// Destructuring y arrow functions
const { data: products, loading } = await useApi().request(
    () => productStore.fetchProducts()
)

// Async/await sobre promises
const handleSubmit = async () => {
    try {
        await productStore.createProduct(form.value)
        router.push('/products')
    } catch (error) {
        console.error('Error creating product:', error)
    }
}
```

### Git Commits (Conventional Commits)
```bash
feat: add product management API
fix: resolve pagination issue in product list
docs: update API documentation
test: add unit tests for product model
refactor: improve error handling in auth store
```

## 🚀 Deployment

### Build para Producción
```bash
# Compilar assets
make build

# Optimizar Laravel
make artisan CMD="config:cache"
make artisan CMD="route:cache"
make artisan CMD="view:cache"
```

### Variables de Entorno Producción
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://watermaster.com

# Base de datos producción
DB_HOST=production-db-host
DB_DATABASE=water_production
```

## 🔍 Debugging

### Backend
```bash
# Ver logs en tiempo real
make logs

# Laravel Tinker para testing
make tinker

# Dump and die en código
dd($variable);

# Log para debugging
\Log::info('Debug info', ['data' => $data]);
```

### Frontend
```javascript
// Vue DevTools en navegador
// Console.log para debugging
console.log('Debug:', data)

// Reactive debugging
watchEffect(() => {
    console.log('State changed:', state.value)
})
```

## 📚 Recursos Adicionales

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js 3 Guide](https://vuejs.org/guide/)
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [Pinia Documentation](https://pinia.vuejs.org/)
- [API Documentation](./API.md)