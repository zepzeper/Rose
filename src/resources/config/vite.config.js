// config/vite.config.js
import { defineConfig } from 'vite';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

export default defineConfig({
  // Base public path when served in production
  root: './', // Explicitly set the root directory

  // Configure build settings
  build: {
    // Directory to output built files
    outDir: 'dist',
    
    // Create manifest.json for server-side asset loading
    manifest: true,
    
    // Clean the output directory before build
    emptyOutDir: true,
    
    // Configure input files and chunk naming
    rollupOptions: {
      input: 'resources/js/main.js',
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]'
      }
    }
  },

  // Configure CSS processing
  css: {
    postcss: {
      plugins: [
        tailwindcss,
        autoprefixer,
      ],
    }
  },

  // Development server configuration
  server: {
    // Force specific port for PHP integration predictability
    strictPort: true,
    port: 5173,
    
    // Enable CORS for PHP <-> Vite communication
    cors: true,
    
    // Configure hot module replacement
    hmr: {
      host: 'localhost'
    }
  },

  // Resolve aliases for easier imports
  resolve: {
    alias: {
      '@': '/resources'
    }
  }
});
