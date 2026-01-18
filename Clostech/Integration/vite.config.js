import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

export default defineConfig({
  plugins: [react()],
  define: {
    'process.env': {},
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process': JSON.stringify({})
  },
  build: {
    outDir: 'view/frontend/web',
    emptyOutDir: false,
    lib: {
      entry: resolve(__dirname, 'src/App.jsx'),
      name: 'ClostechtryOn',
      formats: ['iife'],
      fileName: () => 'js/tryon-bundle.js'
    },
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/tryon-styles.css'
          }
          return assetInfo.name
        }
      }
    }
  }
})