// config/tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
  // Configure content sources to scan for class names
  content: [
    // PHP view files
    "./resources/views/**/*.php",
    "./app/views/**/*.php",
    
    // JavaScript files
    "./resources/js/**/*.js",
    
    // Templates
    "./templates/**/*.php",
    
    // Any other PHP files that might contain HTML/Tailwind classes
    "./app/**/*.php",
  ],
  
  // Configure theme 
  theme: {
    extend: {
      // Example of extending Tailwind with custom colors
      colors: {
        'primary': {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
        },
      },
      // Example of custom font settings
      fontFamily: {
        sans: ['Inter var', 'sans-serif'],
      },
    },
  },
  
  // Optional plugins
  plugins: [
    // Examples of official Tailwind plugins you might want to include
    // require('@tailwindcss/forms'),
    // require('@tailwindcss/typography'),
  ],
  
  // Set darker dark mode colors (optional)
  darkMode: 'class',
}
