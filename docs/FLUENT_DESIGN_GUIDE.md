# Microsoft Fluent Design System Implementation Guide

## Overview

This project implements Microsoft's Fluent Design System to provide a modern, accessible, and consistent user interface that follows Microsoft's design principles and standards.

## Design Principles

### 1. **Light**
- Clean, open layouts with proper use of whitespace
- Subtle shadows and depth cues
- Minimal visual noise

### 2. **Depth**
- Layered information architecture
- Proper use of elevation (shadows)
- Visual hierarchy through typography and spacing

### 3. **Motion**
- Smooth, purposeful animations
- Natural transition curves
- Reduced motion support for accessibility

### 4. **Material**
- Real-world inspired textures and effects
- Consistent surface treatments
- Proper use of transparency and blur

### 5. **Scale**
- Responsive design across all devices
- Adaptive typography and spacing
- Consistent experiences across platforms

## Color System

### Primary Colors
```css
--color-primary-500: #0078d4;  /* Microsoft Blue - Primary brand color */
```

### Neutral Palette
Based on Microsoft's official neutral color tokens:
- `neutral-0` to `neutral-160`: Complete grayscale spectrum
- Proper contrast ratios for accessibility (WCAG 2.1 AA compliance)

### Semantic Colors
- **Success**: `#107c10` (Microsoft Green)
- **Warning**: `#ffc83d` (Microsoft Yellow)
- **Error**: `#d13438` (Microsoft Red)

## Typography

### Font Stack
Primary: `'Segoe UI'` - Microsoft's flagship font
Fallbacks: System fonts for cross-platform compatibility

### Type Ramp
Following Microsoft's official type scale:

| Token | Size | Line Height | Usage |
|-------|------|-------------|-------|
| `caption` | 12px | 16px | Metadata, captions |
| `body` | 14px | 20px | Body text, labels |
| `body-strong` | 14px | 20px | Emphasized body text |
| `subtitle` | 16px | 22px | Subtitles, section headers |
| `title3` | 18px | 24px | Card titles |
| `title2` | 20px | 28px | Page section titles |
| `title1` | 24px | 32px | Page titles |
| `large-title` | 32px | 40px | Hero titles |
| `display` | 40px | 48px | Display text |

## Spacing System

Based on 4px grid system:
- `xs`: 4px
- `sm`: 8px
- `md`: 12px
- `lg`: 16px
- `xl`: 20px
- `2xl`: 24px
- `3xl`: 32px
- `4xl`: 40px
- `5xl`: 48px

## Component Library

### Buttons

#### Primary Button
```vue
<button class="ms-button ms-button-primary">
  Primary Action
</button>
```

#### Secondary Button
```vue
<button class="ms-button ms-button-secondary">
  Secondary Action
</button>
```

#### Subtle Button
```vue
<button class="ms-button ms-button-subtle">
  Subtle Action
</button>
```

### Cards

#### Basic Card
```vue
<div class="ms-card">
  <div class="ms-card-header">
    <h3 class="ms-font-title3">Card Title</h3>
  </div>
  <div class="ms-card-body">
    <p class="ms-font-body">Card content goes here.</p>
  </div>
  <div class="ms-card-footer">
    <button class="ms-button ms-button-primary">Action</button>
  </div>
</div>
```

### Form Controls

#### Input Field
```vue
<div class="ms-form-group">
  <label for="input" class="ms-label ms-label-required">
    Label Text
  </label>
  <input 
    id="input" 
    class="ms-input" 
    type="text" 
    placeholder="Placeholder text"
  />
  <p class="ms-form-help">
    Helper text goes here
  </p>
</div>
```

#### Error State
```vue
<input class="ms-input ms-input-error" />
<p class="ms-form-error">Error message</p>
```

### Navigation

#### Navigation Item
```vue
<router-link to="/path" class="ms-nav-item">
  <i class="fas fa-icon"></i>
  Navigation Item
</router-link>
```

#### Active Navigation Item
```vue
<router-link to="/path" class="ms-nav-item active">
  <i class="fas fa-icon"></i>
  Active Item
</router-link>
```

### Tables

#### Data Table
```vue
<table class="ms-table">
  <thead>
    <tr>
      <th>Column Header</th>
      <th>Column Header</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Cell Data</td>
      <td>Cell Data</td>
    </tr>
  </tbody>
</table>
```

### Badges

#### Status Badges
```vue
<span class="ms-badge ms-badge-success">Success</span>
<span class="ms-badge ms-badge-warning">Warning</span>
<span class="ms-badge ms-badge-error">Error</span>
<span class="ms-badge ms-badge-neutral">Neutral</span>
```

### Modals

#### Standard Modal
```vue
<div class="ms-modal-overlay">
  <div class="ms-modal">
    <div class="ms-modal-header">
      <h3 class="ms-modal-title">Modal Title</h3>
      <button class="ms-button-subtle">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="ms-modal-body">
      Modal content goes here
    </div>
    <div class="ms-modal-footer">
      <button class="ms-button ms-button-secondary">Cancel</button>
      <button class="ms-button ms-button-primary">Confirm</button>
    </div>
  </div>
</div>
```

## Elevation System

Use elevation to create depth and hierarchy:

```css
.ms-elevation-2  /* Subtle elevation for cards */
.ms-elevation-4  /* Medium elevation for dropdowns */
.ms-elevation-8  /* Higher elevation for popups */
.ms-elevation-16 /* High elevation for dialogs */
.ms-elevation-64 /* Maximum elevation for modals */
```

## Animation Guidelines

### Transition Durations
- `ultra-fast`: 50ms - Micro-interactions
- `faster`: 100ms - Small UI changes
- `fast`: 150ms - Standard interactions
- `normal`: 200ms - Default duration
- `slow`: 300ms - Complex animations
- `slower`: 500ms - Page transitions

### Transition Curves
- `easy-ease`: Standard easing for most animations
- `deceleration-mid`: Entrance animations
- `acceleration-mid`: Exit animations

### Implementation
```css
.my-element {
  transition: all var(--duration-fast) var(--curve-easy-ease);
}
```

## Accessibility

### Focus Management
All interactive elements have proper focus indicators:
```css
.ms-focus-visible:focus-visible {
  outline: 2px solid var(--color-primary-500);
  outline-offset: 2px;
}
```

### High Contrast Support
```css
@media (prefers-contrast: high) {
  .ms-button {
    border-width: 2px;
  }
}
```

### Reduced Motion Support
```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

## Usage Examples

### Page Layout
```vue
<template>
  <MainLayout page-title="Page Title">
    <div class="space-y-6">
      <!-- Page content with proper spacing -->
      <div class="ms-card">
        <div class="ms-card-header">
          <h2 class="ms-font-title2">Section Title</h2>
        </div>
        <div class="ms-card-body">
          <!-- Card content -->
        </div>
      </div>
    </div>
  </MainLayout>
</template>
```

### Form Layout
```vue
<form class="space-y-4">
  <div class="ms-form-group">
    <label class="ms-label ms-label-required">Name</label>
    <input class="ms-input" type="text" />
  </div>
  
  <div class="ms-form-group">
    <label class="ms-label">Description</label>
    <textarea class="ms-textarea"></textarea>
    <p class="ms-form-help">Optional helper text</p>
  </div>
  
  <div class="flex justify-end space-x-3">
    <button type="button" class="ms-button ms-button-secondary">
      Cancel
    </button>
    <button type="submit" class="ms-button ms-button-primary">
      Save
    </button>
  </div>
</form>
```

## Implementation Notes

1. **Consistent Spacing**: Always use the defined spacing tokens instead of arbitrary values
2. **Proper Typography**: Use the type ramp classes for consistent text hierarchy
3. **Color Usage**: Stick to the defined color palette for brand consistency
4. **Component Composition**: Build complex UI by composing the basic components
5. **Accessibility First**: Always include proper ARIA labels and keyboard navigation

## Browser Support

- Chrome 88+
- Firefox 85+
- Safari 14+
- Edge 88+

## Resources

- [Microsoft Fluent Design System](https://fluent2.microsoft.design/)
- [Fluent UI Components](https://react.fluentui.dev/)
- [Design Tokens](https://docs.microsoft.com/en-us/fluent-ui/web-components/design-system/design-tokens)