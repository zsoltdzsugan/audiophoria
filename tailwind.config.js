import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors, { transparent } from 'tailwindcss/colors.js';

/** @type {import('tailwindcss').Config} */
export default {
    presets: [
        require("./vendor/wireui/wireui/tailwind.config.js")
    ],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        "./vendor/wireui/wireui/src/*.php",
        "./vendor/wireui/wireui/ts/**/*.ts",
        "./vendor/wireui/wireui/src/WireUi/**/*.php",
        "./vendor/wireui/wireui/src/Components/**/*.php",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
                serif: ['Denk One', ...defaultTheme.fontFamily.serif],
                cursive: ['Audiowide', 'cursive'],
            },
            colors: {
                surface: {
                    50: colors.violet[50],              // Light surface color
                    950: colors.indigo[950],     // Dark surface color
                    alt: {
                        100: colors.violet[100],      // Light surface alt color
                        950: colors.violet[950], // Dark surface alt color
                    },
                },
                primary: {
                    500: colors.orange[500],            // Light primary color
                    600: colors.orange[600],
                },
                secondary: {
                    600: colors.purple[600],          // Light secondary color
                    200: colors.purple[600],
                    500: colors.lime[500],    // Dark secondary color
                },
                positive: colors.green[400],           // Positive color
                negative: colors.red[500],             // Negative color
                warning: colors.amber[500],            // Warning color
                info: colors.sky[500],                 // Info color
                on: {
                    surface: {
                        600: colors.slate[600],        // Light on-surface color
                        100: colors.violet[100],  // Dark on-surface color
                        strong: {
                            800: colors.purple[800],// Light on-surface strong color
                            100: colors.slate[100], // Dark on-surface strong color
                        },
                    },
                    primary: {
                        100: colors.slate[100],       // Light on-primary color
                    },
                    secondary: {
                        100: colors.slate[100],     // Light on-secondary color
                        950: colors.slate[950],     // Dark on-secondary color
                    },
                    positive: colors.slate[900],           // Positive color
                    negative: colors.slate[100],           // Negative color
                    warning: colors.slate[900],            // Warning color
                    info: colors.slate[100],               // Info color
                },
                outline: {
                    300: colors.slate[300],           // Light outline color
                    700: colors.slate[700],     // Dark outline color
                    strong: {
                        800: colors.slate[800],   // Light outline strong color
                        400: colors.slate[400],// Dark outline strong color
                    },
                },
            },
            borderRadius: {
                radius: defaultTheme.borderRadius.xl,
            },
        },
    },

    plugins: [forms],
};
