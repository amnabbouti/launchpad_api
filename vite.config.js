import { defineConfig } from 'vite';

export default defineConfig({
  root: './public',
  publicDir: false,
  build: {
    outDir: './dist',
    rollupOptions: {
      input: './public/docs.html',
    },
    assetsInlineLimit: 0,
    copyPublicDir: false,
  },
});
