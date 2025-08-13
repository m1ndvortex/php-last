import { apiService } from "./api";
import type { AxiosRequestConfig, AxiosResponse } from "axios";

// Performance metrics interfaces
export interface PerformanceMetrics {
  requestCount: number;
  averageResponseTime: number;
  cacheHitRate: number;
  errorRate: number;
  retryCount: number;
  deduplicationSaves: number;
  lastUpdated: Date;
}

export interface CacheStrategy {
  ttl: number; // Time to live in milliseconds
  maxSize: number; // Maximum cache size
  strategy: 'lru' | 'fifo' | 'ttl';
  invalidationRules: InvalidationRule[];
  compressionEnabled: boolean;
}

export interface InvalidationRule {
  pattern: string;
  triggers: string[];
}

export interface RetryPolicy {
  maxRetries: number;
  baseDelay: number;
  maxDelay: number;
  backoffMultiplier: number;
  retryableStatuses: number[];
  retryableErrors: string[];
}

export interface RequestDeduplicationConfig {
  enabled: boolean;
  windowMs: number;
  maxConcurrent: number;
}

// Cache item with metadata
interface CacheItem<T> {
  data: T;
  timestamp: number;
  ttl: number;
  accessCount: number;
  lastAccessed: number;
  size: number;
}

// Request tracking for deduplication
interface PendingRequest {
  promise: Promise<any>;
  timestamp: number;
  requestId: string;
}

// Performance monitoring class
class PerformanceMonitor {
  private metrics: PerformanceMetrics = {
    requestCount: 0,
    averageResponseTime: 0,
    cacheHitRate: 0,
    errorRate: 0,
    retryCount: 0,
    deduplicationSaves: 0,
    lastUpdated: new Date()
  };

  private responseTimes: number[] = [];
  private cacheHits = 0;
  private cacheMisses = 0;
  private errors = 0;
  private retries = 0;
  private deduplicationSaves = 0;

  recordRequest(responseTime: number): void {
    this.metrics.requestCount++;
    this.responseTimes.push(responseTime);
    
    // Keep only last 100 response times for rolling average
    if (this.responseTimes.length > 100) {
      this.responseTimes.shift();
    }
    
    this.updateMetrics();
  }

  recordCacheHit(): void {
    this.cacheHits++;
    this.updateMetrics();
  }

  recordCacheMiss(): void {
    this.cacheMisses++;
    this.updateMetrics();
  }

  recordError(): void {
    this.errors++;
    this.updateMetrics();
  }

  recordRetry(): void {
    this.retries++;
    this.updateMetrics();
  }

  recordDeduplicationSave(): void {
    this.deduplicationSaves++;
    this.updateMetrics();
  }

  private updateMetrics(): void {
    this.metrics.averageResponseTime = this.responseTimes.length > 0
      ? this.responseTimes.reduce((sum, time) => sum + time, 0) / this.responseTimes.length
      : 0;

    const totalCacheRequests = this.cacheHits + this.cacheMisses;
    this.metrics.cacheHitRate = totalCacheRequests > 0
      ? (this.cacheHits / totalCacheRequests) * 100
      : 0;

    this.metrics.errorRate = this.metrics.requestCount > 0
      ? (this.errors / this.metrics.requestCount) * 100
      : 0;

    this.metrics.retryCount = this.retries;
    this.metrics.deduplicationSaves = this.deduplicationSaves;
    this.metrics.lastUpdated = new Date();
  }

  getMetrics(): PerformanceMetrics {
    return { ...this.metrics };
  }

  reset(): void {
    this.metrics = {
      requestCount: 0,
      averageResponseTime: 0,
      cacheHitRate: 0,
      errorRate: 0,
      retryCount: 0,
      deduplicationSaves: 0,
      lastUpdated: new Date()
    };
    this.responseTimes = [];
    this.cacheHits = 0;
    this.cacheMisses = 0;
    this.errors = 0;
    this.retries = 0;
    this.deduplicationSaves = 0;
  }
}

// Intelligent cache with TTL management and LRU eviction
class IntelligentCache {
  private cache = new Map<string, CacheItem<any>>();
  private strategy: CacheStrategy;
  private accessOrder: string[] = [];

  constructor(strategy: CacheStrategy) {
    this.strategy = strategy;
  }

  set<T>(key: string, data: T, customTtl?: number): void {
    const ttl = customTtl || this.strategy.ttl;
    const size = this.estimateSize(data);
    
    // Check if we need to evict items
    this.evictIfNeeded();

    const item: CacheItem<T> = {
      data: this.strategy.compressionEnabled ? this.compress(data) : data,
      timestamp: Date.now(),
      ttl,
      accessCount: 0,
      lastAccessed: Date.now(),
      size
    };

    this.cache.set(key, item);
    this.updateAccessOrder(key);
  }

  get<T>(key: string): T | null {
    const item = this.cache.get(key);
    
    if (!item) {
      return null;
    }

    // Check if expired
    if (Date.now() - item.timestamp > item.ttl) {
      this.delete(key);
      return null;
    }

    // Update access metadata
    item.accessCount++;
    item.lastAccessed = Date.now();
    this.updateAccessOrder(key);

    return this.strategy.compressionEnabled ? this.decompress(item.data) : item.data;
  }

  has(key: string): boolean {
    return this.get(key) !== null;
  }

  delete(key: string): void {
    this.cache.delete(key);
    this.accessOrder = this.accessOrder.filter(k => k !== key);
  }

  clear(): void {
    this.cache.clear();
    this.accessOrder = [];
  }

  invalidate(pattern: string): void {
    const regex = new RegExp(pattern);
    const keysToDelete: string[] = [];

    for (const key of this.cache.keys()) {
      if (regex.test(key)) {
        keysToDelete.push(key);
      }
    }

    keysToDelete.forEach(key => this.delete(key));
  }

  getStats() {
    const totalSize = Array.from(this.cache.values())
      .reduce((sum, item) => sum + item.size, 0);

    return {
      size: this.cache.size,
      maxSize: this.strategy.maxSize,
      totalMemoryUsage: totalSize,
      strategy: this.strategy.strategy,
      compressionEnabled: this.strategy.compressionEnabled
    };
  }

  private evictIfNeeded(): void {
    while (this.cache.size >= this.strategy.maxSize) {
      this.evictOne();
    }
  }

  private evictOne(): void {
    let keyToEvict: string | null = null;

    switch (this.strategy.strategy) {
      case 'lru':
        keyToEvict = this.accessOrder[0] || null;
        break;
      case 'fifo':
        keyToEvict = this.cache.keys().next().value || null;
        break;
      case 'ttl':
        keyToEvict = this.findExpiredOrOldest();
        break;
    }

    if (keyToEvict) {
      this.delete(keyToEvict);
    }
  }

  private findExpiredOrOldest(): string | null {
    let oldestKey: string | null = null;
    let oldestTime = Date.now();

    for (const [key, item] of this.cache.entries()) {
      if (Date.now() - item.timestamp > item.ttl) {
        return key; // Return first expired item
      }
      if (item.timestamp < oldestTime) {
        oldestTime = item.timestamp;
        oldestKey = key;
      }
    }

    return oldestKey;
  }

  private updateAccessOrder(key: string): void {
    // Remove key from current position
    this.accessOrder = this.accessOrder.filter(k => k !== key);
    // Add to end (most recently used)
    this.accessOrder.push(key);
  }

  private estimateSize(data: any): number {
    // Simple size estimation
    return JSON.stringify(data).length * 2; // Rough estimate for UTF-16
  }

  private compress(data: any): any {
    // Simple compression - in production, use a proper compression library
    return data;
  }

  private decompress(data: any): any {
    // Simple decompression
    return data;
  }
}

// Request deduplication manager
class RequestDeduplicationManager {
  private pendingRequests = new Map<string, PendingRequest>();
  private config: RequestDeduplicationConfig;

  constructor(config: RequestDeduplicationConfig) {
    this.config = config;
    
    // Clean up expired requests periodically
    setInterval(() => this.cleanup(), 30000); // Every 30 seconds
  }

  async deduplicate<T>(
    key: string,
    requestFn: () => Promise<T>
  ): Promise<T> {
    if (!this.config.enabled) {
      return requestFn();
    }

    // Check if request is already pending
    const pending = this.pendingRequests.get(key);
    if (pending) {
      // Check if request is still within window
      if (Date.now() - pending.timestamp < this.config.windowMs) {
        return pending.promise;
      } else {
        // Remove expired request
        this.pendingRequests.delete(key);
      }
    }

    // Check concurrent request limit
    if (this.pendingRequests.size >= this.config.maxConcurrent) {
      // Execute immediately without deduplication
      return requestFn();
    }

    // Create new pending request
    const requestId = this.generateRequestId();
    const promise = requestFn().finally(() => {
      this.pendingRequests.delete(key);
    });

    this.pendingRequests.set(key, {
      promise,
      timestamp: Date.now(),
      requestId
    });

    return promise;
  }

  private cleanup(): void {
    const now = Date.now();
    const expiredKeys: string[] = [];

    for (const [key, request] of this.pendingRequests.entries()) {
      if (now - request.timestamp > this.config.windowMs) {
        expiredKeys.push(key);
      }
    }

    expiredKeys.forEach(key => this.pendingRequests.delete(key));
  }

  private generateRequestId(): string {
    return `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  }

  getStats() {
    return {
      pendingRequests: this.pendingRequests.size,
      maxConcurrent: this.config.maxConcurrent,
      windowMs: this.config.windowMs,
      enabled: this.config.enabled
    };
  }
}

// Main Performance-Optimized API Service
export class PerformanceOptimizedApiService {
  private cache: IntelligentCache;
  private monitor: PerformanceMonitor;
  private deduplicationManager: RequestDeduplicationManager;
  private retryPolicy: RetryPolicy;

  constructor() {
    // Initialize with default configurations
    this.cache = new IntelligentCache({
      ttl: 5 * 60 * 1000, // 5 minutes
      maxSize: 200,
      strategy: 'lru',
      invalidationRules: [
        { pattern: 'api/inventory.*', triggers: ['inventory_update', 'inventory_create', 'inventory_delete'] },
        { pattern: 'api/customers.*', triggers: ['customer_update', 'customer_create', 'customer_delete'] },
        { pattern: 'api/invoices.*', triggers: ['invoice_update', 'invoice_create', 'invoice_delete'] }
      ],
      compressionEnabled: false
    });

    this.monitor = new PerformanceMonitor();

    this.deduplicationManager = new RequestDeduplicationManager({
      enabled: true,
      windowMs: 5000, // 5 seconds
      maxConcurrent: 10
    });

    this.retryPolicy = {
      maxRetries: 3,
      baseDelay: 1000,
      maxDelay: 10000,
      backoffMultiplier: 2,
      retryableStatuses: [429, 500, 502, 503, 504],
      retryableErrors: ['NETWORK_ERROR', 'TIMEOUT', 'SERVER_ERROR']
    };
  }

  // Configure cache strategy
  setCacheStrategy(endpoint: string, strategy: Partial<CacheStrategy>): void {
    // For simplicity, we'll update the global strategy
    // In a production system, you'd want per-endpoint strategies
    this.cache = new IntelligentCache({
      ...this.cache['strategy'],
      ...strategy
    } as CacheStrategy);
  }

  // Configure retry policy
  setRetryPolicy(policy: Partial<RetryPolicy>): void {
    this.retryPolicy = { ...this.retryPolicy, ...policy };
  }

  // Configure request deduplication
  setDeduplicationConfig(config: Partial<RequestDeduplicationConfig>): void {
    this.deduplicationManager = new RequestDeduplicationManager({
      ...this.deduplicationManager['config'],
      ...config
    });
  }

  // Enhanced GET with caching, deduplication, and retry
  async get<T>(
    url: string,
    config?: AxiosRequestConfig & { 
      cache?: { ttl?: number; key?: string };
      skipCache?: boolean;
      skipDeduplication?: boolean;
    }
  ): Promise<T> {
    const startTime = Date.now();
    const cacheKey = config?.cache?.key || this.generateCacheKey(url, config?.params);

    try {
      // Check cache first (unless skipped)
      if (!config?.skipCache) {
        const cachedData = this.cache.get<T>(cacheKey);
        if (cachedData !== null) {
          this.monitor.recordCacheHit();
          this.monitor.recordRequest(Date.now() - startTime);
          return cachedData;
        }
        this.monitor.recordCacheMiss();
      }

      // Use deduplication (unless skipped)
      const requestFn = () => this.executeWithRetry<T>(() => apiService.get<T>(url, config));
      
      const response = config?.skipDeduplication
        ? await requestFn()
        : await this.deduplicationManager.deduplicate(cacheKey, requestFn);

      // Cache the result
      if (!config?.skipCache && response) {
        this.cache.set(cacheKey, response, config?.cache?.ttl);
      }

      this.monitor.recordRequest(Date.now() - startTime);
      return response;

    } catch (error) {
      this.monitor.recordError();
      this.monitor.recordRequest(Date.now() - startTime);
      throw error;
    }
  }

  // Enhanced POST with retry
  async post<T>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<T> {
    const startTime = Date.now();

    try {
      const response = await this.executeWithRetry<T>(() => apiService.post<T>(url, data, config));
      
      // Invalidate related cache entries
      this.invalidateRelatedCache(url);
      
      this.monitor.recordRequest(Date.now() - startTime);
      return response;

    } catch (error) {
      this.monitor.recordError();
      this.monitor.recordRequest(Date.now() - startTime);
      throw error;
    }
  }

  // Enhanced PUT with retry
  async put<T>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<T> {
    const startTime = Date.now();

    try {
      const response = await this.executeWithRetry<T>(() => apiService.put<T>(url, data, config));
      
      // Invalidate related cache entries
      this.invalidateRelatedCache(url);
      
      this.monitor.recordRequest(Date.now() - startTime);
      return response;

    } catch (error) {
      this.monitor.recordError();
      this.monitor.recordRequest(Date.now() - startTime);
      throw error;
    }
  }

  // Enhanced DELETE with retry
  async delete<T>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<T> {
    const startTime = Date.now();

    try {
      const response = await this.executeWithRetry<T>(() => apiService.delete<T>(url, config));
      
      // Invalidate related cache entries
      this.invalidateRelatedCache(url);
      
      this.monitor.recordRequest(Date.now() - startTime);
      return response;

    } catch (error) {
      this.monitor.recordError();
      this.monitor.recordRequest(Date.now() - startTime);
      throw error;
    }
  }

  // Execute request with retry logic
  private async executeWithRetry<T>(
    requestFn: () => Promise<AxiosResponse<T>>
  ): Promise<T> {
    let lastError: any;
    let delay = this.retryPolicy.baseDelay;

    for (let attempt = 0; attempt <= this.retryPolicy.maxRetries; attempt++) {
      try {
        const response = await requestFn();
        return response.data;
      } catch (error: any) {
        lastError = error;

        // Don't retry on last attempt
        if (attempt === this.retryPolicy.maxRetries) {
          break;
        }

        // Check if error is retryable
        if (!this.isRetryableError(error)) {
          break;
        }

        this.monitor.recordRetry();

        // Wait before retry with exponential backoff
        await this.sleep(Math.min(delay, this.retryPolicy.maxDelay));
        delay *= this.retryPolicy.backoffMultiplier;
      }
    }

    throw lastError;
  }

  // Check if error should be retried
  private isRetryableError(error: any): boolean {
    // Check HTTP status codes
    if (error.response?.status) {
      return this.retryPolicy.retryableStatuses.includes(error.response.status);
    }

    // Check error codes
    if (error.error?.code) {
      return this.retryPolicy.retryableErrors.includes(error.error.code);
    }

    // Check for network errors
    return !error.response && error.code !== 'ECONNABORTED';
  }

  // Generate cache key
  private generateCacheKey(url: string, params?: any): string {
    const paramString = params ? JSON.stringify(params) : '';
    return `${url}${paramString}`;
  }

  // Invalidate related cache entries
  private invalidateRelatedCache(url: string): void {
    for (const rule of this.cache['strategy'].invalidationRules) {
      if (new RegExp(rule.pattern).test(url)) {
        this.cache.invalidate(rule.pattern);
        break;
      }
    }
  }

  // Utility sleep function
  private sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  // Get performance metrics
  getPerformanceMetrics(): PerformanceMetrics {
    return this.monitor.getMetrics();
  }

  // Get cache statistics
  getCacheStats() {
    return this.cache.getStats();
  }

  // Get deduplication statistics
  getDeduplicationStats() {
    return this.deduplicationManager.getStats();
  }

  // Clear cache
  clearCache(): void {
    this.cache.clear();
  }

  // Invalidate cache by pattern
  invalidateCache(pattern: string): void {
    this.cache.invalidate(pattern);
  }

  // Reset performance metrics
  resetMetrics(): void {
    this.monitor.reset();
  }

  // Preload commonly used data
  async preloadData(endpoints: string[]): Promise<void> {
    const preloadPromises = endpoints.map(endpoint => 
      this.get(endpoint).catch(error => {
        console.warn(`Failed to preload ${endpoint}:`, error);
        return null;
      })
    );

    await Promise.allSettled(preloadPromises);
  }
}

// Create and export the singleton instance
export const performanceApiService = new PerformanceOptimizedApiService();
export default performanceApiService;