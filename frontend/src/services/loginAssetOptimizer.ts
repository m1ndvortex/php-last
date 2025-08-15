// Advanced login asset optimization service
import { loginPerformanceService } from './loginPerformanceService';

export interface AssetOptimizationConfig {
  enableCriticalCSS: boolean;
  enableResourceHints: boolean;
  enableImageOptimization: boolean;
  enableFontOptimization: boolean;
  enableJSOptimization: boolean;
  enableServiceWorkerCaching: boolean;
}

export interface CriticalCSSConfig {
  inlineThreshold: number; // Size threshold for inlining CSS
  criticalViewportHeight: number;
  criticalSelectors: string[];
}

class LoginAssetOptimizer {
  private config: AssetOptimizationConfig = {
    enableCriticalCSS: true,
    enableResourceHints: true,
    enableImageOptimization: true,
    enableFontOptimization: true,
    enableJSOptimization: true,
    enableServiceWorkerCaching: true,
  };

  private criticalCSSConfig: CriticalCSSConfig = {
    inlineThreshold: 14000, // 14KB threshold for critical CSS
    criticalViewportHeight: 1080,
    criticalSelectors: [
      '.min-h-screen',
      '.bg-gray-50',
      '.flex',
      '.justify-center',
      '.card',
      '.form-input',
      '.form-label',
      '.btn',
      '.btn-primary',
      '.text-red-600',
      '.text-gray-900',
      '.text-gray-600',
      '.animate-spin',
    ],
  };

  private optimizedAssets: Set<string> = new Set();
  private criticalCSS: string = '';

  // Initialize all optimizations
  async initialize(): Promise<void> {
    console.log('[LoginAssetOptimizer] Initializing optimizations...');
    
    const startTime = performance.now();
    
    try {
      // Run optimizations in parallel for better performance
      await Promise.all([
        this.optimizeCriticalCSS(),
        this.addResourceHints(),
        this.optimizeFonts(),
        this.optimizeImages(),
        this.enableServiceWorkerCaching(),
      ]);

      const optimizationTime = performance.now() - startTime;
      console.log(`[LoginAssetOptimizer] Optimizations completed in ${optimizationTime.toFixed(2)}ms`);
      
      // Track optimization performance
      console.log(`[LoginAssetOptimizer] Optimization metrics: ${optimizationTime.toFixed(2)}ms`);
    } catch (error) {
      console.error('[LoginAssetOptimizer] Optimization failed:', error);
    }
  }

  // Extract and inline critical CSS
  private async optimizeCriticalCSS(): Promise<void> {
    if (!this.config.enableCriticalCSS) return;

    try {
      // Generate critical CSS for login page
      const criticalCSS = this.generateCriticalCSS();
      
      if (criticalCSS.length > 0 && criticalCSS.length <= this.criticalCSSConfig.inlineThreshold) {
        this.inlineCriticalCSS(criticalCSS);
        console.log(`[LoginAssetOptimizer] Critical CSS inlined (${criticalCSS.length} bytes)`);
      }
    } catch (error) {
      console.error('[LoginAssetOptimizer] Critical CSS optimization failed:', error);
    }
  }

  // Generate critical CSS based on above-the-fold content
  private generateCriticalCSS(): string {
    const criticalRules: string[] = [];
    
    // Essential layout styles for login page
    const essentialCSS = `
      .min-h-screen { min-height: 100vh; }
      .bg-gray-50 { background-color: #f9fafb; }
      .flex { display: flex; }
      .flex-col { flex-direction: column; }
      .justify-center { justify-content: center; }
      .items-center { align-items: center; }
      .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
      .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
      .sm\\:px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
      .lg\\:px-8 { padding-left: 2rem; padding-right: 2rem; }
      .sm\\:mx-auto { margin-left: auto; margin-right: auto; }
      .sm\\:w-full { width: 100%; }
      .sm\\:max-w-md { max-width: 28rem; }
      .text-center { text-align: center; }
      .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
      .font-bold { font-weight: 700; }
      .text-gray-900 { color: #111827; }
      .mt-2 { margin-top: 0.5rem; }
      .mt-8 { margin-top: 2rem; }
      .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
      .text-gray-600 { color: #4b5563; }
      .card { background-color: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); }
      .form-input { 
        appearance: none; 
        border-radius: 0.375rem; 
        border: 1px solid #d1d5db; 
        padding: 0.5rem 0.75rem; 
        font-size: 0.875rem; 
        line-height: 1.25rem; 
        width: 100%; 
      }
      .form-input:focus { 
        outline: 2px solid transparent; 
        outline-offset: 2px; 
        border-color: #3b82f6; 
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); 
      }
      .form-label { 
        display: block; 
        font-size: 0.875rem; 
        line-height: 1.25rem; 
        font-weight: 500; 
        color: #374151; 
        margin-bottom: 0.25rem; 
      }
      .btn { 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 0.375rem; 
        font-size: 0.875rem; 
        line-height: 1.25rem; 
        font-weight: 500; 
        padding: 0.5rem 1rem; 
        transition: all 0.15s ease-in-out; 
        cursor: pointer; 
        border: none; 
      }
      .btn-primary { 
        background-color: #3b82f6; 
        color: white; 
      }
      .btn-primary:hover { 
        background-color: #2563eb; 
      }
      .btn-primary:disabled { 
        opacity: 0.5; 
        cursor: not-allowed; 
      }
      .w-full { width: 100%; }
      .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.5rem; }
      .mb-4 { margin-bottom: 1rem; }
      .p-3 { padding: 0.75rem; }
      .bg-red-50 { background-color: #fef2f2; }
      .border { border-width: 1px; }
      .border-red-200 { border-color: #fecaca; }
      .rounded-md { border-radius: 0.375rem; }
      .text-red-600 { color: #dc2626; }
      .animate-spin { animation: spin 1s linear infinite; }
      @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
      .opacity-50 { opacity: 0.5; }
      .cursor-not-allowed { cursor: not-allowed; }
      .relative { position: relative; }
      .absolute { position: absolute; }
      .inset-y-0 { top: 0; bottom: 0; }
      .right-0 { right: 0; }
      .pr-3 { padding-right: 0.75rem; }
      .pr-10 { padding-right: 2.5rem; }
      .h-5 { height: 1.25rem; }
      .w-5 { width: 1.25rem; }
      .text-gray-400 { color: #9ca3af; }
      .h-4 { height: 1rem; }
      .w-4 { width: 1rem; }
      .ml-2 { margin-left: 0.5rem; }
      .block { display: block; }
      .font-medium { font-weight: 500; }
      .text-primary-600 { color: #2563eb; }
      .hover\\:text-primary-500:hover { color: #3b82f6; }
      .mt-6 { margin-top: 1.5rem; }
      .mt-1 { margin-top: 0.25rem; }
      .-ml-1 { margin-left: -0.25rem; }
      .mr-3 { margin-right: 0.75rem; }
      .text-white { color: white; }
      .opacity-25 { opacity: 0.25; }
      .opacity-75 { opacity: 0.75; }
    `;

    return essentialCSS.replace(/\s+/g, ' ').trim();
  }

  // Inline critical CSS in document head
  private inlineCriticalCSS(css: string): void {
    // Remove existing critical CSS if present
    const existingStyle = document.getElementById('critical-css');
    if (existingStyle) {
      existingStyle.remove();
    }

    // Create and inject critical CSS
    const style = document.createElement('style');
    style.id = 'critical-css';
    style.textContent = css;
    
    // Insert before any existing stylesheets
    const firstLink = document.querySelector('link[rel="stylesheet"]');
    if (firstLink) {
      document.head.insertBefore(style, firstLink);
    } else {
      document.head.appendChild(style);
    }

    this.criticalCSS = css;
  }

  // Add resource hints for better loading performance
  private async addResourceHints(): Promise<void> {
    if (!this.config.enableResourceHints) return;

    const hints = [
      // DNS prefetch for external resources
      { rel: 'dns-prefetch', href: '//fonts.googleapis.com' },
      { rel: 'dns-prefetch', href: '//fonts.gstatic.com' },
      
      // Preconnect to critical origins
      { rel: 'preconnect', href: '//fonts.googleapis.com', crossorigin: true },
      { rel: 'preconnect', href: '//fonts.gstatic.com', crossorigin: true },
      
      // Prefetch critical routes
      { rel: 'prefetch', href: '/dashboard' },
      { rel: 'prefetch', href: '/api/auth/user' },
      
      // Preload critical assets
      { rel: 'preload', href: '/assets/css/app.css', as: 'style' },
      { rel: 'preload', href: '/assets/js/app.js', as: 'script' },
    ];

    hints.forEach(hint => {
      const existingHint = document.querySelector(`link[rel="${hint.rel}"][href="${hint.href}"]`);
      if (!existingHint) {
        const link = document.createElement('link');
        link.rel = hint.rel;
        link.href = hint.href;
        
        if (hint.as) {
          link.setAttribute('as', hint.as);
        }
        if (hint.crossorigin) {
          link.setAttribute('crossorigin', 'anonymous');
        }
        
        document.head.appendChild(link);
      }
    });

    console.log(`[LoginAssetOptimizer] Added ${hints.length} resource hints`);
  }

  // Optimize font loading
  async optimizeFonts(): Promise<void> {
    if (!this.config.enableFontOptimization) return;

    try {
      // Add font-display: swap to existing font links
      const fontLinks = document.querySelectorAll('link[href*="fonts.googleapis.com"]');
      fontLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && !href.includes('display=swap')) {
          const separator = href.includes('?') ? '&' : '?';
          link.setAttribute('href', `${href}${separator}display=swap`);
        }
      });

      // Preload critical fonts
      const criticalFonts = [
        '/assets/fonts/inter-var.woff2',
        '/assets/fonts/vazir-var.woff2',
      ];

      criticalFonts.forEach(fontUrl => {
        const existingPreload = document.querySelector(`link[rel="preload"][href="${fontUrl}"]`);
        if (!existingPreload) {
          const link = document.createElement('link');
          link.rel = 'preload';
          link.href = fontUrl;
          link.as = 'font';
          link.type = 'font/woff2';
          link.setAttribute('crossorigin', 'anonymous');
          document.head.appendChild(link);
        }
      });

      console.log('[LoginAssetOptimizer] Font optimization completed');
    } catch (error) {
      console.error('[LoginAssetOptimizer] Font optimization failed:', error);
    }
  }

  // Optimize images for faster loading
  async optimizeImages(): Promise<void> {
    if (!this.config.enableImageOptimization) return;

    try {
      // Add loading and decoding attributes to images
      const images = document.querySelectorAll('img');
      images.forEach((img, index) => {
        // Above-the-fold images should load eagerly
        if (index < 2 || img.getBoundingClientRect().top < window.innerHeight) {
          img.loading = 'eager';
          img.decoding = 'sync';
        } else {
          img.loading = 'lazy';
          img.decoding = 'async';
        }

        // Add error handling
        img.onerror = () => {
          console.warn(`[LoginAssetOptimizer] Image failed to load: ${img.src}`);
        };
      });

      // Preload critical images
      const criticalImages = [
        '/assets/images/logo.svg',
        '/assets/images/login-bg.jpg',
      ];

      criticalImages.forEach(imageUrl => {
        const existingPreload = document.querySelector(`link[rel="preload"][href="${imageUrl}"]`);
        if (!existingPreload) {
          const link = document.createElement('link');
          link.rel = 'preload';
          link.href = imageUrl;
          link.as = 'image';
          document.head.appendChild(link);
        }
      });

      console.log('[LoginAssetOptimizer] Image optimization completed');
    } catch (error) {
      console.error('[LoginAssetOptimizer] Image optimization failed:', error);
    }
  }

  // Enable service worker caching for assets
  private async enableServiceWorkerCaching(): Promise<void> {
    if (!this.config.enableServiceWorkerCaching || !('serviceWorker' in navigator)) return;

    try {
      const registration = await navigator.serviceWorker.getRegistration();
      if (registration) {
        // Service worker is already registered, send cache optimization message
        if (registration.active) {
          registration.active.postMessage({
            type: 'OPTIMIZE_LOGIN_CACHE',
            assets: [
              '/assets/css/app.css',
              '/assets/js/app.js',
              '/api/auth/csrf-token',
              '/assets/fonts/inter-var.woff2',
              '/assets/fonts/vazir-var.woff2',
            ],
          });
        }
        console.log('[LoginAssetOptimizer] Service worker cache optimization requested');
      }
    } catch (error) {
      console.error('[LoginAssetOptimizer] Service worker optimization failed:', error);
    }
  }

  // Optimize JavaScript loading
  private optimizeJavaScript(): void {
    if (!this.config.enableJSOptimization) return;

    try {
      // Add async/defer attributes to non-critical scripts
      const scripts = document.querySelectorAll('script[src]');
      scripts.forEach(script => {
        const src = script.getAttribute('src');
        if (src && !src.includes('app.js') && !script.hasAttribute('async') && !script.hasAttribute('defer')) {
          script.setAttribute('defer', '');
        }
      });

      // Preload critical JavaScript modules
      const criticalModules = [
        '/assets/js/vue-vendor.js',
        '/assets/js/auth-vendor.js',
      ];

      criticalModules.forEach(moduleUrl => {
        const existingPreload = document.querySelector(`link[rel="modulepreload"][href="${moduleUrl}"]`);
        if (!existingPreload) {
          const link = document.createElement('link');
          link.rel = 'modulepreload';
          link.href = moduleUrl;
          document.head.appendChild(link);
        }
      });

      console.log('[LoginAssetOptimizer] JavaScript optimization completed');
    } catch (error) {
      console.error('[LoginAssetOptimizer] JavaScript optimization failed:', error);
    }
  }

  // Remove unused CSS (simplified version)
  private removeUnusedCSS(): void {
    try {
      // This is a simplified version - in production, you'd use a tool like PurgeCSS
      const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
      stylesheets.forEach(stylesheet => {
        // Mark non-critical stylesheets for lazy loading
        if (!stylesheet.getAttribute('href')?.includes('critical')) {
          stylesheet.setAttribute('media', 'print');
          stylesheet.setAttribute('onload', "this.media='all'");
        }
      });

      console.log('[LoginAssetOptimizer] Unused CSS removal completed');
    } catch (error) {
      console.error('[LoginAssetOptimizer] CSS optimization failed:', error);
    }
  }

  // Get optimization status
  getOptimizationStatus() {
    return {
      config: { ...this.config },
      optimizedAssets: Array.from(this.optimizedAssets),
      criticalCSSSize: this.criticalCSS.length,
      criticalCSSInlined: this.criticalCSS.length > 0,
    };
  }

  // Update configuration
  updateConfig(newConfig: Partial<AssetOptimizationConfig>): void {
    this.config = { ...this.config, ...newConfig };
    console.log('[LoginAssetOptimizer] Configuration updated:', this.config);
  }

  // Force re-optimization
  async reoptimize(): Promise<void> {
    this.optimizedAssets.clear();
    this.criticalCSS = '';
    await this.initialize();
  }

  // Clean up optimizations
  cleanup(): void {
    // Remove critical CSS
    const criticalStyle = document.getElementById('critical-css');
    if (criticalStyle) {
      criticalStyle.remove();
    }

    // Reset optimized assets
    this.optimizedAssets.clear();
    this.criticalCSS = '';

    console.log('[LoginAssetOptimizer] Cleanup completed');
  }
}

// Create singleton instance
export const loginAssetOptimizer = new LoginAssetOptimizer();
export default loginAssetOptimizer;