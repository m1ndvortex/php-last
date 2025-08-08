interface CacheItem<T> {
  data: T
  timestamp: number
  ttl: number
}

interface CacheOptions {
  ttl?: number // Time to live in milliseconds
  maxSize?: number // Maximum number of items in cache
  storage?: 'memory' | 'localStorage' | 'sessionStorage'
}

class CacheService {
  private memoryCache = new Map<string, CacheItem<any>>()
  private defaultTTL = 5 * 60 * 1000 // 5 minutes
  private maxSize = 100

  constructor(private options: CacheOptions = {}) {
    this.defaultTTL = options.ttl || this.defaultTTL
    this.maxSize = options.maxSize || this.maxSize
  }

  // Set cache item
  set<T>(key: string, data: T, ttl?: number): void {
    const cacheItem: CacheItem<T> = {
      data,
      timestamp: Date.now(),
      ttl: ttl || this.defaultTTL
    }

    switch (this.options.storage) {
      case 'localStorage':
        this.setLocalStorage(key, cacheItem)
        break
      case 'sessionStorage':
        this.setSessionStorage(key, cacheItem)
        break
      default:
        this.setMemoryCache(key, cacheItem)
    }
  }

  // Get cache item
  get<T>(key: string): T | null {
    let cacheItem: CacheItem<T> | null = null

    switch (this.options.storage) {
      case 'localStorage':
        cacheItem = this.getLocalStorage<T>(key)
        break
      case 'sessionStorage':
        cacheItem = this.getSessionStorage<T>(key)
        break
      default:
        cacheItem = this.getMemoryCache<T>(key)
    }

    if (!cacheItem) {
      return null
    }

    // Check if item has expired
    if (Date.now() - cacheItem.timestamp > cacheItem.ttl) {
      this.delete(key)
      return null
    }

    return cacheItem.data
  }

  // Check if key exists and is valid
  has(key: string): boolean {
    return this.get(key) !== null
  }

  // Delete cache item
  delete(key: string): void {
    switch (this.options.storage) {
      case 'localStorage':
        localStorage.removeItem(`cache_${key}`)
        break
      case 'sessionStorage':
        sessionStorage.removeItem(`cache_${key}`)
        break
      default:
        this.memoryCache.delete(key)
    }
  }

  // Clear all cache
  clear(): void {
    switch (this.options.storage) {
      case 'localStorage':
        this.clearLocalStorage()
        break
      case 'sessionStorage':
        this.clearSessionStorage()
        break
      default:
        this.memoryCache.clear()
    }
  }

  // Get or set with factory function
  async getOrSet<T>(
    key: string,
    factory: () => Promise<T>,
    ttl?: number
  ): Promise<T> {
    const cached = this.get<T>(key)
    if (cached !== null) {
      return cached
    }

    const data = await factory()
    this.set(key, data, ttl)
    return data
  }

  // Memory cache methods
  private setMemoryCache<T>(key: string, cacheItem: CacheItem<T>): void {
    // Implement LRU eviction if cache is full
    if (this.memoryCache.size >= this.maxSize) {
      const firstKey = this.memoryCache.keys().next().value
      this.memoryCache.delete(firstKey)
    }

    this.memoryCache.set(key, cacheItem)
  }

  private getMemoryCache<T>(key: string): CacheItem<T> | null {
    return this.memoryCache.get(key) || null
  }

  // LocalStorage methods
  private setLocalStorage<T>(key: string, cacheItem: CacheItem<T>): void {
    try {
      localStorage.setItem(`cache_${key}`, JSON.stringify(cacheItem))
    } catch (error) {
      console.warn('Failed to set localStorage cache:', error)
    }
  }

  private getLocalStorage<T>(key: string): CacheItem<T> | null {
    try {
      const item = localStorage.getItem(`cache_${key}`)
      return item ? JSON.parse(item) : null
    } catch (error) {
      console.warn('Failed to get localStorage cache:', error)
      return null
    }
  }

  private clearLocalStorage(): void {
    const keys = Object.keys(localStorage)
    keys.forEach(key => {
      if (key.startsWith('cache_')) {
        localStorage.removeItem(key)
      }
    })
  }

  // SessionStorage methods
  private setSessionStorage<T>(key: string, cacheItem: CacheItem<T>): void {
    try {
      sessionStorage.setItem(`cache_${key}`, JSON.stringify(cacheItem))
    } catch (error) {
      console.warn('Failed to set sessionStorage cache:', error)
    }
  }

  private getSessionStorage<T>(key: string): CacheItem<T> | null {
    try {
      const item = sessionStorage.getItem(`cache_${key}`)
      return item ? JSON.parse(item) : null
    } catch (error) {
      console.warn('Failed to get sessionStorage cache:', error)
      return null
    }
  }

  private clearSessionStorage(): void {
    const keys = Object.keys(sessionStorage)
    keys.forEach(key => {
      if (key.startsWith('cache_')) {
        sessionStorage.removeItem(key)
      }
    })
  }

  // Get cache statistics
  getStats() {
    const size = this.options.storage === 'memory' 
      ? this.memoryCache.size 
      : this.getStorageSize()

    return {
      size,
      maxSize: this.maxSize,
      storage: this.options.storage || 'memory',
      defaultTTL: this.defaultTTL
    }
  }

  private getStorageSize(): number {
    const storage = this.options.storage === 'localStorage' 
      ? localStorage 
      : sessionStorage

    let count = 0
    for (let i = 0; i < storage.length; i++) {
      const key = storage.key(i)
      if (key && key.startsWith('cache_')) {
        count++
      }
    }
    return count
  }
}

// Create different cache instances for different use cases
export const memoryCache = new CacheService({ 
  storage: 'memory', 
  ttl: 5 * 60 * 1000, // 5 minutes
  maxSize: 100 
})

export const persistentCache = new CacheService({ 
  storage: 'localStorage', 
  ttl: 30 * 60 * 1000, // 30 minutes
  maxSize: 50 
})

export const sessionCache = new CacheService({ 
  storage: 'sessionStorage', 
  ttl: 15 * 60 * 1000, // 15 minutes
  maxSize: 75 
})

// API response cache
export const apiCache = new CacheService({
  storage: 'memory',
  ttl: 2 * 60 * 1000, // 2 minutes for API responses
  maxSize: 200
})

// Static data cache (categories, locations, etc.)
export const staticDataCache = new CacheService({
  storage: 'localStorage',
  ttl: 60 * 60 * 1000, // 1 hour for static data
  maxSize: 25
})

export default CacheService