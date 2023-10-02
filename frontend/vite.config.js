import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server:{
    host: '0.0.0.0',
  },
  base: 'https://elsevarasadov.github.io/Code-Academy-IMS-Video-Downloader/'
})
