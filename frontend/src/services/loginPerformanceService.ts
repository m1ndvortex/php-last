// Login page performance optimization service
import { performanceMonitoringService } from './performanceMonitoringService';

export interface LoginPerformanceMetrics {
  pageLoadTime: number;
  assetLoadTime: number;
  authenticationTime: number;
  redirectTime: number;
  totalLoginTime: number;
  timestamp: Date;
  userAgent: string;
  connectionType?: string;
}

export interface LoginAssetMetrics {
  cssLoadTime: number;
  jsLoadTime: number;
  fontLoadTime: number;
  imageLoadTime: number;
  totalAssetSize: number;
  compressedSize: number;
  cacheHits: number;
  cacheMisses: number;
}

export interface LoginOptimizationReport {
  averageLoadTime: number;
  fastestLogin: LoginPerformanceMetrics | null;
  slowestLogin: LoginPerformanceMetrics | null;
  assetPerformance: LoginAssetMetrics;
  optimizationSuggestions: string[];
  performanceGrade: 'A' | 'B' | 'C' | 'D' | 'F';
}

class LoginPerformanceService {
  private metrics: LoginPerformanceMetrics[] = [];
  private assetMetrics: LoginAssetMetrics = {
    cssLoadTime: 0,
    jsLoadTime: 0,
    fontLoadTime: 0,
    imageLoadTime: 0,
    totalAssetSize: 0,
    compressedSize: 0,
    cacheHits: 0,
    cacheMisses: 0,
  };
  
  private performanceThresholds = {
    excellent: 1000, // Under 1 second
    good: 2000,      // Under 2 seconds (requirement)
    acceptable: 3000, // Under 3 seconds
    poor: 5000,      // Under 5 seconds
  };

  private loginStartTime: number = 0;
  private pageLoadStartTime: number = 0;
  private authStartTime: number = 0;

  // Start tracking login page performance
  startLoginPageTracking(): void {
    this.pageLoadStartTime = performance.now();
    
    // Track page load performance
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        this.trackDOMContentLoaded();
      });
    } else {
      this.trackDOMContentLoaded();
    }

    // Track asset loading
    this.trackAssetLoading();
    
    // Track Web Vitals
    this.trackWebVitals();
  }

  // Track DOM content loaded
  private trackDOMContentLoaded(): void {
    const domLoadTime = performance.now() - this.pageLoadStartTime;
    console.log(`[LoginPerformance] DOM Content Loaded: ${domLoadTime.toFixed(2)}ms`);
    
    performanceMonitoringService.recordLoadingMetrics({
      component: 'login-dom',
      loadTime: domLoadTime,
      timestamp: new Date(),
      isInitialLoad: true,
    });
  }

  // Track asset loading performance
  private trackAssetLoading(): void {
    const observer = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        if (entry.entryType === 'resource') {
          const resourceEntry = entry as PerformanceResourceTiming;
          this.processResourceTiming(resourceEntry);
        }
      }
    });

    observer.observe({ entryTypes: ['resource'] });

    // Stop observing after 10 seconds
    setTimeout(() => {
      observer.disconnect();
    }, 10000);
  }

  // Process resource timing data
  private processResourceTiming(entry: PerformanceResourceTiming): void {
    const loadTime = entry.responseEnd - entry.startTime;
    const transferSize = entry.transferSize || 0;
    const encodedSize = entry.encodedBodySize || 0;
    
    // Categorize by resource type
    if (entry.name.includes('.css')) {
      this.assetMetrics.cssLoadTime = Math.max(this.assetMetrics.cssLoadTime, loadTime);
    } else if (entry.name.includes('.js')) {
      this.assetMetrics.jsLoadTime = Math.max(this.assetMetrics.jsLoadTime, loadTime);
    } else if (entry.name.includes('.woff') || entry.name.includes('.ttf')) {
      this.assetMetrics.fontLoadTime = Math.max(this.assetMetrics.fontLoadTime, loadTime);
    } else if (entry.name.match(/\.(png|jpg|jpeg|svg|gif|webp)$/)) {
      this.assetMetrics.imageLoadTime = Math.max(this.assetMetrics.imageLoadTime, loadTime);
    }

    // Track cache performance
    if (transferSize === 0 && encodedSize > 0) {
      this.assetMetrics.cacheHits++;
    } else {
      this.assetMetrics.cacheMisses++;
    }

    // Track total sizes
    this.assetMetrics.totalAssetSize += encodedSize;
    this.assetMetrics.compressedSize += transferSize;

    console.log(`[LoginPerformance] Asset loaded: ${entry.name.split('/').pop()} - ${loadTime.toFixed(2)}ms`);
  }

  // Track Web Vitals for login page
  private trackWebVitals(): void {
    // Track Largest Contentful Paint (LCP)
    const lcpObserver = new PerformanceObserver((list) => {
      const entries = list.getEntries();
      const lastEntry = entries[entries.length - 1];
      const lcp = lastEntry.startTime;
      
      console.log(`[LoginPerformance] LCP: ${lcp.toFixed(2)}ms`);
      
      performanceMonitoringService.recordLoadingMetrics({
        component: 'login-lcp',
        loadTime: lcp,
        timestamp: new Date(),
        isInitialLoad: true,
      });
    });

    lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });

    // Track First Input Delay (FID)
    const fidObserver = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        const fid = entry.processingStart - entry.startTime;
        console.log(`[LoginPerformance] FID: ${fid.toFixed(2)}ms`);
        
        performanceMonitoringService.recordLoadingMetrics({
          component: 'login-fid',
          loadTime: fid,
          timestamp: new Date(),
          isInitialLoad: true,
        });
      }
    });

    fidObserver.observe({ entryTypes: ['first-input'] });

    // Track Cumulative Layout Shift (CLS)
    let clsValue = 0;
    const clsObserver = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        if (!(entry as any).hadRecentInput) {
          clsValue += (entry as any).value;
        }
      }
      
      console.log(`[LoginPerformance] CLS: ${clsValue.toFixed(4)}`);
    });

    clsObserver.observe({ entryTypes: ['layout-shift'] });

    // Clean up observers after 30 seconds
    setTimeout(() => {
      lcpObserver.disconnect();
      fidObserver.disconnect();
      clsObserver.disconnect();
    }, 30000);
  }

  // Start authentication timing
  startAuthentication(): void {
    this.authStartTime = performance.now();
    this.loginStartTime = this.loginStartTime || performance.now();
  }

  // End authentication timing
  endAuthentication(): number {
    if (this.authStartTime === 0) return 0;
    
    const authTime = performance.now() - this.authStartTime;
    console.log(`[LoginPerformance] Authentication completed: ${authTime.toFixed(2)}ms`);
    
    performanceMonitoringService.recordLoadingMetrics({
      component: 'login-auth',
      loadTime: authTime,
      timestamp: new Date(),
      isInitialLoad: false,
    });

    return authTime;
  }

  // Complete login tracking
  completeLoginTracking(redirectTime: number = 0): LoginPerformanceMetrics {
    const totalTime = performance.now() - (this.loginStartTime || this.pageLoadStartTime);
    const pageLoadTime = performance.now() - this.pageLoadStartTime;
    const authTime = this.authStartTime > 0 ? performance.now() - this.authStartTime : 0;
    
    const metrics: LoginPerformanceMetrics = {
      pageLoadTime,
      assetLoadTime: Math.max(
        this.assetMetrics.cssLoadTime,
        this.assetMetrics.jsLoadTime,
        this.assetMetrics.fontLoadTime
      ),
      authenticationTime: authTime,
      redirectTime,
      totalLoginTime: totalTime,
      timestamp: new Date(),
      userAgent: navigator.userAgent,
      connectionType: this.getConnectionType(),
    };

    this.metrics.push(metrics);
    this.trimMetrics();

    console.log(`[LoginPerformance] Login completed:`, {
      pageLoad: `${pageLoadTime.toFixed(2)}ms`,
      authentication: `${authTime.toFixed(2)}ms`,
      total: `${totalTime.toFixed(2)}ms`,
      grade: this.getPerformanceGrade(totalTime),
    });

    // Record overall performance
    performanceMonitoringService.recordLoadingMetrics({
      component: 'login-complete',
      loadTime: totalTime,
      timestamp: new Date(),
      isInitialLoad: true,
    });

    return metrics;
  }

  // Get connection type
  private getConnectionType(): string {
    const connection = (navigator as any).connection || (navigator as any).mozConnection || (navigator as any).webkitConnection;
    return connection ? connection.effectiveType || connection.type || 'unknown' : 'unknown';
  }

  // Get performance grade
  private getPerformanceGrade(totalTime: number): 'A' | 'B' | 'C' | 'D' | 'F' {
    if (totalTime <= this.performanceThresholds.excellent) return 'A';
    if (totalTime <= this.performanceThresholds.good) return 'B';
    if (totalTime <= this.performanceThresholds.acceptable) return 'C';
    if (totalTime <= this.performanceThresholds.poor) return 'D';
    return 'F';
  }

  // Generate optimization report
  generateOptimizationReport(): LoginOptimizationReport {
    if (this.metrics.length === 0) {
      return {
        averageLoadTime: 0,
        fastestLogin: null,
        slowestLogin: null,
        assetPerformance: { ...this.assetMetrics },
        optimizationSuggestions: ['No login data available yet'],
        performanceGrade: 'F',
      };
    }

    const totalTimes = this.metrics.map(m => m.totalLoginTime);
    const averageLoadTime = totalTimes.reduce((sum, time) => sum + time, 0) / totalTimes.length;
    
    const fastestLogin = this.metrics.reduce((fastest, current) =>
      current.totalLoginTime < fastest.totalLoginTime ? current : fastest
    );
    
    const slowestLogin = this.metrics.reduce((slowest, current) =>
      current.totalLoginTime > slowest.totalLoginTime ? current : slowest
    );

    const suggestions = this.generateOptimizationSuggestions(averageLoadTime);
    const grade = this.getPerformanceGrade(averageLoadTime);

    return {
      averageLoadTime,
      fastestLogin,
      slowestLogin,
      assetPerformance: { ...this.assetMetrics },
      optimizationSuggestions: suggestions,
      performanceGrade: grade,
    };
  }

  // Generate optimization suggestions
  private generateOptimizationSuggestions(averageLoadTime: number): string[] {
    const suggestions: string[] = [];

    // Performance-based suggestions
    if (averageLoadTime > this.performanceThresholds.good) {
      suggestions.push('Login page exceeds 2-second target - consider asset optimization');
    }

    if (this.assetMetrics.cssLoadTime > 500) {
      suggestions.push('CSS loading is slow - consider critical CSS inlining');
    }

    if (this.assetMetrics.jsLoadTime > 1000) {
      suggestions.push('JavaScript loading is slow - consider code splitting');
    }

    if (this.assetMetrics.fontLoadTime > 300) {
      suggestions.push('Font loading is slow - consider font preloading');
    }

    // Cache performance suggestions
    const totalRequests = this.assetMetrics.cacheHits + this.assetMetrics.cacheMisses;
    const cacheHitRate = totalRequests > 0 ? this.assetMetrics.cacheHits / totalRequests : 0;
    
    if (cacheHitRate < 0.8 && totalRequests > 5) {
      suggestions.push(`Cache hit rate is ${(cacheHitRate * 100).toFixed(1)}% - improve caching strategy`);
    }

    // Compression suggestions
    if (this.assetMetrics.totalAssetSize > 0 && this.assetMetrics.compressedSize > 0) {
      const compressionRatio = this.assetMetrics.compressedSize / this.assetMetrics.totalAssetSize;
      if (compressionRatio > 0.7) {
        suggestions.push('Low compression ratio detected - enable better compression');
      }
    }

    // Connection-based suggestions
    const slowConnections = this.metrics.filter(m => 
      m.connectionType && ['slow-2g', '2g', '3g'].includes(m.connectionType)
    ).length;
    
    if (slowConnections > this.metrics.length * 0.3) {
      suggestions.push('Many users on slow connections - prioritize critical resources');
    }

    return suggestions.length > 0 ? suggestions : ['Login performance is optimal'];
  }

  // Preload critical resources
  preloadCriticalResources(): void {
    const criticalResources = [
      '/assets/css/app.css',
      '/assets/js/app.js',
      '/api/auth/csrf-token', // Preload CSRF token
    ];

    criticalResources.forEach(resource => {
      const link = document.createElement('link');
      link.rel = 'preload';
      link.href = resource;
      
      if (resource.endsWith('.css')) {
        link.as = 'style';
      } else if (resource.endsWith('.js')) {
        link.as = 'script';
      } else {
        link.as = 'fetch';
        link.setAttribute('crossorigin', 'anonymous');
      }
      
      document.head.appendChild(link);
    });

    console.log('[LoginPerformance] Critical resources preloaded');
  }

  // Optimize images for login page
  optimizeImages(): void {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
      // Add loading="eager" for above-the-fold images
      if (img.getBoundingClientRect().top < window.innerHeight) {
        img.loading = 'eager';
      } else {
        img.loading = 'lazy';
      }

      // Add decoding="async" for better performance
      img.decoding = 'async';
    });

    console.log('[LoginPerformance] Images optimized');
  }

  // Trim metrics to prevent memory leaks
  private trimMetrics(): void {
    const maxMetrics = 100;
    if (this.metrics.length > maxMetrics) {
      this.metrics = this.metrics.slice(-maxMetrics);
    }
  }

  // Get current metrics
  getMetrics(): LoginPerformanceMetrics[] {
    return [...this.metrics];
  }

  // Clear all metrics
  clearMetrics(): void {
    this.metrics = [];
    this.assetMetrics = {
      cssLoadTime: 0,
      jsLoadTime: 0,
      fontLoadTime: 0,
      imageLoadTime: 0,
      totalAssetSize: 0,
      compressedSize: 0,
      cacheHits: 0,
      cacheMisses: 0,
    };
  }

  // Export metrics for analysis
  exportMetrics() {
    return {
      loginMetrics: [...this.metrics],
      assetMetrics: { ...this.assetMetrics },
      thresholds: { ...this.performanceThresholds },
    };
  }
}

// Create singleton instance
export const loginPerformanceService = new LoginPerformanceService();
export default loginPerformanceService;