import type { SessionData } from '@/types/auth'

// Encryption utilities for sensitive data
class EncryptionService {
  private key: string

  constructor() {
    this.key = this.getOrCreateKey()
  }

  private getOrCreateKey(): string {
    const stored = localStorage.getItem('_session_key')
    if (stored) {
      return stored
    }
    
    const key = this.generateKey()
    localStorage.setItem('_session_key', key)
    return key
  }

  private generateKey(): string {
    const array = new Uint8Array(32)
    crypto.getRandomValues(array)
    return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('')
  }

  encrypt(data: string): string {
    try {
      const keyBytes = this.key.match(/.{2}/g)?.map(hex => parseInt(hex, 16)) || []
      const dataBytes = new TextEncoder().encode(data)
      const encrypted = new Uint8Array(dataBytes.length)
      
      for (let i = 0; i < dataBytes.length; i++) {
        encrypted[i] = dataBytes[i] ^ keyBytes[i % keyBytes.length]
      }
      
      return btoa(String.fromCharCode(...encrypted))
    } catch (error) {
      console.error('Encryption failed:', error)
      return data
    }
  }

  decrypt(encryptedData: string): string {
    try {
      const keyBytes = this.key.match(/.{2}/g)?.map(hex => parseInt(hex, 16)) || []
      const encrypted = new Uint8Array(atob(encryptedData).split('').map(char => char.charCodeAt(0)))
      const decrypted = new Uint8Array(encrypted.length)
      
      for (let i = 0; i < encrypted.length; i++) {
        decrypted[i] = encrypted[i] ^ keyBytes[i % keyBytes.length]
      }
      
      return new TextDecoder().decode(decrypted)
    } catch (error) {
      console.error('Decryption failed:', error)
      return encryptedData
    }
  }
}

// Session metadata interface
interface SessionMetadata {
  version: string
  createdAt: Date
  lastAccessed: Date
  accessCount: number
  tabId: string
  checksum: string
}

// Cache invalidation strategy
interface CacheInvalidationRule {
  key: string
  ttl: number
  maxAge: number
  dependencies: string[]
}

// Storage configuration
interface StorageConfig {
  encryptSensitiveData: boolean
  enableCompression: boolean
  maxStorageSize: number
  cleanupInterval: number
  backupEnabled: boolean
}

export class SessionPersistenceStorage {
  private encryption: EncryptionService
  private config: StorageConfig
  private invalidationRules: Map<string, CacheInvalidationRule>
  private cleanupTimer: number | null = null

  constructor(config: Partial<StorageConfig> = {}) {
    this.encryption = new EncryptionService()
    this.config = {
      encryptSensitiveData: true,
      enableCompression: false,
      maxStorageSize: 5 * 1024 * 1024,
      cleanupInterval: 5 * 60 * 1000,
      backupEnabled: true,
      ...config
    }
    this.invalidationRules = new Map()
    this.startCleanupTimer()
  }

  setItem(key: string, value: any, options: { encrypt?: boolean; ttl?: number } = {}): void {
    try {
      const metadata: SessionMetadata = {
        version: '1.0',
        createdAt: new Date(),
        lastAccessed: new Date(),
        accessCount: 1,
        tabId: this.getTabId(),
        checksum: this.generateChecksum(value)
      }

      const dataToStore = {
        value,
        metadata,
        encrypted: options.encrypt || this.shouldEncrypt(key),
        ttl: options.ttl
      }

      let serialized = JSON.stringify(dataToStore)
      
      if (dataToStore.encrypted) {
        serialized = this.encryption.encrypt(serialized)
      }

      if (this.getStorageSize() + serialized.length > this.config.maxStorageSize) {
        this.performCleanup()
      }

      localStorage.setItem(this.prefixKey(key), serialized)

      if (options.ttl) {
        this.setInvalidationRule(key, {
          key,
          ttl: options.ttl,
          maxAge: options.ttl,
          dependencies: []
        })
      }

      if (this.config.backupEnabled) {
        this.createBackup(key, dataToStore)
      }
    } catch (error) {
      console.error('Failed to store session data:', error)
      throw new Error(`Storage failed for key: ${key}`)
    }
  }

  getItem<T = any>(key: string): T | null {
    try {
      const stored = localStorage.getItem(this.prefixKey(key))
      if (!stored) {
        return null
      }

      let parsed: any
      try {
        if (this.isEncrypted(stored)) {
          const decrypted = this.encryption.decrypt(stored)
          parsed = JSON.parse(decrypted)
        } else {
          parsed = JSON.parse(stored)
        }
      } catch (parseError) {
        const backup = this.recoverFromBackup(key)
        if (backup) {
          return backup
        }
        throw parseError
      }

      if (this.isExpired(parsed)) {
        this.removeItem(key)
        return null
      }

      if (!this.verifyChecksum(parsed.value, parsed.metadata?.checksum)) {
        console.warn(`Data corruption detected for key: ${key}`)
        const backup = this.recoverFromBackup(key)
        if (backup) {
          return backup
        }
        this.removeItem(key)
        return null
      }

      if (parsed.metadata) {
        parsed.metadata.lastAccessed = new Date()
        parsed.metadata.accessCount = (parsed.metadata.accessCount || 0) + 1
        
        // Update the stored data with new metadata without resetting access count
        const updatedData = {
          ...parsed,
          metadata: parsed.metadata
        }
        
        let serialized = JSON.stringify(updatedData)
        if (parsed.encrypted) {
          serialized = this.encryption.encrypt(serialized)
        }
        
        localStorage.setItem(this.prefixKey(key), serialized)
      }

      return parsed.value
    } catch (error) {
      console.error('Failed to retrieve session data:', error)
      return null
    }
  }

  removeItem(key: string): void {
    try {
      localStorage.removeItem(this.prefixKey(key))
      this.removeBackup(key)
      this.invalidationRules.delete(key)
    } catch (error) {
      console.error('Failed to remove session data:', error)
    }
  }

  getSessionMetadata(key: string): SessionMetadata | null {
    const stored = localStorage.getItem(this.prefixKey(key))
    if (!stored) return null

    try {
      let parsed: any
      if (this.isEncrypted(stored)) {
        const decrypted = this.encryption.decrypt(stored)
        parsed = JSON.parse(decrypted)
      } else {
        parsed = JSON.parse(stored)
      }

      const metadata = parsed.metadata
      if (metadata) {
        // Convert string dates back to Date objects
        metadata.createdAt = new Date(metadata.createdAt)
        metadata.lastAccessed = new Date(metadata.lastAccessed)
      }
      return metadata || null
    } catch (error) {
      console.error('Failed to get session metadata:', error)
      return null
    }
  }

  getAllSessionKeys(): string[] {
    const keys: string[] = []
    const prefix = this.prefixKey('')
    
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i)
      if (key && key.startsWith(prefix)) {
        keys.push(key.substring(prefix.length))
      }
    }
    
    return keys
  }

  setInvalidationRule(key: string, rule: CacheInvalidationRule): void {
    this.invalidationRules.set(key, rule)
  }

  invalidateCache(pattern: string): void {
    const keys = this.getAllSessionKeys()
    const regex = new RegExp(pattern)
    
    keys.forEach(key => {
      if (regex.test(key)) {
        this.removeItem(key)
      }
    })
  }

  invalidateDependencies(changedKey: string): void {
    this.invalidationRules.forEach((rule, key) => {
      if (rule.dependencies.includes(changedKey)) {
        this.removeItem(key)
      }
    })
  }

  performCleanup(): void {
    const keys = this.getAllSessionKeys()
    const now = Date.now()
    
    keys.forEach(key => {
      const metadata = this.getSessionMetadata(key)
      if (metadata) {
        const rule = this.invalidationRules.get(key)
        if (rule) {
          const age = now - new Date(metadata.createdAt).getTime()
          if (age > rule.maxAge) {
            this.removeItem(key)
          }
        }
      }
    })

    if (this.getStorageSize() > this.config.maxStorageSize * 0.8) {
      this.performLRUCleanup()
    }
  }

  private performLRUCleanup(): void {
    const keys = this.getAllSessionKeys()
    const keyMetadata: Array<{ key: string; lastAccessed: Date }> = []
    
    keys.forEach(key => {
      const metadata = this.getSessionMetadata(key)
      if (metadata) {
        keyMetadata.push({
          key,
          lastAccessed: new Date(metadata.lastAccessed)
        })
      }
    })

    keyMetadata.sort((a, b) => a.lastAccessed.getTime() - b.lastAccessed.getTime())
    
    const itemsToRemove = Math.ceil(keyMetadata.length * 0.25)
    for (let i = 0; i < itemsToRemove; i++) {
      this.removeItem(keyMetadata[i].key)
    }
  }

  private createBackup(key: string, data: any): void {
    try {
      const backupKey = this.getBackupKey(key)
      const backup = {
        originalKey: key,
        data,
        timestamp: new Date().toISOString()
      }
      localStorage.setItem(backupKey, JSON.stringify(backup))
    } catch (error) {
      console.error('Failed to create backup:', error)
    }
  }

  private recoverFromBackup(key: string): any {
    try {
      const backupKey = this.getBackupKey(key)
      const backup = localStorage.getItem(backupKey)
      if (backup) {
        const parsed = JSON.parse(backup)
        console.log(`Recovered data from backup for key: ${key}`)
        return parsed.data.value
      }
    } catch (error) {
      console.error('Failed to recover from backup:', error)
    }
    return null
  }

  private removeBackup(key: string): void {
    try {
      const backupKey = this.getBackupKey(key)
      localStorage.removeItem(backupKey)
    } catch (error) {
      console.error('Failed to remove backup:', error)
    }
  }

  private prefixKey(key: string): string {
    return `session_${key}`
  }

  private getBackupKey(key: string): string {
    return `backup_${this.prefixKey(key)}`
  }

  private shouldEncrypt(key: string): boolean {
    const sensitiveKeys = ['token', 'password', 'session', 'auth', 'user']
    return this.config.encryptSensitiveData && 
           sensitiveKeys.some(sensitive => key.toLowerCase().includes(sensitive))
  }

  private isEncrypted(data: string): boolean {
    return !data.startsWith('{') && /^[A-Za-z0-9+/=]+$/.test(data)
  }

  private isExpired(parsed: any): boolean {
    if (!parsed.ttl) return false
    
    const createdAt = new Date(parsed.metadata?.createdAt || 0).getTime()
    const now = Date.now()
    return (now - createdAt) > parsed.ttl
  }

  private generateChecksum(data: any): string {
    const str = JSON.stringify(data)
    let hash = 0
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i)
      hash = ((hash << 5) - hash) + char
      hash = hash & hash
    }
    return hash.toString(16)
  }

  private verifyChecksum(data: any, expectedChecksum?: string): boolean {
    if (!expectedChecksum) return true
    return this.generateChecksum(data) === expectedChecksum
  }

  private getTabId(): string {
    let tabId = sessionStorage.getItem('tabId')
    if (!tabId) {
      tabId = `tab_${Date.now()}_${Math.random().toString(36).substring(2, 11)}`
      sessionStorage.setItem('tabId', tabId)
    }
    return tabId
  }

  private getStorageSize(): number {
    let total = 0
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i)
      if (key) {
        const value = localStorage.getItem(key)
        if (value) {
          total += key.length + value.length
        }
      }
    }
    return total
  }

  private startCleanupTimer(): void {
    if (this.cleanupTimer) {
      clearInterval(this.cleanupTimer)
    }
    
    this.cleanupTimer = window.setInterval(() => {
      this.performCleanup()
    }, this.config.cleanupInterval)
  }

  destroy(): void {
    if (this.cleanupTimer) {
      clearInterval(this.cleanupTimer)
      this.cleanupTimer = null
    }
  }

  getStorageStats(): {
    totalSize: number
    itemCount: number
    encryptedItems: number
    backupItems: number
  } {
    const keys = this.getAllSessionKeys()
    let encryptedCount = 0
    let backupCount = 0
    
    keys.forEach(key => {
      const stored = localStorage.getItem(this.prefixKey(key))
      if (stored && this.isEncrypted(stored)) {
        encryptedCount++
      }
      
      const backupKey = this.getBackupKey(key)
      if (localStorage.getItem(backupKey)) {
        backupCount++
      }
    })

    return {
      totalSize: this.getStorageSize(),
      itemCount: keys.length,
      encryptedItems: encryptedCount,
      backupItems: backupCount
    }
  }
}

export const sessionPersistenceStorage = new SessionPersistenceStorage()