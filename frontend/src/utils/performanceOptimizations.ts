// Performance optimization utilities

// Debounce function for search inputs and API calls
export function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number,
  immediate = false
): (...args: Parameters<T>) => void {
  let timeout: NodeJS.Timeout | null = null

  return function executedFunction(...args: Parameters<T>) {
    const later = () => {
      timeout = null
      if (!immediate) func(...args)
    }

    const callNow = immediate && !timeout
    
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(later, wait)
    
    if (callNow) func(...args)
  }
}

// Throttle function for scroll events and frequent updates
export function throttle<T extends (...args: any[]) => any>(
  func: T,
  limit: number
): (...args: Parameters<T>) => void {
  let inThrottle: boolean
  
  return function executedFunction(this: any, ...args: Parameters<T>) {
    if (!inThrottle) {
      func.apply(this, args)
      inThrottle = true
      setTimeout(() => inThrottle = false, limit)
    }
  }
}

// Memoization for expensive computations
export function memoize<T extends (...args: any[]) => any>(
  fn: T,
  getKey?: (...args: Parameters<T>) => string
): T {
  const cache = new Map<string, ReturnType<T>>()
  
  return ((...args: Parameters<T>): ReturnType<T> => {
    const key = getKey ? getKey(...args) : JSON.stringify(args)
    
    if (cache.has(key)) {
      return cache.get(key)!
    }
    
    const result = fn(...args)
    cache.set(key, result)
    
    return result
  }) as T
}

// Batch DOM updates to avoid layout thrashing
export function batchDOMUpdates(callback: () => void): void {
  if ('requestIdleCallback' in window) {
    requestIdleCallback(callback, { timeout: 100 })
  } else {
    setTimeout(callback, 0)
  }
}

// Preload images for better UX
export function preloadImage(src: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve()
    img.onerror = reject
    img.src = src
  })
}

// Preload multiple images
export async function preloadImages(srcs: string[]): Promise<void> {
  const promises = srcs.map(src => preloadImage(src).catch(() => {}))
  await Promise.allSettled(promises)
}

// Lazy load script
export function loadScript(src: string): Promise<void> {
  return new Promise((resolve, reject) => {
    // Check if script is already loaded
    if (document.querySelector(`script[src="${src}"]`)) {
      resolve()
      return
    }

    const script = document.createElement('script')
    script.src = src
    script.async = true
    script.onload = () => resolve()
    script.onerror = reject
    
    document.head.appendChild(script)
  })
}

// Optimize array operations for large datasets
export function chunkArray<T>(array: T[], chunkSize: number): T[][] {
  const chunks: T[][] = []
  for (let i = 0; i < array.length; i += chunkSize) {
    chunks.push(array.slice(i, i + chunkSize))
  }
  return chunks
}

// Process large arrays in chunks to avoid blocking the main thread
export async function processArrayInChunks<T, R>(
  array: T[],
  processor: (item: T, index: number) => R,
  chunkSize = 100,
  delay = 0
): Promise<R[]> {
  const results: R[] = []
  const chunks = chunkArray(array, chunkSize)
  
  for (const chunk of chunks) {
    const chunkResults = chunk.map((item, index) => 
      processor(item, results.length + index)
    )
    results.push(...chunkResults)
    
    // Yield control back to the browser
    if (delay > 0) {
      await new Promise(resolve => setTimeout(resolve, delay))
    } else {
      await new Promise(resolve => setTimeout(resolve, 0))
    }
  }
  
  return results
}

// Optimize object property access
export function createPropertyAccessor<T extends Record<string, any>>(
  obj: T
): (path: string) => any {
  const cache = new Map<string, any>()
  
  return (path: string) => {
    if (cache.has(path)) {
      return cache.get(path)
    }
    
    const value = path.split('.').reduce((current, key) => current?.[key], obj)
    cache.set(path, value)
    
    return value
  }
}

// Intersection Observer for lazy loading
export function createIntersectionObserver(
  callback: (entries: IntersectionObserverEntry[]) => void,
  options: IntersectionObserverInit = {}
): IntersectionObserver {
  const defaultOptions: IntersectionObserverInit = {
    threshold: 0.1,
    rootMargin: '50px',
    ...options
  }
  
  return new IntersectionObserver(callback, defaultOptions)
}

// Resize Observer for responsive components
export function createResizeObserver(
  callback: (entries: ResizeObserverEntry[]) => void
): ResizeObserver {
  return new ResizeObserver(callback)
}

// Performance measurement utilities
export class PerformanceTimer {
  private startTime: number = 0
  private marks: Map<string, number> = new Map()
  
  start(): void {
    this.startTime = performance.now()
  }
  
  mark(name: string): void {
    this.marks.set(name, performance.now())
  }
  
  measure(name: string, startMark?: string): number {
    const endTime = performance.now()
    const startTime = startMark ? this.marks.get(startMark) || this.startTime : this.startTime
    const duration = endTime - startTime
    
    if (import.meta.env.DEV) {
      console.log(`⏱️ ${name}: ${duration.toFixed(2)}ms`)
    }
    
    return duration
  }
  
  getMarks(): Map<string, number> {
    return new Map(this.marks)
  }
  
  reset(): void {
    this.startTime = 0
    this.marks.clear()
  }
}

// Memory usage monitoring
export function getMemoryUsage(): {
  used: number
  total: number
  limit: number
} | null {
  if ('memory' in performance) {
    const memory = (performance as any).memory
    return {
      used: memory.usedJSHeapSize,
      total: memory.totalJSHeapSize,
      limit: memory.jsHeapSizeLimit
    }
  }
  return null
}

// Check if user prefers reduced motion
export function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

// Check connection quality
export function getConnectionInfo(): {
  effectiveType: string
  downlink: number
  rtt: number
  saveData: boolean
} | null {
  if ('connection' in navigator) {
    const connection = (navigator as any).connection
    return {
      effectiveType: connection.effectiveType,
      downlink: connection.downlink,
      rtt: connection.rtt,
      saveData: connection.saveData
    }
  }
  return null
}

// Adaptive loading based on connection
export function shouldLoadHighQuality(): boolean {
  const connection = getConnectionInfo()
  if (!connection) return true
  
  // Don't load high quality on slow connections or when save data is enabled
  return !connection.saveData && 
         connection.effectiveType !== 'slow-2g' && 
         connection.effectiveType !== '2g'
}

// Image optimization utilities
export function getOptimizedImageUrl(
  baseUrl: string,
  width: number,
  height?: number,
  quality = 80
): string {
  const params = new URLSearchParams()
  params.set('w', width.toString())
  if (height) params.set('h', height.toString())
  params.set('q', quality.toString())
  
  // Use WebP if supported
  if (supportsWebP()) {
    params.set('f', 'webp')
  }
  
  return `${baseUrl}?${params.toString()}`
}

// Check WebP support
export function supportsWebP(): boolean {
  const canvas = document.createElement('canvas')
  canvas.width = 1
  canvas.height = 1
  return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0
}

// Bundle size optimization - dynamic imports with error handling
export async function importWithRetry<T>(
  importFn: () => Promise<T>,
  retries = 3,
  delay = 1000
): Promise<T> {
  for (let i = 0; i < retries; i++) {
    try {
      return await importFn()
    } catch (error) {
      if (i === retries - 1) throw error
      
      // Wait before retrying
      await new Promise(resolve => setTimeout(resolve, delay * (i + 1)))
    }
  }
  
  throw new Error('Failed to import after retries')
}

// Service Worker utilities
export function registerServiceWorker(swUrl: string): Promise<ServiceWorkerRegistration> {
  return navigator.serviceWorker.register(swUrl)
}

export function updateServiceWorker(registration: ServiceWorkerRegistration): Promise<void> {
  return registration.update()
}

// Critical resource hints
export function addResourceHint(
  href: string,
  rel: 'preload' | 'prefetch' | 'preconnect' | 'dns-prefetch',
  as?: string
): void {
  const link = document.createElement('link')
  link.rel = rel
  link.href = href
  if (as) link.as = as
  
  document.head.appendChild(link)
}

// Preconnect to external domains
export function preconnectToDomains(domains: string[]): void {
  domains.forEach(domain => {
    addResourceHint(domain, 'preconnect')
  })
}