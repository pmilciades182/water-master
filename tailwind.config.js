/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './storage/framework/views/*.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php'
  ],
  
  theme: {
    extend: {
      // Microsoft Fluent Design Colors
      colors: {
        primary: {
          50: 'var(--color-primary-50)',
          100: 'var(--color-primary-100)',
          200: 'var(--color-primary-200)',
          300: 'var(--color-primary-300)',
          400: 'var(--color-primary-400)',
          500: 'var(--color-primary-500)', // Microsoft Blue
          600: 'var(--color-primary-600)',
          700: 'var(--color-primary-700)',
          800: 'var(--color-primary-800)',
          900: 'var(--color-primary-900)',
        },
        
        neutral: {
          0: 'var(--color-neutral-0)',
          10: 'var(--color-neutral-10)',
          20: 'var(--color-neutral-20)',
          30: 'var(--color-neutral-30)',
          40: 'var(--color-neutral-40)',
          50: 'var(--color-neutral-50)',
          60: 'var(--color-neutral-60)',
          70: 'var(--color-neutral-70)',
          80: 'var(--color-neutral-80)',
          90: 'var(--color-neutral-90)',
          100: 'var(--color-neutral-100)',
          110: 'var(--color-neutral-110)',
          120: 'var(--color-neutral-120)',
          130: 'var(--color-neutral-130)',
          140: 'var(--color-neutral-140)',
          150: 'var(--color-neutral-150)',
          160: 'var(--color-neutral-160)',
        },
        
        success: {
          primary: 'var(--color-success-primary)',
          tint10: 'var(--color-success-tint-10)',
          tint20: 'var(--color-success-tint-20)',
          shade10: 'var(--color-success-shade-10)',
          shade20: 'var(--color-success-shade-20)',
        },
        
        warning: {
          primary: 'var(--color-warning-primary)',
          tint10: 'var(--color-warning-tint-10)',
          tint20: 'var(--color-warning-tint-20)',
          shade10: 'var(--color-warning-shade-10)',
          shade20: 'var(--color-warning-shade-20)',
        },
        
        error: {
          primary: 'var(--color-error-primary)',
          tint10: 'var(--color-error-tint-10)',
          tint20: 'var(--color-error-tint-20)',
          shade10: 'var(--color-error-shade-10)',
          shade20: 'var(--color-error-shade-20)',
        }
      },
      
      // Microsoft Typography Scale
      fontFamily: {
        sans: ['Segoe UI', '-apple-system', 'BlinkMacSystemFont', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Open Sans', 'Helvetica Neue', 'sans-serif'],
        mono: ['Cascadia Code', 'Consolas', 'Courier New', 'monospace'],
      },
      
      fontSize: {
        'caption': ['0.75rem', { lineHeight: '1rem' }],      // 12px/16px
        'body': ['0.875rem', { lineHeight: '1.25rem' }],     // 14px/20px
        'body-strong': ['0.875rem', { lineHeight: '1.25rem', fontWeight: '600' }], // 14px/20px
        'subtitle': ['1rem', { lineHeight: '1.375rem' }],    // 16px/22px
        'title3': ['1.125rem', { lineHeight: '1.5rem' }],    // 18px/24px
        'title2': ['1.25rem', { lineHeight: '1.75rem' }],    // 20px/28px
        'title1': ['1.5rem', { lineHeight: '2rem' }],        // 24px/32px
        'large-title': ['2rem', { lineHeight: '2.5rem' }],   // 32px/40px
        'display': ['2.5rem', { lineHeight: '3rem' }],       // 40px/48px
      },
      
      fontWeight: {
        light: '300',
        regular: '400',
        medium: '500',
        semibold: '600',
        bold: '700',
      },
      
      // Microsoft Spacing (4px grid)
      spacing: {
        'xs': '0.25rem',    // 4px
        'sm': '0.5rem',     // 8px
        'md': '0.75rem',    // 12px
        'lg': '1rem',       // 16px
        'xl': '1.25rem',    // 20px
        '2xl': '1.5rem',    // 24px
        '3xl': '2rem',      // 32px
        '4xl': '2.5rem',    // 40px
        '5xl': '3rem',      // 48px
      },
      
      // Microsoft Border Radius
      borderRadius: {
        'sm': '0.125rem',   // 2px
        'md': '0.25rem',    // 4px
        'lg': '0.5rem',     // 8px
        'xl': '0.75rem',    // 12px
      },
      
      // Microsoft Shadows (Fluent depth)
      boxShadow: {
        '2': 'var(--shadow-2)',
        '4': 'var(--shadow-4)',
        '8': 'var(--shadow-8)',
        '16': 'var(--shadow-16)',
        '64': 'var(--shadow-64)',
      },
      
      // Microsoft Animation Curves
      transitionTimingFunction: {
        'acceleration-max': 'var(--curve-acceleration-max)',
        'acceleration-mid': 'var(--curve-acceleration-mid)',
        'acceleration-min': 'var(--curve-acceleration-min)',
        'deceleration-max': 'var(--curve-deceleration-max)',
        'deceleration-mid': 'var(--curve-deceleration-mid)',
        'deceleration-min': 'var(--curve-deceleration-min)',
        'max-easy-ease': 'var(--curve-max-easy-ease)',
        'easy-ease': 'var(--curve-easy-ease)',
      },
      
      // Microsoft Animation Durations
      transitionDuration: {
        'ultra-fast': 'var(--duration-ultra-fast)',
        'faster': 'var(--duration-faster)',
        'fast': 'var(--duration-fast)',
        'normal': 'var(--duration-normal)',
        'slow': 'var(--duration-slow)',
        'slower': 'var(--duration-slower)',
      },
      
      // Grid and Layout
      container: {
        center: true,
        padding: {
          DEFAULT: '1rem',
          sm: '2rem',
          lg: '4rem',
          xl: '5rem',
          '2xl': '6rem',
        },
      },
    },
  },
  
  plugins: [
    // Custom plugin for Microsoft Fluent Design components
    function({ addComponents, theme }) {
      addComponents({
        // Microsoft Button Components
        '.btn-fluent': {
          display: 'inline-flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: theme('spacing.sm'),
          padding: `${theme('spacing.sm')} ${theme('spacing.lg')}`,
          fontFamily: theme('fontFamily.sans'),
          fontSize: theme('fontSize.body')[0],
          fontWeight: theme('fontWeight.medium'),
          lineHeight: theme('fontSize.body')[1].lineHeight,
          border: '1px solid transparent',
          borderRadius: theme('borderRadius.md'),
          cursor: 'pointer',
          transition: 'all var(--duration-fast) var(--curve-easy-ease)',
          textDecoration: 'none',
          minHeight: '2rem',
          outline: 'none',
          position: 'relative',
          userSelect: 'none',
          
          '&:focus-visible': {
            outline: '2px solid var(--color-primary-500)',
            outlineOffset: '2px',
          },
          
          '&:disabled': {
            opacity: '0.6',
            cursor: 'not-allowed',
          },
        },
        
        '.btn-fluent-primary': {
          backgroundColor: 'var(--color-primary-500)',
          borderColor: 'var(--color-primary-500)',
          color: 'var(--color-neutral-0)',
          
          '&:hover:not(:disabled)': {
            backgroundColor: 'var(--color-primary-600)',
            borderColor: 'var(--color-primary-600)',
          },
          
          '&:active:not(:disabled)': {
            backgroundColor: 'var(--color-primary-700)',
            borderColor: 'var(--color-primary-700)',
          },
        },
        
        '.btn-fluent-secondary': {
          backgroundColor: 'var(--color-neutral-0)',
          borderColor: 'var(--color-neutral-60)',
          color: 'var(--color-neutral-160)',
          
          '&:hover:not(:disabled)': {
            backgroundColor: 'var(--color-neutral-20)',
            borderColor: 'var(--color-neutral-70)',
          },
          
          '&:active:not(:disabled)': {
            backgroundColor: 'var(--color-neutral-30)',
            borderColor: 'var(--color-neutral-80)',
          },
        },
        
        // Microsoft Card Components
        '.card-fluent': {
          backgroundColor: 'var(--color-neutral-0)',
          border: '1px solid var(--color-neutral-40)',
          borderRadius: theme('borderRadius.lg'),
          boxShadow: 'var(--shadow-2)',
          transition: 'box-shadow var(--duration-normal) var(--curve-easy-ease)',
          
          '&:hover': {
            boxShadow: 'var(--shadow-8)',
          },
        },
        
        // Microsoft Input Components
        '.input-fluent': {
          width: '100%',
          padding: `${theme('spacing.sm')} ${theme('spacing.md')}`,
          fontFamily: theme('fontFamily.sans'),
          fontSize: theme('fontSize.body')[0],
          lineHeight: theme('fontSize.body')[1].lineHeight,
          border: '1px solid var(--color-neutral-60)',
          borderRadius: theme('borderRadius.md'),
          backgroundColor: 'var(--color-neutral-0)',
          color: 'var(--color-neutral-160)',
          transition: 'border-color var(--duration-fast) var(--curve-easy-ease)',
          
          '&:focus': {
            outline: 'none',
            borderColor: 'var(--color-primary-500)',
            boxShadow: '0 0 0 1px var(--color-primary-500)',
          },
          
          '&:disabled': {
            backgroundColor: 'var(--color-neutral-20)',
            borderColor: 'var(--color-neutral-40)',
            color: 'var(--color-neutral-90)',
            cursor: 'not-allowed',
          },
        },
      })
    },
  ],
}