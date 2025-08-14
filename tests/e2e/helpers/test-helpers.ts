import { Page, BrowserContext, expect } from '@playwright/test'

/**
 * Test helper utilities for Seamless Tab Navigation E2E tests
 */

export const TEST_CREDENTIALS = {
  email: 'test@example.com',
  password: 'password'
}

export const APP_ROUTES = {
  login: '/login',
  dashboard: '/dashboard',
  customers: '/customers',
  inventory: '/inventory',
  invoices: '/invoices',
  accounting: '/accounting'
}

/**
 * Authentication helper functions
 */
export class AuthHelpers {
  /**
   * Perform login on a page
   */
  static async login(page: Page, credentials = TEST_CREDENTIALS): Promise<void> {
    await page.goto(APP_ROUTES.login)
    await page.fill('input[type="email"]', credentials.email)
    await page.fill('input[type="password"]', credentials.password)
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
  }

  /**
   * Perform logout on a page
   */
  static async logout(page: Page): Promise<void> {
    await page.click('[data-testid="user-menu"]')
    await page.click('[data-testid="logout-button"]')
    await page.waitForURL('**/login')
  }

  /**
   * Verify user is authenticated
   */
  static async verifyAuthenticated(page: Page): Promise<void> {
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible()
  }

  /**
   * Verify user is not authenticated
   */
  static async verifyNotAuthenticated(page: Page): Promise<void> {
    await expect(page.locator('form[data-testid="login-form"]')).toBeVisible()
  }

  /**
   * Check if user is currently authenticated
   */
  static async isAuthenticated(page: Page): Promise<boolean> {
    try {
      await page.locator('[data-testid="user-menu"]').waitFor({ timeout: 2000 })
      return true
    } catch {
      return false
    }
  }
}

/**
 * Performance measurement helpers
 */
export class PerformanceHelpers {
  /**
   * Measure tab switching time
   */
  static async measureTabSwitchTime(fromPage: Page, toPage: Page): Promise<number> {
    const startTime = Date.now()
    await toPage.bringToFront()
    await toPage.waitForLoadState('domcontentloaded')
    const endTime = Date.now()
    return endTime - startTime
  }

  /**
   * Measure multiple tab switches and return statistics
   */
  static async measureMultipleTabSwitches(
    pages: Page[], 
    iterations: number = 5
  ): Promise<{
    times: number[]
    average: number
    min: number
    max: number
  }> {
    const times: number[] = []
    
    for (let i = 0; i < iterations; i++) {
      for (let j = 0; j < pages.length - 1; j++) {
        const switchTime = await this.measureTabSwitchTime(pages[j], pages[j + 1])
        times.push(switchTime)
        
        // Small delay between measurements
        await pages[j + 1].waitForTimeout(100)
      }
    }
    
    const average = times.reduce((a, b) => a + b, 0) / times.length
    const min = Math.min(...times)
    const max = Math.max(...times)
    
    return { times, average, min, max }
  }

  /**
   * Verify performance meets requirements
   */
  static verifyPerformanceRequirement(
    averageTime: number, 
    maxAllowed: number = 100
  ): void {
    expect(averageTime).toBeLessThan(maxAllowed)
  }
}

/**
 * Multi-tab management helpers
 */
export class MultiTabHelpers {
  /**
   * Create multiple tabs with different routes
   */
  static async createAuthenticatedTabs(
    context: BrowserContext,
    routes: string[] = [APP_ROUTES.dashboard, APP_ROUTES.customers, APP_ROUTES.inventory]
  ): Promise<Page[]> {
    const pages: Page[] = []
    
    // Create first tab and login
    const firstPage = await context.newPage()
    await AuthHelpers.login(firstPage)
    pages.push(firstPage)
    
    // Create additional tabs
    for (let i = 1; i < routes.length; i++) {
      const page = await context.newPage()
      await page.goto(routes[i])
      await AuthHelpers.verifyAuthenticated(page)
      pages.push(page)
    }
    
    return pages
  }

  /**
   * Verify all tabs are authenticated
   */
  static async verifyAllTabsAuthenticated(pages: Page[]): Promise<void> {
    for (const page of pages) {
      await AuthHelpers.verifyAuthenticated(page)
    }
  }

  /**
   * Verify all tabs are logged out
   */
  static async verifyAllTabsLoggedOut(pages: Page[]): Promise<void> {
    for (const page of pages) {
      await page.reload()
      await expect(page).toHaveURL(/.*login/)
    }
  }

  /**
   * Close all tabs
   */
  static async closeAllTabs(pages: Page[]): Promise<void> {
    for (const page of pages) {
      await page.close()
    }
  }
}

/**
 * Network simulation helpers
 */
export class NetworkHelpers {
  /**
   * Simulate network interruption
   */
  static async simulateNetworkInterruption(
    context: BrowserContext,
    durationMs: number = 5000
  ): Promise<void> {
    await context.setOffline(true)
    await new Promise(resolve => setTimeout(resolve, durationMs))
    await context.setOffline(false)
  }

  /**
   * Simulate slow network
   */
  static async simulateSlowNetwork(context: BrowserContext): Promise<void> {
    await context.route('**/*', async route => {
      // Add delay to simulate slow network
      await new Promise(resolve => setTimeout(resolve, 1000))
      await route.continue()
    })
  }

  /**
   * Reset network conditions
   */
  static async resetNetworkConditions(context: BrowserContext): Promise<void> {
    await context.setOffline(false)
    await context.unroute('**/*')
  }
}

/**
 * Session management helpers
 */
export class SessionHelpers {
  /**
   * Clear all browser storage
   */
  static async clearBrowserStorage(page: Page): Promise<void> {
    await page.evaluate(() => {
      localStorage.clear()
      sessionStorage.clear()
    })
  }

  /**
   * Get session data from localStorage
   */
  static async getSessionData(page: Page): Promise<any> {
    return await page.evaluate(() => {
      const sessionData = localStorage.getItem('auth_session')
      return sessionData ? JSON.parse(sessionData) : null
    })
  }

  /**
   * Verify session persistence
   */
  static async verifySessionPersistence(page: Page): Promise<void> {
    const sessionData = await this.getSessionData(page)
    expect(sessionData).toBeTruthy()
    expect(sessionData.token).toBeTruthy()
  }
}

/**
 * Test data helpers
 */
export class TestDataHelpers {
  /**
   * Generate test report data
   */
  static generateTestReport(
    testName: string,
    status: 'passed' | 'failed',
    metrics?: any
  ): any {
    return {
      testName,
      status,
      timestamp: new Date().toISOString(),
      metrics: metrics || {},
      environment: {
        baseURL: process.env.APP_URL || 'http://localhost:8080',
        browser: 'chromium',
        platform: 'docker'
      }
    }
  }

  /**
   * Log performance metrics
   */
  static logPerformanceMetrics(
    testName: string,
    metrics: any
  ): void {
    console.log(`📊 Performance Metrics for ${testName}:`)
    console.log(JSON.stringify(metrics, null, 2))
  }
}

/**
 * Error handling helpers
 */
export class ErrorHelpers {
  /**
   * Handle and log test errors
   */
  static async handleTestError(
    error: Error,
    page: Page,
    testName: string
  ): Promise<void> {
    console.error(`❌ Test failed: ${testName}`)
    console.error(`Error: ${error.message}`)
    
    // Take screenshot on error
    await page.screenshot({
      path: `storage/logs/error-${testName}-${Date.now()}.png`,
      fullPage: true
    })
    
    // Log page URL and title
    console.error(`Page URL: ${page.url()}`)
    console.error(`Page Title: ${await page.title()}`)
  }

  /**
   * Retry operation with exponential backoff
   */
  static async retryOperation<T>(
    operation: () => Promise<T>,
    maxRetries: number = 3,
    baseDelay: number = 1000
  ): Promise<T> {
    let lastError: Error
    
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      try {
        return await operation()
      } catch (error) {
        lastError = error as Error
        
        if (attempt === maxRetries) {
          throw lastError
        }
        
        const delay = baseDelay * Math.pow(2, attempt - 1)
        console.log(`Retry attempt ${attempt}/${maxRetries} after ${delay}ms`)
        await new Promise(resolve => setTimeout(resolve, delay))
      }
    }
    
    throw lastError!
  }
}