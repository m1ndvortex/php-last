import { test, expect, Browser, BrowserContext, Page } from '@playwright/test'
import { 
  AuthHelpers, 
  PerformanceHelpers, 
  MultiTabHelpers, 
  NetworkHelpers, 
  SessionHelpers,
  TestDataHelpers,
  ErrorHelpers,
  TEST_CREDENTIALS,
  APP_ROUTES 
} from './helpers/test-helpers'

test.describe('Seamless Tab Navigation E2E Tests', () => {
  let browser: Browser
  let context: BrowserContext
  let page1: Page
  let page2: Page
  let page3: Page

  const APP_URL = process.env.APP_URL || 'http://localhost:8080'

  test.beforeAll(async ({ browser: testBrowser }) => {
    browser = testBrowser
    context = await browser.newContext()
  })

  test.afterAll(async () => {
    await context.close()
  })

  test.beforeEach(async () => {
    // Create multiple tabs for testing
    page1 = await context.newPage()
    page2 = await context.newPage()
    page3 = await context.newPage()
  })

  test.afterEach(async () => {
    await page1.close()
    await page2.close()
    await page3.close()
  })

  test('should authenticate in one tab and persist session across all tabs', async () => {
    try {
      // Perform login in first tab using helper
      await AuthHelpers.login(page1)
      await AuthHelpers.verifyAuthenticated(page1)
      
      // Navigate to the same app in second tab
      await page2.goto(`${APP_URL}${APP_ROUTES.dashboard}`)
      
      // Should automatically be logged in without authentication prompt
      await expect(page2).toHaveURL(/.*dashboard/)
      await AuthHelpers.verifyAuthenticated(page2)
      
      // Navigate to the same app in third tab
      await page3.goto(`${APP_URL}${APP_ROUTES.inventory}`)
      
      // Should automatically be logged in and access protected route
      await expect(page3).toHaveURL(/.*inventory/)
      await AuthHelpers.verifyAuthenticated(page3)
      
      // Verify session persistence
      await SessionHelpers.verifySessionPersistence(page1)
      
      const report = TestDataHelpers.generateTestReport(
        'Multi-tab authentication persistence',
        'passed'
      )
      TestDataHelpers.logPerformanceMetrics('Authentication Persistence', report)
      
    } catch (error) {
      await ErrorHelpers.handleTestError(error as Error, page1, 'multi-tab-auth-persistence')
      throw error
    }
  })

  test('should not show authentication prompts when switching between authenticated tabs', async () => {
    // Login in first tab
    await page1.goto(`${APP_URL}/login`)
    await page1.fill('input[type="email"]', TEST_CREDENTIALS.email)
    await page1.fill('input[type="password"]', TEST_CREDENTIALS.password)
    await page1.click('button[type="submit"]')
    await page1.waitForURL('**/dashboard')
    
    // Open different routes in other tabs
    await page2.goto(`${APP_URL}/customers`)
    await page3.goto(`${APP_URL}/invoices`)
    
    // Verify all tabs are authenticated without login prompts
    await expect(page1).toHaveURL(/.*dashboard/)
    await expect(page2).toHaveURL(/.*customers/)
    await expect(page3).toHaveURL(/.*invoices/)
    
    // Verify no login forms are visible in any tab
    await expect(page1.locator('form[data-testid="login-form"]')).not.toBeVisible()
    await expect(page2.locator('form[data-testid="login-form"]')).not.toBeVisible()
    await expect(page3.locator('form[data-testid="login-form"]')).not.toBeVisible()
    
    // Verify user menus are visible in all tabs
    await expect(page1.locator('[data-testid="user-menu"]')).toBeVisible()
    await expect(page2.locator('[data-testid="user-menu"]')).toBeVisible()
    await expect(page3.locator('[data-testid="user-menu"]')).toBeVisible()
  })

  test('should measure tab switching performance under 100ms', async () => {
    try {
      // Login and setup tabs using helper
      const pages = await MultiTabHelpers.createAuthenticatedTabs(context, [
        APP_ROUTES.dashboard,
        APP_ROUTES.customers,
        APP_ROUTES.inventory
      ])
      
      // Wait for all tabs to be fully loaded
      await Promise.all(pages.map(page => page.waitForLoadState('networkidle')))
      
      // Measure tab switching performance using helper
      const performanceMetrics = await PerformanceHelpers.measureMultipleTabSwitches(pages, 5)
      
      // Verify performance requirement (target: <100ms)
      PerformanceHelpers.verifyPerformanceRequirement(performanceMetrics.average, 100)
      
      // Log detailed performance metrics
      const report = TestDataHelpers.generateTestReport(
        'Tab switching performance',
        'passed',
        performanceMetrics
      )
      TestDataHelpers.logPerformanceMetrics('Tab Switching Performance', performanceMetrics)
      
      console.log(`Average tab switching time: ${performanceMetrics.average}ms`)
      console.log(`Min: ${performanceMetrics.min}ms, Max: ${performanceMetrics.max}ms`)
      console.log(`Individual times: ${performanceMetrics.times.join(', ')}ms`)
      
      // Close additional pages
      await MultiTabHelpers.closeAllTabs(pages.slice(3)) // Close any extra pages
      
    } catch (error) {
      await ErrorHelpers.handleTestError(error as Error, page1, 'tab-switching-performance')
      throw error
    }
  })

  test('should logout from all tabs when logout is initiated from one tab', async () => {
    try {
      // Create authenticated tabs using helper
      const pages = await MultiTabHelpers.createAuthenticatedTabs(context, [
        APP_ROUTES.dashboard,
        APP_ROUTES.customers,
        APP_ROUTES.inventory
      ])
      
      // Verify all tabs are authenticated
      await MultiTabHelpers.verifyAllTabsAuthenticated(pages)
      
      // Perform logout from first tab using helper
      await AuthHelpers.logout(pages[0])
      
      // Verify other tabs are also logged out
      await MultiTabHelpers.verifyAllTabsLoggedOut(pages.slice(1))
      
      // Verify login forms are visible in all tabs
      for (const page of pages) {
        await AuthHelpers.verifyNotAuthenticated(page)
      }
      
      const report = TestDataHelpers.generateTestReport(
        'Cross-tab logout functionality',
        'passed'
      )
      TestDataHelpers.logPerformanceMetrics('Cross-tab Logout', report)
      
      // Close additional pages
      await MultiTabHelpers.closeAllTabs(pages.slice(3))
      
    } catch (error) {
      await ErrorHelpers.handleTestError(error as Error, page1, 'cross-tab-logout')
      throw error
    }
  })

  test('should recover session after network interruption', async () => {
    try {
      // Login using helper
      await AuthHelpers.login(page1)
      await AuthHelpers.verifyAuthenticated(page1)
      
      // Simulate network interruption using helper
      await NetworkHelpers.simulateNetworkInterruption(context, 3000)
      
      // Navigate again - should recover session
      await ErrorHelpers.retryOperation(async () => {
        await page1.goto(`${APP_URL}${APP_ROUTES.customers}`)
        await page1.waitForLoadState('networkidle')
      }, 3, 2000)
      
      // Verify session is recovered and user is still authenticated
      await expect(page1).toHaveURL(/.*customers/)
      await AuthHelpers.verifyAuthenticated(page1)
      
      // Open new tab to verify cross-tab session persistence after recovery
      const page4 = await context.newPage()
      await page4.goto(`${APP_URL}${APP_ROUTES.inventory}`)
      
      await expect(page4).toHaveURL(/.*inventory/)
      await AuthHelpers.verifyAuthenticated(page4)
      
      // Verify session data is intact
      await SessionHelpers.verifySessionPersistence(page1)
      
      const report = TestDataHelpers.generateTestReport(
        'Session recovery after network interruption',
        'passed'
      )
      TestDataHelpers.logPerformanceMetrics('Network Recovery', report)
      
      await page4.close()
      
    } catch (error) {
      await ErrorHelpers.handleTestError(error as Error, page1, 'network-interruption-recovery')
      throw error
    } finally {
      // Ensure network conditions are reset
      await NetworkHelpers.resetNetworkConditions(context)
    }
  })

  test('should handle concurrent login attempts across tabs', async () => {
    // Navigate to login page in multiple tabs simultaneously
    await Promise.all([
      page1.goto(`${APP_URL}/login`),
      page2.goto(`${APP_URL}/login`),
      page3.goto(`${APP_URL}/login`)
    ])
    
    // Attempt to login from multiple tabs simultaneously
    const loginPromises = [
      (async () => {
        await page1.fill('input[type="email"]', TEST_CREDENTIALS.email)
        await page1.fill('input[type="password"]', TEST_CREDENTIALS.password)
        await page1.click('button[type="submit"]')
      })(),
      (async () => {
        await page2.fill('input[type="email"]', TEST_CREDENTIALS.email)
        await page2.fill('input[type="password"]', TEST_CREDENTIALS.password)
        await page2.click('button[type="submit"]')
      })(),
      (async () => {
        await page3.fill('input[type="email"]', TEST_CREDENTIALS.email)
        await page3.fill('input[type="password"]', TEST_CREDENTIALS.password)
        await page3.click('button[type="submit"]')
      })()
    ]
    
    await Promise.all(loginPromises)
    
    // Wait for all tabs to complete authentication
    await Promise.all([
      page1.waitForURL('**/dashboard', { timeout: 10000 }),
      page2.waitForURL('**/dashboard', { timeout: 10000 }),
      page3.waitForURL('**/dashboard', { timeout: 10000 })
    ])
    
    // Verify all tabs are successfully authenticated
    await expect(page1).toHaveURL(/.*dashboard/)
    await expect(page2).toHaveURL(/.*dashboard/)
    await expect(page3).toHaveURL(/.*dashboard/)
    
    // Verify user menus are visible in all tabs
    await expect(page1.locator('[data-testid="user-menu"]')).toBeVisible()
    await expect(page2.locator('[data-testid="user-menu"]')).toBeVisible()
    await expect(page3.locator('[data-testid="user-menu"]')).toBeVisible()
  })

  test('should maintain session across browser restart simulation', async () => {
    // Login in first tab
    await page1.goto(`${APP_URL}/login`)
    await page1.fill('input[type="email"]', TEST_CREDENTIALS.email)
    await page1.fill('input[type="password"]', TEST_CREDENTIALS.password)
    await page1.click('button[type="submit"]')
    await page1.waitForURL('**/dashboard')
    
    // Verify authenticated state
    await expect(page1.locator('[data-testid="user-menu"]')).toBeVisible()
    
    // Close all pages (simulate browser restart)
    await page1.close()
    await page2.close()
    await page3.close()
    
    // Create new pages (simulate reopening browser)
    const newPage1 = await context.newPage()
    const newPage2 = await context.newPage()
    
    // Navigate to protected routes
    await newPage1.goto(`${APP_URL}/dashboard`)
    await newPage2.goto(`${APP_URL}/customers`)
    
    // Should maintain session and not require re-authentication
    await expect(newPage1).toHaveURL(/.*dashboard/)
    await expect(newPage2).toHaveURL(/.*customers/)
    
    // Verify user menus are visible
    await expect(newPage1.locator('[data-testid="user-menu"]')).toBeVisible()
    await expect(newPage2.locator('[data-testid="user-menu"]')).toBeVisible()
    
    await newPage1.close()
    await newPage2.close()
  })

  test('should validate logout functionality works reliably in all scenarios', async () => {
    // Test scenario 1: Normal logout
    await page1.goto(`${APP_URL}/login`)
    await page1.fill('input[type="email"]', TEST_CREDENTIALS.email)
    await page1.fill('input[type="password"]', TEST_CREDENTIALS.password)
    await page1.click('button[type="submit"]')
    await page1.waitForURL('**/dashboard')
    
    // Perform logout
    await page1.click('[data-testid="user-menu"]')
    await page1.click('[data-testid="logout-button"]')
    await page1.waitForURL('**/login')
    
    // Verify logout completed
    await expect(page1).toHaveURL(/.*login/)
    await expect(page1.locator('form[data-testid="login-form"]')).toBeVisible()
    
    // Test scenario 2: Logout with multiple tabs open
    await page1.fill('input[type="email"]', TEST_CREDENTIALS.email)
    await page1.fill('input[type="password"]', TEST_CREDENTIALS.password)
    await page1.click('button[type="submit"]')
    await page1.waitForURL('**/dashboard')
    
    await page2.goto(`${APP_URL}/customers`)
    await page3.goto(`${APP_URL}/inventory`)
    
    // Logout from one tab
    await page2.click('[data-testid="user-menu"]')
    await page2.click('[data-testid="logout-button"]')
    await page2.waitForURL('**/login')
    
    // Verify all tabs are logged out
    await page1.reload()
    await page3.reload()
    
    await expect(page1).toHaveURL(/.*login/)
    await expect(page2).toHaveURL(/.*login/)
    await expect(page3).toHaveURL(/.*login/)
    
    // Test scenario 3: Logout verification after network issues
    await page1.fill('input[type="email"]', TEST_CREDENTIALS.email)
    await page1.fill('input[type="password"]', TEST_CREDENTIALS.password)
    await page1.click('button[type="submit"]')
    await page1.waitForURL('**/dashboard')
    
    // Simulate network issue during logout
    await context.setOffline(true)
    await page1.click('[data-testid="user-menu"]')
    await page1.click('[data-testid="logout-button"]')
    
    // Restore network
    await context.setOffline(false)
    
    // Should still complete logout
    await page1.waitForURL('**/login', { timeout: 10000 })
    await expect(page1).toHaveURL(/.*login/)
  })
})