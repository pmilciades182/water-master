# Water Master - Sistema de Gestión para Aguetería

Sistema SaaS multi-tenant desarrollado en Laravel + Vue.js para la gestión integral de agueterías en Paraguay. Incluye facturación electrónica, gestión de inventario, RBAC, y integración con métodos de pago.

## 🚀 Características Principales

- **Multi-tenant**: Soporte para múltiples empresas agueterías
- **Autenticación múltiple**: Login local, Google OAuth, Facebook OAuth
- **RBAC**: Sistema de roles y permisos granulares
- **Gestión de productos**: Inventario configurable con categorías dinámicas
- **API REST**: Endpoints documentados para integración
- **Frontend moderno**: Vue.js 3 + Vite + Tailwind CSS
- **Base de datos robusta**: PostgreSQL con soporte geográfico

## 📋 Requisitos del Sistema

- **Docker** >= 20.10
- **Docker Compose** >= 1.29
- **Node.js** >= 18.x
- **NPM** >= 8.x
- **Make** (opcional, pero recomendado)

## 🛠 Instalación Rápida (Recomendado)

### Usando Makefile (Más fácil):

```bash
# 1. Clonar el repositorio
git clone <repository-url>
cd water-master

# 2. Copiar configuración
cp .env.example .env

# 3. Configuración completa automática
make install
```

### Instalación Manual:

<details>
<summary>Click para expandir instrucciones manuales</summary>

1. **Clonar el repositorio y configurar environment:**
   ```bash
   git clone <repository-url>
   cd water-master
   cp .env.example .env
   ```

2. **Configurar variables Docker (Linux/Mac):**
   ```bash
   echo "WWWUSER=$(id -u)" >> .env
   echo "WWWGROUP=$(id -g)" >> .env
   ```

3. **Levantar contenedores:**
   ```bash
   docker-compose up --build -d
   ```

4. **Instalar dependencias:**
   ```bash
   docker-compose exec laravel.test composer install
   npm install
   ```

5. **Configurar Laravel:**
   ```bash
   docker-compose exec laravel.test php artisan key:generate
   docker-compose exec laravel.test php artisan migrate --seed
   ```

6. **Compilar assets:**
   ```bash
   npm run build
   ```

</details>

## 🌐 Acceso al Sistema

- **Aplicación Web**: http://localhost:9901
- **Servidor de desarrollo Vite**: http://localhost:5174
- **PostgreSQL**: localhost:9902

### 👤 Credenciales de Prueba
- **Email**: demo@watermaster.com
- **Contraseña**: password123

## ⚡ Comandos Make (Recomendado)

El proyecto incluye un Makefile completo para simplificar las tareas de desarrollo:

### 📦 Gestión de Contenedores
```bash
make start          # Iniciar contenedores
make stop           # Detener contenedores
make restart        # Reiniciar contenedores
make status         # Ver estado de contenedores
make logs           # Ver logs de Laravel
```

### 🛠 Desarrollo
```bash
make build          # Compilar assets frontend
make dev            # Servidor de desarrollo Vite
make migrate        # Ejecutar migraciones
make seed           # Ejecutar seeders
make cache-clear    # Limpiar cache de Laravel
```

### 🐚 Acceso a Contenedores
```bash
make shell          # Shell del contenedor Laravel
make shell-postgres # Shell de PostgreSQL
make tinker         # Laravel Tinker
```

### 🧪 Testing
```bash
make test           # Ejecutar tests de PHP
make test-coverage  # Tests con coverage
```

### 🔧 Utilidades
```bash
make info           # Información del sistema
make version        # Versiones instaladas
make routes         # Listar todas las rutas
make clean          # Limpiar archivos temporales
make fresh-start    # Reinicio completo
```

### 🆘 Emergencia
```bash
make emergency-reset # Reset completo (¡cuidado!)
```

### 📋 Ayuda
```bash
make help           # Ver todos los comandos disponibles
```

## 🏗 Arquitectura del Proyecto

### Backend (Laravel 12.x)
- **Autenticación**: Sistema multi-modal con OAuth
- **Base de datos**: PostgreSQL con migraciones
- **API**: RESTful con validación y documentación
- **RBAC**: Roles y permisos granulares multi-tenant
- **Modelos**: Company, User, Role, Permission, Product, ProductCategory

### Frontend (Vue.js 3)
- **Framework**: Vue 3 + Composition API
- **Bundler**: Vite 7.x
- **Estilos**: Tailwind CSS 4.x
- **Routing**: Vue Router 4.x
- **Estado**: Pinia
- **Iconos**: Font Awesome 6

### DevOps
- **Contenedores**: Docker + Docker Compose
- **Servidor web**: Nginx
- **PHP**: 8.4
- **Base de datos**: PostgreSQL 15

## 📊 Base de Datos

### Conexión
- **Host**: localhost
- **Puerto**: 9902
- **Base de datos**: water
- **Usuario**: admin
- **Contraseña**: 12345

### Estructura Principal
- `companies` - Empresas agueterías (multi-tenant)
- `users` - Usuarios del sistema
- `roles` - Roles configurables por empresa
- `permissions` - Permisos granulares
- `product_categories` - Categorías de productos
- `products` - Inventario de productos

## 🔧 Comandos Alternativos

<details>
<summary>Usando Docker Compose directamente</summary>

```bash
# Gestión de contenedores
docker-compose up -d
docker-compose down
docker-compose restart
docker-compose ps

# Comandos Laravel
docker-compose exec laravel.test php artisan migrate
docker-compose exec laravel.test php artisan tinker
docker-compose exec laravel.test composer install

# Frontend
npm install
npm run build
npm run dev
```

</details>

<details>
<summary>Usando Laravel Sail</summary>

```bash
# Primero configurar alias
alias sail='bash vendor/bin/sail'

# Comandos Sail
sail up -d
sail down
sail artisan migrate
sail shell
sail logs
```

</details>

## 🧩 Módulos Implementados

### ✅ Sistema de Autenticación
- Login local con email/password
- OAuth con Google y Facebook
- Middleware de autenticación
- Gestión de sesiones

### ✅ Sistema RBAC (Role-Based Access Control)
- Roles configurables por empresa
- Permisos granulares
- Middleware de verificación de permisos
- API para gestión de usuarios y roles

### ✅ Gestión de Productos
- Categorías configurables (Tuberías, Conexiones, Medidores)
- Productos con atributos dinámicos
- Sistema de inventario
- API RESTful completa

### ✅ Frontend Dashboard
- Dashboard responsive con Vue.js 3
- Navegación dinámica por permisos
- Componentes reutilizables
- Estado global con Pinia

## 🚧 Próximos Módulos (Roadmap)

### 📋 Sistema de Clientes
- Gestión de clientes residenciales y comerciales
- Historial de servicios
- Ubicación geográfica

### 🔧 Gestión de Servicios Técnicos
- Órdenes de trabajo
- Asignación de técnicos
- App móvil para campo

### 🧾 Sistema de Facturación
- Facturación electrónica SET (Paraguay)
- Diferentes tipos de comprobantes
- Reportes fiscales

### 💳 Integración de Pagos
- Bancard API
- Códigos QR
- Pagos recurrentes

## 🐛 Solución de Problemas

### Pantalla Blanca en Frontend
```bash
make build
make cache-clear
# Limpiar caché del navegador (Ctrl+Shift+R)
```

### Problemas de Permisos
```bash
# Ejecutar con sudo si es necesario
sudo chown -R $USER:$USER storage bootstrap/cache public/build
sudo chmod -R 755 storage bootstrap/cache public/build
```

### Contenedores no levantan
```bash
make stop
make clean
make start
```

### Base de datos corrupta
```bash
make fresh-start  # ⚠️ Esto elimina todos los datos
```

## 🤝 Contribución

### Setup para Desarrollo
```bash
# 1. Fork del repositorio
# 2. Clonar tu fork
git clone https://github.com/tu-usuario/water-master.git
cd water-master

# 3. Setup del proyecto
make install

# 4. Crear rama para nueva feature
git checkout -b feature/nueva-funcionalidad

# 5. Desarrollar y commit
git add .
git commit -m "feat: descripción de la nueva funcionalidad"

# 6. Push y crear Pull Request
git push origin feature/nueva-funcionalidad
```

### Estándares de Código
- **Backend**: PSR-12 para PHP
- **Frontend**: ESLint + Prettier para JavaScript/Vue
- **Commits**: Conventional Commits
- **Tests**: PHPUnit para backend, Vitest para frontend

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

- **Documentación**: Ver archivos en `/docs`
- **Issues**: Reportar problemas en GitHub Issues
- **Wiki**: Documentación adicional en GitHub Wiki

## 🏷️ Versiones

- **v1.0.0**: Sistema base multi-tenant con RBAC y productos
- **v1.1.0**: Sistema de clientes y servicios (próximo)
- **v1.2.0**: Facturación electrónica SET (próximo)
- **v2.0.0**: App móvil y georeferenciación (futuro)
