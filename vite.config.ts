import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
  // Bind ke IPv4 127.0.0.1 secara eksplisit. Node di Windows me-resolve
  // 'localhost' ke ::1 terlebih dahulu, dan alamat IPv6 literal tidak sah
  // sebagai sumber Content-Security-Policy sehingga aset dev server diblokir.
  server: {
    host: '127.0.0.1',
  },
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.tsx'],
      refresh: true,
    }),
    inertia(),
    react({
      babel: {
        plugins: ['babel-plugin-react-compiler'],
      },
    }),
    tailwindcss(),
    wayfinder({
      formVariants: true,
    }),
  ],
});
