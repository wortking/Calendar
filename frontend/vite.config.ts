import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      // Desactivado en dev: con HMR cambiando archivos todo el tiempo, el
      // service worker queda con un bundle viejo cacheado (p.ej. referencias
      // a módulos ya borrados) y sirve una página rota. La instalabilidad de
      // la PWA se prueba contra `npm run build && npm run preview`, no contra
      // el dev server.
      devOptions: {
        enabled: false,
      },
      manifest: {
        name: 'Calendar',
        short_name: 'Calendar',
        description: 'Gestión de tareas y proyectos',
        theme_color: '#1976d2',
        background_color: '#ffffff',
        display: 'standalone',
        start_url: '/',
        icons: [
          { src: '/icons/icon-192.svg', sizes: '192x192', type: 'image/svg+xml' },
          { src: '/icons/icon-512.svg', sizes: '512x512', type: 'image/svg+xml' },
          { src: '/icons/icon-512.svg', sizes: '512x512', type: 'image/svg+xml', purpose: 'maskable' },
        ],
      },
    }),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    watch: { usePolling: true },
  },
})
