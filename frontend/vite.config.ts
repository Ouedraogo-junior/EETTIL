import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath, URL } from 'node:url'

const API = 'http://127.0.0.1:8000'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    port: 5173,
    // L'API et le front partagent le même domaine en dev : indispensable pour le cookie Sanctum.
    proxy: {
      '/api': API,
      '/sanctum': API,
    },
  },
})