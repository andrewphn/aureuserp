import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './resources/views/vendor/**/*.blade.php',
        './resources/css/**/*.css',
        './plugins/webkul/*/resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/guava/filament-knowledge-base/src/**/*.php',
        './vendor/guava/filament-knowledge-base/resources/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Helvetica Neue', 'sans-serif'],
            },
            colors: {
                // TCS Brand Colors
                'tcs-gold': '#D4A574',           // Primary TCS Golden
                'tcs-gold-dark': '#B8935E',      // Darker TCS Golden for hovers
                
                // Project Stage Colors
                'stage-uncategorized': '#64748b', // Slate
                'stage-discovery': '#f59e0b',     // Amber
                'stage-design': '#8b5cf6',        // Violet
                'stage-sourcing': '#0891b2',      // Cyan
                'stage-production': '#dc2626',    // Red
                'stage-delivery': '#059669',      // Emerald
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
    safelist: [
        // TCS Brand Colors
        'bg-tcs-gold',
        'bg-tcs-gold-dark',
        'text-tcs-gold',
        'text-tcs-gold-dark',
        'hover:bg-tcs-gold',
        'hover:bg-tcs-gold-dark',
        'border-tcs-gold',
        'ring-tcs-gold',
        'focus:ring-tcs-gold',
        'focus:border-tcs-gold',
        'text-tcs-gold-dark',
        'dark:text-tcs-gold',
        
        // Kanban stage colors
        'bg-stage-uncategorized',
        'bg-stage-discovery', 
        'bg-stage-design',
        'bg-stage-sourcing',
        'bg-stage-production',
        'bg-stage-delivery',
        'kanban-stage-uncategorized',
        'kanban-stage-discovery',
        'kanban-stage-design',
        'kanban-stage-sourcing',
        'kanban-stage-production',
        'kanban-stage-delivery',
        // Component classes
        'kanban-card',
        'kanban-card-redesigned',
        'kanban-card-header',
        'kanban-card-title', 
        'kanban-card-priority',
        'kanban-card-client',
        'kanban-card-progress',
        'kanban-card-footer',
        'kanban-card-value',
        'kanban-card-date',
        'progress-bar',
        'progress-fill',
        'progress-text',
        'kanban-column',
        'kanban-column-body',
        'kanban-stage-header',
        'stage-count-badge',
        'project-progress-bar',
        'project-progress-fill',
        'priority-high',
        'priority-medium',
        'priority-low',
        // Enhanced Timesheet Calendar active states
        'bg-blue-600',
        'text-white',
        'shadow-md',
        'bg-blue-50',
        'dark:bg-blue-900/20',
        'text-gray-600',
        'dark:text-gray-400',
        'hover:bg-gray-50',
        'dark:hover:bg-gray-700',
        
        // TCS Design Enhancement Classes
        'linear-feet-display',
        'shop-floor-button',
        'timesheet-card',
        'clock-in-button',
        'clock-out-button',
        'status-badge',
        'status-critical',
        'status-on-track',
        'status-at-risk',
        
        // Framework-compliant mobile/tablet classes
        'timesheet-header-widget',
        'clock-action-primary',
        'critical-info',
        'shop-floor-mode',
        'skip-to-content',
        'loading',
        'sr-only',
        
        // Special utility classes
        'min-h-20',
        'z-[45]',
        'z-[100]',
        'border-[3px]',
        'outline-[3px]',
        'scale-[0.98]',
        'min-h-[3.25rem]',
        'min-h-[3.75rem]',
        
        // TCS Chart Widget Classes
        'tcs-chart-widget',
        'tcs-chart-container',

        // Production Estimate Alert System Colors (FilamentPHP semantic colors)
        // Success (GREEN alert)
        'bg-success-50',
        'bg-success-950/30',
        'border-success-300',
        'border-success-700',
        'text-success-700',
        'text-success-300',
        'text-success-600',
        'text-success-400',
        'ring-success-500/20',

        // Warning (AMBER alert)
        'bg-warning-50',
        'bg-warning-950/30',
        'border-warning-300',
        'border-warning-700',
        'text-warning-700',
        'text-warning-300',
        'text-warning-600',
        'text-warning-400',
        'ring-warning-500/20',

        // Danger (RED alert)
        'bg-danger-50',
        'bg-danger-950/30',
        'border-danger-300',
        'border-danger-700',
        'text-danger-700',
        'text-danger-300',
        'text-danger-600',
        'text-danger-400',
        'ring-danger-500/30',

        // Gray (BLACK alert)
        'bg-gray-900',
        'bg-gray-950',
        'border-gray-900',
        'border-gray-950',
        'ring-gray-900/50',

        // Dark mode variants
        'dark:bg-success-950/30',
        'dark:border-success-700',
        'dark:text-success-300',
        'dark:text-success-400',
        'dark:bg-warning-950/30',
        'dark:border-warning-700',
        'dark:text-warning-300',
        'dark:text-warning-400',
        'dark:bg-danger-950/30',
        'dark:border-danger-700',
        'dark:text-danger-300',
        'dark:text-danger-400',
        'dark:bg-gray-950',
        'dark:border-gray-950',

        // Production Estimate Card Gradient Classes
        'bg-gradient-to-br',
        'from-tcs-gold',
        'to-tcs-gold-dark',
        'from-blue-500',
        'to-blue-600',
        'shadow-lg',
        'hover:shadow-xl',
    ]
};
