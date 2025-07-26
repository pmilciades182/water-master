# Water Master - Biblioteca de Componentes

## 📚 Índice
1. [Introducción](#introducción)
2. [Principios de Diseño](#principios-de-diseño)
3. [Tokens de Diseño](#tokens-de-diseño)
4. [Componentes Base](#componentes-base)
5. [Componentes Complejos](#componentes-complejos)
6. [Patrones de Uso](#patrones-de-uso)
7. [Guías de Implementación](#guías-de-implementación)
8. [Testing y Validación](#testing-y-validación)

---

## 🎨 Introducción

La biblioteca de componentes de Water Master está basada en **Microsoft Fluent Design System**, proporcionando una experiencia consistente, accesible y profesional para la gestión de sistemas de agua.

### **Objetivos Principales**
- ✅ **Consistencia**: Misma experiencia en toda la aplicación
- ♿ **Accesibilidad**: WCAG 2.1 AA compliance
- 🚀 **Rendimiento**: Componentes optimizados para carga rápida
- 🔧 **Mantenibilidad**: Código reutilizable y bien documentado
- 📱 **Responsividad**: Funciona en todos los dispositivos

---

## 🏗️ Principios de Diseño

### **1. Claridad**
Los componentes comunican su propósito de forma clara e inmediata.

### **2. Eficiencia**
Optimizados para tareas comunes del usuario, minimizando el esfuerzo cognitivo.

### **3. Coherencia**
Comportamiento predecible y patrones familiares en toda la aplicación.

### **4. Belleza**
Diseño limpio y profesional que inspira confianza.

---

## 🎯 Tokens de Diseño

### **Colores**
```css
/* Primarios */
--ms-color-primary: #0078d4;           /* Microsoft Blue */
--ms-color-primary-hover: #106ebe;     /* Estado hover */
--ms-color-primary-pressed: #005a9e;   /* Estado pressed */

/* Neutrales */
--ms-color-neutral-white: #ffffff;     /* Fondo principal */
--ms-color-neutral-grey2: #fafafa;     /* Fondo secundario */
--ms-color-neutral-grey84: #201f1e;    /* Texto principal */

/* Semánticos */
--ms-color-success: #107c10;           /* Estados de éxito */
--ms-color-warning: #797620;           /* Advertencias */
--ms-color-danger: #a4262c;            /* Errores y acciones peligrosas */
```

### **Tipografía**
```css
/* Familias */
--ms-font-family-base: 'Segoe UI', system-ui, sans-serif;
--ms-font-family-monospace: 'Cascadia Code', monospace;

/* Tamaños */
--ms-font-size-100: 0.75rem;   /* 12px - Caption */
--ms-font-size-200: 0.875rem;  /* 14px - Body */
--ms-font-size-300: 1rem;      /* 16px - Body Large */
--ms-font-size-400: 1.125rem;  /* 18px - Subtitle */
--ms-font-size-500: 1.25rem;   /* 20px - Title 3 */
--ms-font-size-600: 1.5rem;    /* 24px - Title 2 */
--ms-font-size-700: 1.75rem;   /* 28px - Title 1 */
```

### **Espaciado**
```css
/* Sistema de 4px */
--ms-spacing-xxs: 0.25rem;    /* 4px */
--ms-spacing-xs: 0.5rem;      /* 8px */
--ms-spacing-s: 0.75rem;      /* 12px */
--ms-spacing-m: 1rem;         /* 16px */
--ms-spacing-l: 1.25rem;      /* 20px */
--ms-spacing-xl: 1.5rem;      /* 24px */
--ms-spacing-xxl: 2rem;       /* 32px */
```

---

## 🧩 Componentes Base

### **1. Botones**

#### **Button Primary**
Acción principal de la página o formulario.

```vue
<template>
  <button class="ms-button ms-button-primary">
    <i class="fas fa-save"></i>
    Guardar
  </button>
</template>
```

**Estados:**
- Default: Azul Microsoft (#0078d4)
- Hover: Azul más oscuro (#106ebe)
- Pressed: Azul presionado (#005a9e)
- Disabled: Opacidad 60%

**Uso:**
- ✅ Guardar formularios
- ✅ Confirmar acciones importantes
- ✅ Call-to-action principal
- ❌ Acciones secundarias

#### **Button Secondary**
Acciones secundarias o alternativas.

```vue
<template>
  <button class="ms-button ms-button-secondary">
    <i class="fas fa-times"></i>
    Cancelar
  </button>
</template>
```

**Estados:**
- Default: Fondo blanco, borde gris
- Hover: Fondo gris claro
- Pressed: Fondo gris
- Disabled: Opacidad 60%

**Uso:**
- ✅ Cancelar acciones
- ✅ Acciones alternativas
- ✅ Navegación secundaria
- ❌ Acciones principales

#### **Button Subtle**
Acciones de menor importancia, enlaces-botón.

```vue
<template>
  <button class="ms-button ms-button-subtle">
    <i class="fas fa-edit"></i>
    Editar
  </button>
</template>
```

**Uso:**
- ✅ Acciones de edición inline
- ✅ Enlaces que actúan como botones
- ✅ Acciones terciarias
- ❌ Acciones principales

---

### **2. Inputs y Formularios**

#### **Input Field**
Campo de entrada de texto estándar.

```vue
<template>
  <div class="ms-form-group">
    <label for="email" class="ms-label ms-label-required">
      Correo electrónico
    </label>
    <input 
      id="email"
      v-model="email"
      type="email"
      class="ms-input"
      :class="{ 'ms-input-error': hasError }"
      placeholder="usuario@empresa.com"
    />
    <p v-if="helpText" class="ms-form-help">
      {{ helpText }}
    </p>
    <p v-if="errorMessage" class="ms-form-error">
      {{ errorMessage }}
    </p>
  </div>
</template>
```

**Estados:**
- Default: Borde gris neutro
- Focus: Borde azul Microsoft + shadow
- Error: Borde rojo + texto error
- Disabled: Fondo gris + cursor disabled

**Variantes:**
- `ms-input`: Input estándar
- `ms-select`: Dropdown select
- `ms-textarea`: Área de texto multilinea

#### **Form Group Structure**
```html
<div class="ms-form-group">
  <label class="ms-label [ms-label-required]">Label</label>
  <input class="ms-input [ms-input-error]" />
  <p class="ms-form-help">Helper text</p>
  <p class="ms-form-error">Error message</p>
</div>
```

---

### **3. Cards**

#### **Card Base**
Contenedor principal para agrupar contenido relacionado.

```vue
<template>
  <div class="ms-card">
    <div class="ms-card-header">
      <h3 class="ms-font-title3">Título de la Card</h3>
      <button class="ms-button ms-button-subtle">
        <i class="fas fa-ellipsis-h"></i>
      </button>
    </div>
    <div class="ms-card-body">
      <p class="ms-font-body">Contenido de la card...</p>
    </div>
    <div class="ms-card-footer">
      <button class="ms-button ms-button-primary">Acción</button>
      <button class="ms-button ms-button-secondary">Cancelar</button>
    </div>
  </div>
</template>
```

**Características:**
- Elevación sutil (shadow-2)
- Bordes redondeados (8px)
- Hover effect (shadow-8)
- Estructura semántica clara

---

### **4. Navigation**

#### **Nav Item**
Elemento de navegación lateral.

```vue
<template>
  <router-link 
    to="/users" 
    class="ms-nav-item"
    :class="{ active: isActive }"
  >
    <i class="fas fa-users"></i>
    Usuarios
  </router-link>
</template>
```

**Estados:**
- Default: Texto gris, fondo transparente
- Hover: Fondo gris claro
- Active: Fondo azul claro, texto azul
- Focus: Outline azul

---

### **5. Tables**

#### **Data Table**
Tabla para mostrar datos estructurados.

```vue
<template>
  <table class="ms-table">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Email</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="user in users" :key="user.id">
        <td class="ms-font-body-strong">{{ user.name }}</td>
        <td class="ms-font-body">{{ user.email }}</td>
        <td>
          <span 
            class="ms-badge" 
            :class="user.active ? 'ms-badge-success' : 'ms-badge-neutral'"
          >
            {{ user.active ? 'Activo' : 'Inactivo' }}
          </span>
        </td>
        <td>
          <button class="ms-button ms-button-subtle">
            <i class="fas fa-edit"></i>
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</template>
```

---

### **6. Badges**

#### **Status Badges**
Indicadores de estado y categorización.

```vue
<template>
  <span 
    class="ms-badge"
    :class="{
      'ms-badge-success': status === 'active',
      'ms-badge-warning': status === 'pending',
      'ms-badge-error': status === 'error',
      'ms-badge-neutral': status === 'inactive'
    }"
  >
    {{ statusText }}
  </span>
</template>
```

**Variantes:**
- `ms-badge-success`: Estados positivos (verde)
- `ms-badge-warning`: Advertencias (amarillo)
- `ms-badge-error`: Errores (rojo)
- `ms-badge-neutral`: Estados neutros (gris)

---

## 🏗️ Componentes Complejos

### **1. Modales**

#### **Modal Base Structure**
```vue
<template>
  <div class="ms-modal-overlay" @click="handleBackdropClick">
    <div class="ms-modal" @click.stop>
      <div class="ms-modal-header">
        <h3 class="ms-modal-title">Título del Modal</h3>
        <button @click="$emit('close')" class="ms-button-subtle">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div class="ms-modal-body">
        <!-- Contenido del modal -->
        <slot />
      </div>
      
      <div class="ms-modal-footer">
        <button @click="$emit('close')" class="ms-button ms-button-secondary">
          Cancelar
        </button>
        <button @click="$emit('confirm')" class="ms-button ms-button-primary">
          Confirmar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineEmits(['close', 'confirm'])

const handleBackdropClick = (event) => {
  if (event.target === event.currentTarget) {
    emit('close')
  }
}
</script>
```

**Características:**
- Backdrop blur effect
- Animaciones suaves (fadeIn + slideUp)
- Escape key handling
- Focus trap
- Accesibilidad completa

#### **Modal Variants**

##### **Confirmation Modal**
```vue
<template>
  <ConfirmationModal
    v-if="showDeleteModal"
    title="Confirmar eliminación"
    message="¿Estás seguro de que quieres eliminar este usuario?"
    confirm-text="Eliminar"
    confirm-variant="danger"
    @confirm="handleDelete"
    @cancel="showDeleteModal = false"
  />
</template>
```

##### **Form Modal**
```vue
<template>
  <FormModal
    v-if="showUserModal"
    :title="isEdit ? 'Editar Usuario' : 'Nuevo Usuario'"
    :loading="loading"
    @submit="handleSubmit"
    @cancel="showUserModal = false"
  >
    <UserForm v-model="userForm" :errors="errors" />
  </FormModal>
</template>
```

---

### **2. Data Tables Avanzadas**

#### **DataTable Component**
```vue
<template>
  <div class="ms-data-table-container">
    <!-- Filtros y búsqueda -->
    <div class="ms-data-table-toolbar">
      <div class="flex items-center space-x-4">
        <input 
          v-model="searchTerm"
          class="ms-input"
          placeholder="Buscar..."
        />
        <select v-model="statusFilter" class="ms-select">
          <option value="">Todos</option>
          <option value="active">Activos</option>
          <option value="inactive">Inactivos</option>
        </select>
      </div>
      
      <button @click="$emit('create')" class="ms-button ms-button-primary">
        <i class="fas fa-plus"></i>
        Nuevo
      </button>
    </div>
    
    <!-- Tabla -->
    <table class="ms-table">
      <thead>
        <tr>
          <th v-for="column in columns" :key="column.key">
            <button 
              v-if="column.sortable"
              @click="handleSort(column.key)"
              class="flex items-center space-x-1"
            >
              <span>{{ column.label }}</span>
              <i 
                class="fas text-xs"
                :class="getSortIcon(column.key)"
              ></i>
            </button>
            <span v-else>{{ column.label }}</span>
          </th>
        </tr>
      </thead>
      
      <tbody>
        <tr v-for="item in paginatedItems" :key="item.id">
          <td v-for="column in columns" :key="column.key">
            <slot 
              :name="`cell-${column.key}`" 
              :item="item" 
              :value="item[column.key]"
            >
              {{ item[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
    
    <!-- Paginación -->
    <Pagination
      v-model:page="currentPage"
      :total="filteredItems.length"
      :per-page="itemsPerPage"
    />
  </div>
</template>
```

**Características:**
- Búsqueda en tiempo real
- Filtros múltiples
- Ordenamiento por columnas
- Paginación
- Slots personalizables
- Loading states
- Empty states

---

### **3. Forms Complejos**

#### **Multi-step Form**
```vue
<template>
  <div class="ms-wizard">
    <!-- Progress indicator -->
    <div class="ms-wizard-progress">
      <div 
        v-for="(step, index) in steps"
        :key="step.id"
        class="ms-wizard-step"
        :class="{
          'active': currentStep === index,
          'completed': currentStep > index,
          'pending': currentStep < index
        }"
      >
        <div class="ms-wizard-step-indicator">
          <i v-if="currentStep > index" class="fas fa-check"></i>
          <span v-else>{{ index + 1 }}</span>
        </div>
        <span class="ms-wizard-step-label">{{ step.label }}</span>
      </div>
    </div>
    
    <!-- Step content -->
    <div class="ms-wizard-content">
      <component 
        :is="steps[currentStep].component"
        v-model="formData"
        :errors="errors"
        @next="handleNext"
        @previous="handlePrevious"
      />
    </div>
  </div>
</template>
```

---

## 📋 Patrones de Uso

### **1. Gestión de Estado de Formularios**

#### **Composable para Forms**
```javascript
// composables/useForm.js
export function useForm(initialData = {}, validation = {}) {
  const form = ref({ ...initialData })
  const errors = ref({})
  const loading = ref(false)
  
  const validate = () => {
    const newErrors = {}
    
    Object.keys(validation).forEach(field => {
      const rules = validation[field]
      const value = form.value[field]
      
      rules.forEach(rule => {
        if (!rule.validator(value)) {
          newErrors[field] = newErrors[field] || []
          newErrors[field].push(rule.message)
        }
      })
    })
    
    errors.value = newErrors
    return Object.keys(newErrors).length === 0
  }
  
  const submit = async (submitFn) => {
    if (!validate()) return false
    
    loading.value = true
    try {
      await submitFn(form.value)
      return true
    } catch (error) {
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
      }
      return false
    } finally {
      loading.value = false
    }
  }
  
  const reset = () => {
    form.value = { ...initialData }
    errors.value = {}
  }
  
  return {
    form,
    errors,
    loading,
    validate,
    submit,
    reset
  }
}
```

#### **Uso del Composable**
```vue
<script setup>
import { useForm } from '@/composables/useForm'

const { form, errors, loading, submit, reset } = useForm(
  { name: '', email: '', password: '' },
  {
    name: [
      { validator: v => v.trim().length > 0, message: 'Nombre requerido' },
      { validator: v => v.length >= 2, message: 'Mínimo 2 caracteres' }
    ],
    email: [
      { validator: v => v.includes('@'), message: 'Email inválido' }
    ]
  }
)

const handleSubmit = () => {
  submit(async (data) => {
    await api.post('/users', data)
    emit('created')
    reset()
  })
}
</script>
```

### **2. Gestión de Estado de Listas**

#### **Composable para Data Tables**
```javascript
// composables/useDataTable.js
export function useDataTable(fetchFn, options = {}) {
  const items = ref([])
  const loading = ref(false)
  const searchTerm = ref('')
  const sortBy = ref(options.defaultSort?.key || '')
  const sortDirection = ref(options.defaultSort?.direction || 'asc')
  const currentPage = ref(1)
  const itemsPerPage = ref(options.itemsPerPage || 10)
  
  const filteredItems = computed(() => {
    let filtered = items.value
    
    if (searchTerm.value) {
      const search = searchTerm.value.toLowerCase()
      filtered = filtered.filter(item =>
        Object.values(item).some(value =>
          String(value).toLowerCase().includes(search)
        )
      )
    }
    
    if (sortBy.value) {
      filtered.sort((a, b) => {
        const aVal = a[sortBy.value]
        const bVal = b[sortBy.value]
        const result = aVal < bVal ? -1 : aVal > bVal ? 1 : 0
        return sortDirection.value === 'desc' ? -result : result
      })
    }
    
    return filtered
  })
  
  const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return filteredItems.value.slice(start, end)
  })
  
  const load = async () => {
    loading.value = true
    try {
      items.value = await fetchFn()
    } finally {
      loading.value = false
    }
  }
  
  const handleSort = (key) => {
    if (sortBy.value === key) {
      sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortBy.value = key
      sortDirection.value = 'asc'
    }
  }
  
  return {
    items,
    loading,
    searchTerm,
    sortBy,
    sortDirection,
    currentPage,
    itemsPerPage,
    filteredItems,
    paginatedItems,
    load,
    handleSort
  }
}
```

---

## 🧪 Testing y Validación

### **1. Component Testing**

#### **Testing Buttons**
```javascript
// tests/components/Button.test.js
import { mount } from '@vue/test-utils'
import Button from '@/components/Button.vue'

describe('Button Component', () => {
  it('renders primary button correctly', () => {
    const wrapper = mount(Button, {
      props: { variant: 'primary' },
      slots: { default: 'Save' }
    })
    
    expect(wrapper.classes()).toContain('ms-button-primary')
    expect(wrapper.text()).toBe('Save')
  })
  
  it('handles click events', async () => {
    const wrapper = mount(Button)
    await wrapper.trigger('click')
    
    expect(wrapper.emitted('click')).toBeTruthy()
  })
  
  it('shows loading state', () => {
    const wrapper = mount(Button, {
      props: { loading: true }
    })
    
    expect(wrapper.find('.fa-spinner').exists()).toBe(true)
    expect(wrapper.attributes('disabled')).toBeDefined()
  })
})
```

#### **Testing Forms**
```javascript
// tests/components/UserForm.test.js
describe('UserForm Component', () => {
  it('validates required fields', async () => {
    const wrapper = mount(UserForm)
    
    // Submit without filling required fields
    await wrapper.find('form').trigger('submit')
    
    expect(wrapper.find('.ms-form-error').exists()).toBe(true)
    expect(wrapper.emitted('submit')).toBeFalsy()
  })
  
  it('submits valid form data', async () => {
    const wrapper = mount(UserForm)
    
    // Fill form
    await wrapper.find('#name').setValue('John Doe')
    await wrapper.find('#email').setValue('john@example.com')
    
    // Submit
    await wrapper.find('form').trigger('submit')
    
    expect(wrapper.emitted('submit')).toBeTruthy()
    expect(wrapper.emitted('submit')[0][0]).toEqual({
      name: 'John Doe',
      email: 'john@example.com'
    })
  })
})
```

### **2. Accessibility Testing**

#### **A11y Tests**
```javascript
// tests/accessibility/components.test.js
import { axe, toHaveNoViolations } from 'jest-axe'

expect.extend(toHaveNoViolations)

describe('Accessibility Tests', () => {
  it('Button should be accessible', async () => {
    const wrapper = mount(Button, {
      props: { variant: 'primary' },
      slots: { default: 'Save' }
    })
    
    const results = await axe(wrapper.html())
    expect(results).toHaveNoViolations()
  })
  
  it('Form should be accessible', async () => {
    const wrapper = mount(UserForm)
    const results = await axe(wrapper.html())
    expect(results).toHaveNoViolations()
  })
})
```

### **3. Visual Regression Testing**

#### **Chromatic Setup**
```javascript
// .storybook/main.js
module.exports = {
  stories: ['../src/**/*.stories.js'],
  addons: ['@storybook/addon-essentials']
}
```

#### **Component Stories**
```javascript
// stories/Button.stories.js
export default {
  title: 'Components/Button',
  component: Button,
  argTypes: {
    variant: {
      control: { type: 'select' },
      options: ['primary', 'secondary', 'subtle']
    }
  }
}

export const Primary = {
  args: {
    variant: 'primary',
    children: 'Primary Button'
  }
}

export const Loading = {
  args: {
    variant: 'primary',
    loading: true,
    children: 'Loading...'
  }
}
```

---

## 📱 Responsive Guidelines

### **Breakpoints**
```css
/* Mobile First Approach */
.ms-component {
  /* Mobile styles (default) */
}

@media (min-width: 768px) {
  .ms-component {
    /* Tablet styles */
  }
}

@media (min-width: 1024px) {
  .ms-component {
    /* Desktop styles */
  }
}

@media (min-width: 1280px) {
  .ms-component {
    /* Large desktop styles */
  }
}
```

### **Component Adaptations**

#### **Responsive Tables**
```vue
<template>
  <div class="ms-table-responsive">
    <!-- Desktop: Traditional table -->
    <table class="ms-table hidden md:table">
      <!-- Table content -->
    </table>
    
    <!-- Mobile: Card layout -->
    <div class="md:hidden space-y-4">
      <div v-for="item in items" :key="item.id" class="ms-card">
        <div class="ms-card-body">
          <h3 class="ms-font-body-strong">{{ item.name }}</h3>
          <p class="ms-font-body text-gray-600">{{ item.email }}</p>
          <div class="mt-2 flex justify-end">
            <button class="ms-button ms-button-subtle">
              <i class="fas fa-edit"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
```

---

## 🔄 Versionado y Actualizaciones

### **Semantic Versioning**
- **Major** (1.0.0): Breaking changes
- **Minor** (0.1.0): New features
- **Patch** (0.0.1): Bug fixes

### **Changelog Format**
```markdown
## [1.2.0] - 2024-01-15

### Added
- New DataTable component with advanced filtering
- Dark theme support for all components

### Changed
- Button hover states improved for better accessibility
- Form validation messages now support HTML

### Fixed
- Modal focus trap issue on Safari
- Input field placeholder contrast ratio

### Deprecated
- Old Button component (use ms-button instead)
```

---

## 📚 Recursos y Referencias

### **Documentación Oficial**
- [Microsoft Fluent Design](https://fluent2.microsoft.design/)
- [Vue.js Style Guide](https://vuejs.org/style-guide/)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)

### **Herramientas de Desarrollo**
- **Storybook**: Desarrollo aislado de componentes
- **Vue DevTools**: Debugging y performance
- **axe-core**: Testing de accesibilidad

### **Comunidad y Soporte**
- **GitHub Issues**: Reportar bugs y feature requests
- **Slack Channel**: `#water-master-design-system`
- **Weekly Design Reviews**: Jueves 3PM

---

**🎯 Meta**: Crear la biblioteca de componentes más completa y accesible para sistemas de gestión de agua en Latinoamérica.