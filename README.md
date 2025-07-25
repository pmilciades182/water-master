# Water Master

Proyecto Laravel con PostgreSQL para gestión de agua.

## Requisitos

- Docker
- Docker Compose

## Configuración del alias Sail (Opcional)

Para usar `sail` en lugar de `./vendor/bin/sail`, agrega este alias a tu `~/.bashrc` o `~/.zshrc`:

```bash
alias sail='bash vendor/bin/sail'
```

Luego ejecuta:
```bash
source ~/.bashrc  # o source ~/.zshrc
```

## Instalación

1. Clonar el repositorio
2. Copiar el archivo de configuración:
   ```bash
   cp .env.example .env
   ```

3. Construir y ejecutar los contenedores:
   ```bash
   docker-compose up --build -d
   ```

4. Instalar dependencias de Laravel:
   ```bash
   docker-compose exec laravel.test composer install
   ```

5. Generar la clave de la aplicación:
   ```bash
   docker-compose exec laravel.test php artisan key:generate
   ```

6. Ejecutar migraciones:
   ```bash
   docker-compose exec laravel.test php artisan migrate
   ```

7. Instalar dependencias de Node.js y compilar assets:
   ```bash
   docker-compose exec -T laravel.test npm install
   docker-compose exec -T laravel.test npm run build
   ```

## Frontend

El proyecto utiliza **Vue.js 3** con **Vite** para el frontend.

### Desarrollo del frontend:
```bash
# Instalar dependencias de Vue.js
docker-compose exec -T laravel.test npm install vue@^3.0.0 @vitejs/plugin-vue

# Compilar assets para desarrollo
docker-compose exec -T laravel.test npm run dev

# Compilar assets para producción
docker-compose exec -T laravel.test npm run build
```

## Servicios

- **Laravel**: http://localhost:9901
- **PostgreSQL**: localhost:9902

## Base de datos

- **Base de datos**: water
- **Usuario**: admin
- **Contraseña**: 12345
- **Puerto**: 9902

## Comandos útiles

### Usando docker-compose directamente:
```bash
# Acceder al contenedor de Laravel
docker-compose exec laravel.test bash

# Ver logs
docker-compose logs -f

# Detener servicios
docker-compose down

# Reiniciar servicios
docker-compose restart
```

### Usando Sail (con alias configurado):
```bash
# Levantar servicios
sail up -d

# Ejecutar comandos Artisan
sail artisan migrate
sail artisan tinker

# Acceder al contenedor
sail shell

# Ver logs
sail logs

# Detener servicios
sail down
```
