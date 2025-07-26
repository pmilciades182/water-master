# Water Master API Documentation

## 🔗 Base URL
```
http://localhost:9901/api
```

## 🔐 Autenticación

Todas las rutas API requieren autenticación. El sistema utiliza autenticación basada en sesiones de Laravel.

### Login
```http
POST /login
Content-Type: application/json

{
  "email": "demo@watermaster.com",
  "password": "password123",
  "remember": false
}
```

### Logout
```http
POST /logout
```

### Obtener Usuario Actual
```http
GET /api/user
```

**Respuesta:**
```json
{
  "user": {
    "id": 1,
    "name": "Demo User",
    "email": "demo@watermaster.com",
    "company_id": 1,
    "company": {
      "id": 1,
      "name": "Aguetería Demo",
      "subdomain": "demo"
    }
  }
}
```

## 📊 Dashboard

### Obtener Datos del Dashboard
```http
GET /api/dashboard
```

## 👥 Gestión de Usuarios

### Listar Usuarios
```http
GET /api/users
```

### Crear Usuario
```http
POST /api/users
Content-Type: application/json

{
  "name": "Nuevo Usuario",
  "email": "usuario@empresa.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

## 🛡️ Gestión de Roles

### Listar Roles
```http
GET /api/roles
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Admin",
      "description": "Administrador completo",
      "company_id": 1,
      "permissions": [...]
    }
  ]
}
```

### Crear Rol
```http
POST /api/roles
Content-Type: application/json

{
  "name": "Técnico",
  "description": "Técnico de campo",
  "permissions": [1, 2, 3]
}
```

### Actualizar Rol
```http
PUT /api/roles/{id}
Content-Type: application/json

{
  "name": "Técnico Senior",
  "description": "Técnico especializado",
  "permissions": [1, 2, 3, 4, 5]
}
```

### Eliminar Rol
```http
DELETE /api/roles/{id}
```

### Obtener Estadísticas de Roles
```http
GET /api/roles/stats
```

### Clonar Rol
```http
POST /api/roles/{id}/clone
Content-Type: application/json

{
  "name": "Técnico - Copia"
}
```

### Sincronizar Permisos
```http
POST /api/roles/{id}/permissions/sync
Content-Type: application/json

{
  "permissions": [1, 2, 3, 4]
}
```

## 🔑 Gestión de Permisos

### Listar Permisos
```http
GET /api/permissions
```

### Crear Permiso
```http
POST /api/permissions
Content-Type: application/json

{
  "name": "products.create",
  "description": "Crear productos",
  "module": "products"
}
```

### Permisos Agrupados por Módulo
```http
GET /api/permissions/grouped-by-module
```

**Respuesta:**
```json
{
  "products": [
    {
      "id": 1,
      "name": "products.view",
      "description": "Ver productos"
    },
    {
      "id": 2,
      "name": "products.create",
      "description": "Crear productos"
    }
  ],
  "users": [...]
}
```

### Crear Permisos CRUD
```http
POST /api/permissions/create-crud
Content-Type: application/json

{
  "module": "invoices",
  "description": "Gestión de facturas"
}
```

## 📦 Gestión de Productos

### Listar Productos
```http
GET /api/products
```

**Parámetros de consulta:**
- `category_id`: Filtrar por categoría
- `search`: Búsqueda por nombre, SKU o descripción
- `low_stock`: Mostrar solo productos con stock bajo
- `per_page`: Productos por página (default: 15)

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Tubería PVC 1/2\"",
      "description": "Tubería PVC presión 1/2 pulgada",
      "sku": "TUB-PVC-12",
      "price": "15000.00",
      "stock": 50,
      "min_stock": 10,
      "is_active": true,
      "category": {
        "id": 1,
        "name": "Tuberías"
      },
      "attributes": {
        "material": "PVC",
        "diametro": "1/2\"",
        "presion": "10kg/cm²"
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### Crear Producto
```http
POST /api/products
Content-Type: application/json

{
  "category_id": 1,
  "name": "Tubería PVC 3/4\"",
  "description": "Tubería PVC presión 3/4 pulgada",
  "sku": "TUB-PVC-34",
  "price": 18000,
  "stock": 30,
  "min_stock": 5,
  "attributes": {
    "material": "PVC",
    "diametro": "3/4\"",
    "presion": "10kg/cm²",
    "longitud": "6m"
  },
  "is_active": true
}
```

### Obtener Producto
```http
GET /api/products/{id}
```

### Actualizar Producto
```http
PUT /api/products/{id}
Content-Type: application/json

{
  "name": "Tubería PVC 3/4\" Reforzada",
  "price": 19000,
  "stock": 25
}
```

### Eliminar Producto
```http
DELETE /api/products/{id}
```

## 📂 Gestión de Categorías de Productos

### Listar Categorías
```http
GET /api/product-categories
```

**Respuesta:**
```json
[
  {
    "id": 1,
    "name": "Tuberías",
    "description": "Tuberías de diferentes materiales y diámetros",
    "is_active": true,
    "config": {
      "attributes": {
        "tipo": ["PVC", "Hierro", "Cobre"],
        "diametro": ["1/2\"", "3/4\"", "1\"", "2\""],
        "material": ["PVC", "Hierro galvanizado", "Cobre"],
        "longitud": ["3m", "6m", "12m"],
        "presion": ["6kg/cm²", "10kg/cm²", "16kg/cm²"]
      }
    },
    "products_count": 15
  }
]
```

### Crear Categoría
```http
POST /api/product-categories
Content-Type: application/json

{
  "name": "Válvulas",
  "description": "Válvulas y llaves de paso",
  "config": {
    "attributes": {
      "tipo": ["Esférica", "Compuerta", "Mariposa"],
      "material": ["Bronce", "Hierro", "PVC"],
      "diametro": ["1/2\"", "3/4\"", "1\"", "2\""]
    }
  },
  "is_active": true
}
```

### Actualizar Categoría
```http
PUT /api/product-categories/{id}
Content-Type: application/json

{
  "name": "Válvulas y Llaves",
  "description": "Válvulas, llaves de paso y accesorios de control"
}
```

### Eliminar Categoría
```http
DELETE /api/product-categories/{id}
```

**Nota:** No se puede eliminar una categoría que tenga productos asociados.

## 👨‍👩‍👧‍👦 Gestión de Roles de Usuario

### Asignar Rol a Usuario
```http
POST /api/user-roles/assign
Content-Type: application/json

{
  "user_id": 2,
  "role_id": 3
}
```

### Remover Rol de Usuario
```http
POST /api/user-roles/remove
Content-Type: application/json

{
  "user_id": 2,
  "role_id": 3
}
```

### Sincronizar Roles de Usuario
```http
POST /api/user-roles/sync
Content-Type: application/json

{
  "user_id": 2,
  "roles": [1, 3, 5]
}
```

### Obtener Roles de Usuario
```http
GET /api/user-roles/users/{userId}/roles
```

### Obtener Usuarios de un Rol
```http
GET /api/user-roles/roles/{roleId}/users
```

### Estadísticas de Roles
```http
GET /api/user-roles/stats
```

## 📝 Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK - Solicitud exitosa |
| 201 | Created - Recurso creado exitosamente |
| 400 | Bad Request - Error en la solicitud |
| 401 | Unauthorized - No autenticado |
| 403 | Forbidden - Sin permisos |
| 404 | Not Found - Recurso no encontrado |
| 422 | Unprocessable Entity - Error de validación |
| 500 | Internal Server Error - Error del servidor |

## 🔄 Estructura de Respuestas de Error

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

## 🏷️ Headers Requeridos

Todas las solicitudes deben incluir:

```http
Content-Type: application/json
Accept: application/json
X-Requested-With: XMLHttpRequest
```

## 🔒 Multi-Tenancy

Todos los endpoints automáticamente filtran datos por la empresa (`company_id`) del usuario autenticado. No es necesario especificar el `company_id` en las solicitudes.

## 🧪 Testing con cURL

### Ejemplo completo de autenticación y uso:

```bash
# 1. Login
curl -X POST http://localhost:9901/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@watermaster.com","password":"password123"}' \
  -c cookies.txt

# 2. Obtener productos
curl -X GET http://localhost:9901/api/products \
  -H "Accept: application/json" \
  -b cookies.txt

# 3. Crear producto
curl -X POST http://localhost:9901/api/products \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -b cookies.txt \
  -d '{
    "category_id": 1,
    "name": "Tubería Test",
    "price": 15000,
    "stock": 10,
    "min_stock": 2
  }'
```

## 📊 Paginación

Los endpoints que retornan listas incluyen información de paginación:

```json
{
  "data": [...],
  "links": {
    "first": "http://localhost:9901/api/products?page=1",
    "last": "http://localhost:9901/api/products?page=10",
    "prev": null,
    "next": "http://localhost:9901/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```