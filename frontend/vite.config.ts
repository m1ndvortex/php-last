import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { VitePWA } from 'vite-plugin-pwa';
import { resolve } from "path";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue({
      template: {
        compilerOptions: {
          // Enable production optimizations
          hoistStatic: true,
          cacheHandlers: true,
        }
      }
    }),
    VitePWA({
      registerType: 'autoUpdate',
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
        runtimeCaching: [
          {
            urlPattern: /^\/api\/.*/i,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24 // 24 hours
              }
            }
          },
          {
            urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'images-cache',
              expiration: {
                maxEntries: 50,
                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
              }
            }
          },
          {
            urlPattern: /\.(?:js|css)$/,
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'static-resources'
            }
          }
        ]
      },
      manifest: {
        name: 'Jewelry Business Platform',
        short_name: 'Jewelry Platform',
        description: 'Bilingual Persian/English Jewelry Business Management Platform',
        theme_color: '#1f2937',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait',
        scope: '/',
        start_url: '/'
      }
    })
  ],
  resolve: {
    alias: {
      "@": resolve(__dirname, "src"),
    },
  },
  // @ts-ignore
  test: {
    environment: "jsdom",
    globals: true,
    setupFiles: ["./src/test-utils/setup.ts"],
  },
  server: {
    host: "0.0.0.0",
    port: 3000,
    hmr: {
      port: 3000,
    },
    proxy: {
      "/api": {
        target: "http://nginx:80",
        changeOrigin: true,
        secure: false,
      },
    },
  },
  build: {
    outDir: "dist",
    assetsDir: "assets",
    sourcemap: false, // Disable sourcemaps in production for smaller bundles
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true, // Remove console.log in production
        drop_debugger: true,
      },
    },
    rollupOptions: {
      output: {
        // Manual chunk splitting for better caching
        manualChunks: {
          // Vendor chunks
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          'ui-vendor': ['@headlessui/vue', '@heroicons/vue'],
          'chart-vendor': ['chart.js', 'vue-chartjs'],
          'utils-vendor': ['axios', 'lodash-es', 'date-fns'],
          'i18n-vendor': ['vue-i18n', 'date-fns-jalali'],
          'form-vendor': ['vee-validate', '@vee-validate/yup', 'yup'],
          
          // Component chunks
          'dashboard-components': [
            './src/views/DashboardView.vue',
            './src/components/dashboard/KPIWidget.vue',
            './src/components/dashboard/ChartWidget.vue',
            './src/components/dashboard/WidgetGrid.vue'
          ],
          'inventory-components': [
            './src/views/InventoryView.vue',
            './src/components/inventory/InventoryList.vue',
            './src/components/inventory/ItemFormModal.vue'
          ],
          'invoice-components': [
            './src/views/InvoicesView.vue',
            './src/components/invoices/InvoiceList.vue',
            './src/components/invoices/InvoiceFormModal.vue'
          ],
          'customer-components': [
            './src/views/CustomersView.vue',
            './src/components/customers/CustomerList.vue',
            './src/components/customers/CustomerFormModal.vue'
          ],
          'accounting-components': [
            './src/views/AccountingView.vue',
            './src/components/accounting/TransactionManagement.vue',
            './src/components/accounting/FinancialReports.vue'
          ]
        },
        // Optimize chunk file names
        chunkFileNames: (chunkInfo) => {
          const facadeModuleId = chunkInfo.facadeModuleId
            ? chunkInfo.facadeModuleId.split('/').pop()?.replace('.vue', '') || 'chunk'
            : 'chunk';
          return `js/${facadeModuleId}-[hash].js`;
        },
        entryFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name?.split('.') || [];
          const ext = info[info.length - 1];
          if (/\.(css)$/.test(assetInfo.name || '')) {
            return `css/[name]-[hash].${ext}`;
          }
          if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name || '')) {
            return `images/[name]-[hash].${ext}`;
          }
          return `assets/[name]-[hash].${ext}`;
        }
      }
    },
    // Increase chunk size warning limit
    chunkSizeWarningLimit: 1000,
  },
  css: {
    postcss: "./postcss.config.js",
  },
  // Enable experimental features for better performance
  experimental: {
    renderBuiltUrl(filename, { hostType }) {
      if (hostType === 'js') {
        return { js: `/${filename}` };
      }
      return { relative: true };
    }
  },
  // Optimize dependencies
  optimizeDeps: {
    include: [
      'vue',
      'vue-router',
      'pinia',
      '@headlessui/vue',
      '@heroicons/vue/24/outline',
      '@heroicons/vue/24/solid',
      'vue-i18n',
      'axios',
      'lodash-es',
      'date-fns',
      'date-fns-jalali'
    ],
    exclude: ['@vite/client', '@vite/env']
  }
});
