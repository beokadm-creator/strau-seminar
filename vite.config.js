import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  build: {
    // Output to the preview theme's dist folder
    outDir: 'theme/preview_modern/dist',
    emptyOutDir: true, // Clean the output directory before building
    manifest: true, // Generate manifest.json for PHP integration
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'src/entries/main.js'),
        // Add more entry points as needed
      },
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]'
      }
    }
  },
  server: {
    // Configuration for dev server
    cors: true,
    strictPort: true,
    port: 5173,
    hmr: {
        host: 'localhost',
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src')
    }
  },
  // css: {
  //   preprocessorOptions: {
  //     scss: {
  //       api: 'modern-compiler',
  //       silenceDeprecations: ['legacy-js-api'],
  //     }
  //   }
  // }
});