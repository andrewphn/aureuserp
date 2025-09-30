import {
    Chart,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    TimeScale,
    Filler
} from 'chart.js';

import 'chartjs-adapter-date-fns';

// Register Chart.js components
Chart.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    TimeScale,
    Filler
);

// TCS Woodwork Branding Configuration
const TCS_BRAND_COLORS = {
    primary: '#D4A574',      // TCS Gold
    primaryDark: '#B8935E',  // TCS Gold Dark  
    success: '#4A7C59',      // Forest Green
    background: '#FDF6EC',   // Cream Light
    text: '#8B6914',         // Dark Golden Text
    border: '#E8DCC4',       // Light Border
    grid: 'rgba(212, 165, 116, 0.1)', // Subtle grid
    accent: '#5B8FA3'        // Blue Accent
};

// Apply TCS branding to Chart.js defaults
Chart.defaults.color = TCS_BRAND_COLORS.text;
Chart.defaults.borderColor = TCS_BRAND_COLORS.border;
Chart.defaults.backgroundColor = TCS_BRAND_COLORS.background;

// Default TCS chart configuration
const TCS_CHART_DEFAULTS = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                boxWidth: 20,
                padding: 15,
                color: TCS_BRAND_COLORS.text,
                font: {
                    size: 14,
                    family: "'Inter', sans-serif",
                    weight: '500'
                }
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            titleFont: { size: 16, weight: '600' },
            bodyFont: { size: 14 },
            cornerRadius: 8,
            padding: 12,
            borderColor: TCS_BRAND_COLORS.primary,
            borderWidth: 1
        }
    },
    scales: {
        y: {
            grid: {
                color: TCS_BRAND_COLORS.grid,
                borderColor: TCS_BRAND_COLORS.border
            },
            ticks: {
                color: TCS_BRAND_COLORS.text,
                font: { size: 12 }
            }
        },
        x: {
            grid: {
                color: TCS_BRAND_COLORS.grid,
                borderColor: TCS_BRAND_COLORS.border
            },
            ticks: {
                color: TCS_BRAND_COLORS.text,
                font: { size: 12 }
            }
        }
    },
    // Shop floor tablet optimizations
    elements: {
        point: {
            radius: 6,
            hoverRadius: 8,
            borderWidth: 2
        },
        line: {
            borderWidth: 3,
            tension: 0.2
        },
        bar: {
            borderRadius: 4,
            borderSkipped: false
        }
    }
};

// TCS Color Palette for Charts
const TCS_COLOR_PALETTE = [
    TCS_BRAND_COLORS.primary,
    TCS_BRAND_COLORS.primaryDark,
    TCS_BRAND_COLORS.success,
    TCS_BRAND_COLORS.accent,
    '#8B9DC3',  // Light Blue
    '#DDB892',  // Tan
    '#7F9F8A',  // Sage
    '#A0826D'   // Brown
];

// Utility function to create TCS-branded charts
window.createTcsChart = function(ctx, config) {
    // Merge TCS defaults with user config
    const tcsConfig = {
        ...config,
        options: {
            ...TCS_CHART_DEFAULTS,
            ...config.options
        }
    };

    // Apply TCS color palette if not specified
    if (tcsConfig.data && tcsConfig.data.datasets) {
        tcsConfig.data.datasets.forEach((dataset, index) => {
            if (!dataset.backgroundColor && !dataset.borderColor) {
                const colorIndex = index % TCS_COLOR_PALETTE.length;
                const color = TCS_COLOR_PALETTE[colorIndex];
                
                if (tcsConfig.type === 'line') {
                    dataset.borderColor = color;
                    dataset.backgroundColor = color + '20'; // 20% opacity
                } else {
                    dataset.backgroundColor = color;
                    dataset.borderColor = color;
                }
            }
        });
    }

    return new Chart(ctx, tcsConfig);
};

// Export for use in other modules
window.Chart = Chart;
window.TCS_BRAND_COLORS = TCS_BRAND_COLORS;
window.TCS_COLOR_PALETTE = TCS_COLOR_PALETTE;