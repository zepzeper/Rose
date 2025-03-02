// tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    // Scan your actual view directories
    "./views/**/*.{php,twig}",
    "./resources/js/**/*.js",
  ],
  theme: {
    extend: {
      // You can add theme customizations here
    },
  },
  plugins: [],
  darkMode: 'class',
}
