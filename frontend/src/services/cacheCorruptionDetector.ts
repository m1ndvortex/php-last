// Cache corruption detector - standalone service

export interface CacheEntry {
  key: string
  value: any
  timestamp: number
  ttl: number
  checksum: string
  version: string
}

export interface CorruptionReport {
  id: string
  type: 'checksum_mismatch' | 'invalid_format' | 'expired_data' | 'missing_metadata' | 'storage_error'
  key: string
  description: string
  timestamp: Date
  recovered: boolean
  recoveryMethod?: string
}

export interface CacheHealth {
  totalEntries: number
  corruptedEntries: number
  healthPercentage: number
  lastScanTime: Date
  storageUsage: {
    used: number
    available: number
    percentage: number
  }
}

class CacheCorruptionDetector {
  private readonly CACHE_VERSION = '1.0.0'
  private readonly SCAN_INTERVAL = 300000 // 5 minutes
  private readonly MAX_CORRUPTION_REPORTS = 100

  private corruptionReports = new Map<string, CorruptionReport>()
  private cacheHealth: CacheHealth = {
    totalEntries: 0,
    corruptedEntries: 0,
    healthPercentage: 100,
    lastScanTime: new Date(),
    storageUsage: {
      used: 0,
      available: 0,
      percentage: 0
    }
  }

  private scanTimer: number | null = null
  private isScanning = false

  constructor() {
    this.initializeHealthMonitoring()
  }

  private initializeHealthMonitoring(): void {
    // Start periodic health scans
    this.startPeriodicScanning()

    // Listen for storage events
    window.addEventListener('storage', (event) => {
      if (event.key && event.key.startsWith('cache_')) {
        this.validateCacheEntry(event.key)
      }
    })

    // Monitor storage quota
    this.monitorStorageQuota()
  }

  private startPeriodicScanning(): void {
    this.scanTimer = window.setInterval(() => {
      this.performHealthScan()
    }, this.SCAN_INTERVAL)

    // Initial scan
    setTimeout(() => this.performHealthScan(), 1000)
  }

  private async monitorStorageQuota(): Promise<void> {
    if ('storage' in navigator && 'estimate' in navigator.storage) {
      try {
        const estimate = await navigator.storage.estimate()
        this.cacheHealth.storageUsage = {
          used: estimate.usage || 0,
          available: estimate.quota || 0,
          percentage: estimate.quota ? ((estimate.usage || 0) / estimate.quota) * 100 : 0
        }
      } catch (error) {
        console.warn('Failed to get storage estimate:', error)
      }
    }
  }

  public async performHealthScan(): Promise<CacheHealth> {
    if (this.isScanning) {
      return this.cacheHealth
    }

    this.isScanning = true

    try {
      const cacheKeys = this.getAllCacheKeys()
      let totalEntries = 0
      let corruptedEntries = 0

      for (const key of cacheKeys) {
        totalEntries++
        const isCorrupted = await this.validateCacheEntry(key)
        if (isCorrupted) {
          corruptedEntries++
        }
      }

      this.cacheHealth.totalEntries = totalEntries
      this.cacheHealth.corruptedEntries = corruptedEntries
      this.cacheHealth.healthPercentage = totalEntries > 0 
        ? ((totalEntries - corruptedEntries) / totalEntries) * 100 
        : 100
      this.cacheHealth.lastScanTime = new Date()

      await this.monitorStorageQuota()

      return this.cacheHealth
    } finally {
      this.isScanning = false
    }
  }

  private getAllCacheKeys(): string[] {
    const keys: string[] = []
    
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i)
      if (key && (key.startsWith('cache_') || key.startsWith('session_') || key.startsWith('auth_'))) {
        keys.push(key)
      }
    }

    return keys
  }

  public async validateCacheEntry(key: string): Promise<boolean> {
    try {
      const rawData = localStorage.getItem(key)
      
      if (!rawData) {
        return false // Not corrupted, just missing
      }

      // Try to parse the data
      let parsedData: any
      try {
        parsedData = JSON.parse(rawData)
      } catch (parseError) {
        await this.reportCorruption(key, 'invalid_format', 'Failed to parse JSON data')
        return true
      }

      // Validate structure for cache entries
      if (this.isCacheEntry(parsedData)) {
        return await this.validateCacheEntryStructure(key, parsedData)
      }

      // For non-cache entries, just validate they're valid JSON
      return false

    } catch (error) {
      await this.reportCorruption(key, 'storage_error', `Storage access error: ${error instanceof Error ? error.message : 'Unknown error'}`)
      return true
    }
  }

  private isCacheEntry(data: any): data is CacheEntry {
    return data && 
           typeof data === 'object' && 
           'key' in data && 
           'value' in data && 
           'timestamp' in data
  }

  private async validateCacheEntryStructure(key: string, entry: CacheEntry): Promise<boolean> {
    // Check required fields
    if (!entry.key || !entry.timestamp) {
      await this.reportCorruption(key, 'missing_metadata', 'Missing required cache metadata')
      return true
    }

    // Check if expired
    if (entry.ttl && Date.now() - entry.timestamp > entry.ttl) {
      await this.reportCorruption(key, 'expired_data', 'Cache entry has expired')
      return true
    }

    // Validate checksum if present
    if (entry.checksum) {
      const calculatedChecksum = await this.calculateChecksum(entry.value)
      if (calculatedChecksum !== entry.checksum) {
        await this.reportCorruption(key, 'checksum_mismatch', 'Data integrity check failed')
        return true
      }
    }

    // Check version compatibility
    if (entry.version && entry.version !== this.CACHE_VERSION) {
      await this.reportCorruption(key, 'invalid_format', `Incompatible cache version: ${entry.version}`)
      return true
    }

    return false
  }

  private async calculateChecksum(data: any): Promise<string> {
    const jsonString = JSON.stringify(data)
    const encoder = new TextEncoder()
    const dataBuffer = encoder.encode(jsonString)
    
    if ('crypto' in window && 'subtle' in window.crypto) {
      try {
        const hashBuffer = await window.crypto.subtle.digest('SHA-256', dataBuffer)
        const hashArray = Array.from(new Uint8Array(hashBuffer))
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('')
      } catch (error) {
        // Fallback to simple hash
        return this.simpleHash(jsonString)
      }
    }

    return this.simpleHash(jsonString)
  }

  private simpleHash(str: string): string {
    let hash = 0
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i)
      hash = ((hash << 5) - hash) + char
      hash = hash & hash // Convert to 32-bit integer
    }
    return hash.toString(16)
  }

  private async reportCorruption(
    key: string, 
    type: CorruptionReport['type'], 
    description: string
  ): Promise<void> {
    const reportId = `${key}_${Date.now()}`
    
    const report: CorruptionReport = {
      id: reportId,
      type,
      key,
      description,
      timestamp: new Date(),
      recovered: false
    }

    this.corruptionReports.set(reportId, report)

    // Limit the number of reports
    if (this.corruptionReports.size > this.MAX_CORRUPTION_REPORTS) {
      const oldestKey = Array.from(this.corruptionReports.keys())[0]
      this.corruptionReports.delete(oldestKey)
    }

    // Attempt automatic recovery
    await this.attemptRecovery(report)
  }

  private async attemptRecovery(report: CorruptionReport): Promise<void> {
    try {
      switch (report.type) {
        case 'expired_data':
          await this.recoverExpiredData(report)
          break
        case 'checksum_mismatch':
          await this.recoverCorruptedData(report)
          break
        case 'invalid_format':
          await this.recoverInvalidFormat(report)
          break
        case 'missing_metadata':
          await this.recoverMissingMetadata(report)
          break
        case 'storage_error':
          await this.recoverStorageError(report)
          break
      }
    } catch (error) {
      console.warn(`Failed to recover corrupted cache entry ${report.key}:`, error)
    }
  }

  private async recoverExpiredData(report: CorruptionReport): Promise<void> {
    // Simply remove expired data
    localStorage.removeItem(report.key)
    report.recovered = true
    report.recoveryMethod = 'removed_expired'
  }

  private async recoverCorruptedData(report: CorruptionReport): Promise<void> {
    // Try to recover from backup or remove if no backup exists
    const backupKey = `${report.key}_backup`
    const backup = localStorage.getItem(backupKey)
    
    if (backup) {
      try {
        JSON.parse(backup) // Just validate it's parseable
        if (await this.validateCacheEntry(backupKey) === false) {
          localStorage.setItem(report.key, backup)
          report.recovered = true
          report.recoveryMethod = 'restored_from_backup'
          return
        }
      } catch (error) {
        // Backup is also corrupted
      }
    }

    // No valid backup, remove corrupted data
    localStorage.removeItem(report.key)
    report.recovered = true
    report.recoveryMethod = 'removed_corrupted'
  }

  private async recoverInvalidFormat(report: CorruptionReport): Promise<void> {
    // Try to salvage data or remove if unsalvageable
    const rawData = localStorage.getItem(report.key)
    
    if (rawData) {
      // Try to extract any valid data
      try {
        // Attempt to fix common JSON issues
        const fixedData = rawData
          .replace(/,\s*}/g, '}')  // Remove trailing commas
          .replace(/,\s*]/g, ']')  // Remove trailing commas in arrays
        
        const parsed = JSON.parse(fixedData)
        
        // If it's salvageable, create a proper cache entry
        const newEntry: CacheEntry = {
          key: report.key,
          value: parsed,
          timestamp: Date.now(),
          ttl: 3600000, // 1 hour default
          checksum: await this.calculateChecksum(parsed),
          version: this.CACHE_VERSION
        }
        
        localStorage.setItem(report.key, JSON.stringify(newEntry))
        report.recovered = true
        report.recoveryMethod = 'data_salvaged'
        return
      } catch (error) {
        // Data is unsalvageable
      }
    }

    localStorage.removeItem(report.key)
    report.recovered = true
    report.recoveryMethod = 'removed_invalid'
  }

  private async recoverMissingMetadata(report: CorruptionReport): Promise<void> {
    const rawData = localStorage.getItem(report.key)
    
    if (rawData) {
      try {
        const data = JSON.parse(rawData)
        
        // Add missing metadata
        const enhancedEntry: CacheEntry = {
          key: report.key,
          value: data.value || data,
          timestamp: data.timestamp || Date.now(),
          ttl: data.ttl || 3600000,
          checksum: await this.calculateChecksum(data.value || data),
          version: this.CACHE_VERSION
        }
        
        localStorage.setItem(report.key, JSON.stringify(enhancedEntry))
        report.recovered = true
        report.recoveryMethod = 'metadata_restored'
      } catch (error) {
        localStorage.removeItem(report.key)
        report.recovered = true
        report.recoveryMethod = 'removed_unrecoverable'
      }
    }
  }

  private async recoverStorageError(report: CorruptionReport): Promise<void> {
    // For storage errors, try to clear space and retry
    try {
      // Clear some old cache entries to free up space
      await this.clearOldCacheEntries()
      
      // Try to access the key again
      const data = localStorage.getItem(report.key)
      if (data !== null) {
        report.recovered = true
        report.recoveryMethod = 'storage_cleared'
      }
    } catch (error) {
      // If still failing, remove the problematic entry
      try {
        localStorage.removeItem(report.key)
        report.recovered = true
        report.recoveryMethod = 'removed_storage_error'
      } catch (removeError) {
        // Storage is seriously corrupted
        console.error('Critical storage error:', removeError)
      }
    }
  }

  private async clearOldCacheEntries(): Promise<void> {
    const keys = this.getAllCacheKeys()
    const now = Date.now()
    
    for (const key of keys) {
      try {
        const rawData = localStorage.getItem(key)
        if (rawData) {
          const data = JSON.parse(rawData)
          
          // Remove entries older than 24 hours
          if (data.timestamp && (now - data.timestamp) > 86400000) {
            localStorage.removeItem(key)
          }
        }
      } catch (error) {
        // Remove unparseable entries
        localStorage.removeItem(key)
      }
    }
  }

  public async createBackup(key: string): Promise<void> {
    const data = localStorage.getItem(key)
    if (data) {
      const backupKey = `${key}_backup`
      localStorage.setItem(backupKey, data)
    }
  }

  public getCorruptionReports(): CorruptionReport[] {
    return Array.from(this.corruptionReports.values())
  }

  public getCacheHealth(): CacheHealth {
    return { ...this.cacheHealth }
  }

  public clearCorruptionReports(): void {
    this.corruptionReports.clear()
  }

  public async forceCacheCleanup(): Promise<void> {
    const keys = this.getAllCacheKeys()
    
    for (const key of keys) {
      const isCorrupted = await this.validateCacheEntry(key)
      if (isCorrupted) {
        localStorage.removeItem(key)
      }
    }
    
    await this.performHealthScan()
  }

  public destroy(): void {
    if (this.scanTimer) {
      clearInterval(this.scanTimer)
      this.scanTimer = null
    }
  }
}

export const cacheCorruptionDetector = new CacheCorruptionDetector()
export default cacheCorruptionDetector