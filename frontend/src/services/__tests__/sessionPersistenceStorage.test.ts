import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { sessionPersistenceStorage, SessionPersistenceStorage } from '../sessionPersistenceStorage'

// Mock crypto for testing
Object.defineProperty(global, 'crypto', {
  value: {
    getRandomValues: (arr: Uint8Array) => {
      for (let i = 0; i < arr.length; i++) {
        arr[i] = Math.floor(Math.random() * 256)
      }
      return arr
    }
  }
})

// Mock localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    },
    get length() {
      return Object.keys(store).length
    },
    key: (index: number) => {
      const keys = Object.keys(store)
      return keys[index] || null
    }
  }
})()

// Mock sessionStorage
const sessionStorageMock = (() => {
  let store: Record<string, string> = {}
  
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    }
  }
})()

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

Object.defineProperty(window, 'sessionStorage', {
  value: sessionStorageMock
})

describe('SessionPersistenceStorage', () => {
  let storage: SessionPersistenceStorage

  beforeEach(() => {
    localStorageMock.clear()
    sessionStorageMock.clear()
    storage = new SessionPersistenceStorage({
      cleanupInterval: 1000,
      maxStorageSize: 1024
    })
  })

  afterEach(() => {
    if (storage && typeof storage.destroy === 'function') {
      storage.destroy()
    }
  })

  describe('Basic Storage Operations', () => {
    it('should store and retrieve simple data', () => {
      const testData = { name: 'test', value: 123 }
      
      storage.setItem('test-key', testData)
      const retrieved = storage.getItem('test-key')
      
      expect(retrieved).toEqual(testData)
    })

    it('should return null for non-existent keys', () => {
      const result = storage.getItem('non-existent')
      expect(result).toBeNull()
    })

    it('should remove items correctly', () => {
      storage.setItem('test-key', 'test-value')
      expect(storage.getItem('test-key')).toBe('test-value')
      
      storage.removeItem('test-key')
      expect(storage.getItem('test-key')).toBeNull()
    })
  })

  describe('Session Metadata', () => {
    it('should store and retrieve session metadata', () => {
      const testData = { user: 'test-user' }
      
      storage.setItem('user-data', testData)
      const metadata = storage.getSessionMetadata('user-data')
      
      expect(metadata).toBeDefined()
      expect(metadata?.version).toBe('1.0')
      expect(metadata?.createdAt).toBeInstanceOf(Date)
      expect(metadata?.tabId).toBeDefined()
    })
  })

  describe('Storage Statistics', () => {
    it('should provide accurate storage statistics', () => {
      storage.setItem('item1', { data: 'test1' })
      storage.setItem('item2', { data: 'test2' })
      
      const stats = storage.getStorageStats()
      
      expect(stats.itemCount).toBe(2)
      expect(stats.totalSize).toBeGreaterThan(0)
    })
  })

  describe('Singleton Instance', () => {
    it('should provide a singleton instance', () => {
      expect(sessionPersistenceStorage).toBeDefined()
      expect(typeof sessionPersistenceStorage.setItem).toBe('function')
      expect(typeof sessionPersistenceStorage.getItem).toBe('function')
    })
  })
})