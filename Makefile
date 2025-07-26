# Water Master - Sistema de Gestión para Aguetería
# Makefile para simplificar comandos de desarrollo

# Variables
DOCKER_COMPOSE = docker-compose
LARAVEL_CONTAINER = water-master_laravel.test_1
POSTGRES_CONTAINER = water-master_postgres_1

# Colores para output
GREEN = \033[0;32m
YELLOW = \033[1;33m
RED = \033[0;31m
NC = \033[0m # No Color

.PHONY: help install start stop restart status logs shell artisan tinker migrate seed test build npm fix-permissions clean

# Comando por defecto
help: ## Mostrar esta ayuda
	@echo "$(GREEN)Water Master - Comandos disponibles:$(NC)"
	@echo ""
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  $(YELLOW)%-15s$(NC) %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# ================================
# GESTIÓN DE CONTENEDORES
# ================================

install: ## Instalar dependencias y configurar proyecto
	@echo "$(GREEN)Instalando dependencias...$(NC)"
	@$(DOCKER_COMPOSE) up -d
	@sleep 5
	@docker exec $(LARAVEL_CONTAINER) composer install
	@npm install
	@$(MAKE) migrate
	@$(MAKE) seed
	@$(MAKE) build
	@echo "$(GREEN)✓ Instalación completada$(NC)"

start: ## Iniciar todos los contenedores
	@echo "$(GREEN)Iniciando contenedores...$(NC)"
	@$(DOCKER_COMPOSE) up -d
	@echo "$(GREEN)✓ Contenedores iniciados$(NC)"

stop: ## Detener todos los contenedores
	@echo "$(YELLOW)Deteniendo contenedores...$(NC)"
	@$(DOCKER_COMPOSE) down
	@echo "$(GREEN)✓ Contenedores detenidos$(NC)"

restart: stop start ## Reiniciar todos los contenedores

status: ## Mostrar estado de contenedores
	@echo "$(GREEN)Estado de contenedores:$(NC)"
	@$(DOCKER_COMPOSE) ps

logs: ## Mostrar logs de Laravel
	@docker logs -f $(LARAVEL_CONTAINER)

logs-postgres: ## Mostrar logs de PostgreSQL
	@docker logs -f $(POSTGRES_CONTAINER)

# ================================
# ACCESO A CONTENEDORES
# ================================

shell: ## Acceder al shell del contenedor Laravel
	@echo "$(GREEN)Accediendo al contenedor Laravel...$(NC)"
	@docker exec -it $(LARAVEL_CONTAINER) bash

shell-postgres: ## Acceder al shell de PostgreSQL
	@echo "$(GREEN)Accediendo a PostgreSQL...$(NC)"
	@docker exec -it $(POSTGRES_CONTAINER) psql -U admin -d water

# ================================
# COMANDOS LARAVEL
# ================================

artisan: ## Ejecutar comando artisan (usar: make artisan CMD="route:list")
	@docker exec $(LARAVEL_CONTAINER) php artisan $(CMD)

tinker: ## Abrir Laravel Tinker
	@docker exec -it $(LARAVEL_CONTAINER) php artisan tinker

migrate: ## Ejecutar migraciones
	@echo "$(GREEN)Ejecutando migraciones...$(NC)"
	@docker exec $(LARAVEL_CONTAINER) php artisan migrate
	@echo "$(GREEN)✓ Migraciones completadas$(NC)"

migrate-fresh: ## Ejecutar migraciones desde cero
	@echo "$(YELLOW)Ejecutando migraciones desde cero...$(NC)"
	@docker exec $(LARAVEL_CONTAINER) php artisan migrate:fresh --seed
	@echo "$(GREEN)✓ Base de datos recreada$(NC)"

migrate-status: ## Ver estado de migraciones
	@docker exec $(LARAVEL_CONTAINER) php artisan migrate:status

seed: ## Ejecutar seeders
	@echo "$(GREEN)Ejecutando seeders...$(NC)"
	@docker exec $(LARAVEL_CONTAINER) php artisan db:seed
	@echo "$(GREEN)✓ Seeders completados$(NC)"

seed-class: ## Ejecutar seeder específico (usar: make seed-class CLASS="ProductCategorySeeder")
	@docker exec $(LARAVEL_CONTAINER) php artisan db:seed --class=$(CLASS)

cache-clear: ## Limpiar cache de Laravel
	@echo "$(GREEN)Limpiando cache...$(NC)"
	@docker exec $(LARAVEL_CONTAINER) php artisan cache:clear
	@docker exec $(LARAVEL_CONTAINER) php artisan config:clear
	@docker exec $(LARAVEL_CONTAINER) php artisan route:clear
	@docker exec $(LARAVEL_CONTAINER) php artisan view:clear
	@echo "$(GREEN)✓ Cache limpiado$(NC)"

# ================================
# TESTING
# ================================

test: ## Ejecutar tests de PHP
	@echo "$(GREEN)Ejecutando tests...$(NC)"
	@docker exec $(LARAVEL_CONTAINER) php artisan test

test-coverage: ## Ejecutar tests con coverage
	@docker exec $(LARAVEL_CONTAINER) php artisan test --coverage

# ================================
# FRONTEND
# ================================

npm: ## Ejecutar comando npm (usar: make npm CMD="install")
	@npm $(CMD)

build: ## Compilar assets para producción
	@echo "$(GREEN)Compilando assets...$(NC)"
	@npm run build
	@echo "$(GREEN)✓ Assets compilados$(NC)"

dev: ## Iniciar servidor de desarrollo Vite
	@echo "$(GREEN)Iniciando servidor de desarrollo...$(NC)"
	@npm run dev

watch: ## Compilar assets en modo watch
	@npm run dev

# ================================
# UTILIDADES
# ================================

fix-permissions: ## Arreglar permisos de archivos
	@echo "$(GREEN)Arreglando permisos...$(NC)"
	@sudo chown -R $$USER:$$USER storage bootstrap/cache public/build
	@sudo chmod -R 755 storage bootstrap/cache public/build
	@sudo chown -R $$USER:$$USER node_modules
	@echo "$(GREEN)✓ Permisos arreglados$(NC)"

clean: ## Limpiar archivos temporales
	@echo "$(GREEN)Limpiando archivos temporales...$(NC)"
	@rm -rf node_modules/.vite-temp
	@rm -rf public/build
	@$(MAKE) cache-clear
	@echo "$(GREEN)✓ Limpieza completada$(NC)"

# ================================
# DESARROLLO RÁPIDO
# ================================

fresh-start: ## Inicio limpio completo
	@echo "$(GREEN)Iniciando proyecto desde cero...$(NC)"
	@$(MAKE) stop
	@$(MAKE) clean
	@$(MAKE) start
	@sleep 5
	@$(MAKE) migrate-fresh
	@$(MAKE) build
	@echo "$(GREEN)✓ Proyecto reiniciado$(NC)"

quick-setup: ## Setup rápido para desarrollo
	@echo "$(GREEN)Configuración rápida...$(NC)"
	@$(MAKE) start
	@$(MAKE) migrate
	@$(MAKE) build
	@echo "$(GREEN)✓ Setup completado$(NC)"

# ================================
# COMANDOS ESPECÍFICOS DEL PROYECTO
# ================================

create-user: ## Crear usuario demo (usar: make create-user EMAIL="test@test.com" PASSWORD="password")
	@docker exec $(LARAVEL_CONTAINER) php artisan tinker --execute=" \
		\$$company = App\\Models\\Company::firstOrCreate(['email' => '$(EMAIL)'], ['name' => 'Demo Company', 'subdomain' => 'demo']); \
		\$$user = App\\Models\\User::firstOrCreate(['email' => '$(EMAIL)'], ['name' => 'Demo User', 'company_id' => \$$company->id, 'password' => bcrypt('$(PASSWORD)')]); \
		echo 'Usuario creado: ' . \$$user->email . ' - Contraseña: $(PASSWORD)'; \
	"

routes: ## Mostrar todas las rutas
	@docker exec $(LARAVEL_CONTAINER) php artisan route:list

models: ## Mostrar todos los modelos
	@docker exec $(LARAVEL_CONTAINER) php artisan model:show --all

# ================================
# INFORMACIÓN DEL SISTEMA
# ================================

info: ## Mostrar información del sistema
	@echo "$(GREEN)=== Water Master - Información del Sistema ===$(NC)"
	@echo "$(YELLOW)URL de la aplicación:$(NC) http://localhost:9901"
	@echo "$(YELLOW)URL de Vite:$(NC) http://localhost:5174"
	@echo "$(YELLOW)PostgreSQL:$(NC) localhost:9902"
	@echo "$(YELLOW)Usuario demo:$(NC) demo@watermaster.com"
	@echo "$(YELLOW)Contraseña demo:$(NC) password123"
	@echo ""
	@echo "$(GREEN)=== Estado de contenedores ===$(NC)"
	@$(MAKE) status

version: ## Mostrar versiones
	@echo "$(GREEN)=== Versiones del Sistema ===$(NC)"
	@echo "$(YELLOW)Laravel:$(NC)"
	@docker exec $(LARAVEL_CONTAINER) php artisan --version
	@echo "$(YELLOW)PHP:$(NC)"
	@docker exec $(LARAVEL_CONTAINER) php --version | head -1
	@echo "$(YELLOW)Node.js:$(NC)"
	@node --version
	@echo "$(YELLOW)NPM:$(NC)"
	@npm --version

# ================================
# COMANDOS DE EMERGENCIA
# ================================

emergency-reset: ## Reset completo en caso de problemas
	@echo "$(RED)⚠️  RESET COMPLETO - Esto eliminará todos los datos$(NC)"
	@read -p "¿Estás seguro? (y/N): " confirm && [ "$$confirm" = "y" ]
	@$(DOCKER_COMPOSE) down -v
	@docker system prune -f
	@rm -rf node_modules
	@rm -rf vendor
	@$(MAKE) install

# ================================
# ALIAS COMUNES
# ================================

up: start ## Alias para start
down: stop ## Alias para stop
bash: shell ## Alias para shell