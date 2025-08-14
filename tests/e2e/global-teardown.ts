import { chromium, FullConfig } from '@playwright/test'

/**
 * Global teardown for Playwright E2E tests
 * Cleans up test environment after all tests complete
 */
async function globalTeardown(config: FullConfig) {
  console.log('🧹 Starting Playwright E2E test environment cleanup...')
  
  const baseURL = config.projects[0].use.baseURL || 'http://localhost:8080'
  
  // Launch browser for cleanup
  const browser = await chromium.launch()
  const context = await browser.newContext()
  const page = await context.newPage()
  
  try {
    // Ensure any remaining sessions are cleared
    console.log('🔐 Clearing any remaining test sessions...')
    
    // Navigate to application
    await page.goto(`${baseURL}/login`)
    
    // Try to logout if already logged in
    try {
      await page.goto(`${baseURL}/dashboard`, { timeout: 5000 })
      const userMenu = page.locator('[data-testid="user-menu"]')
      if (await userMenu.isVisible({ timeout: 2000 })) {
        await userMenu.click()
        const logoutButton = page.locator('[data-testid="logout-button"]')
        if (await logoutButton.isVisible({ timeout: 2000 })) {
          await logoutButton.click()
          await page.waitForURL('**/login')
          console.log('✅ Cleared remaining session')
        }
      }
    } catch (error) {
      // No session to clear, which is fine
      console.log('ℹ️  No active session to clear')
    }
    
    // Clear browser storage
    await context.clearCookies()
    await page.evaluate(() => {
      localStorage.clear()
      sessionStorage.clear()
    })
    
    console.log('✅ Browser storage cleared')
    
    // Generate cleanup report
    const cleanupReport = {
      timestamp: new Date().toISOString(),
      baseURL,
      status: 'completed',
      actions: [
        'Cleared remaining sessions',
        'Cleared browser cookies',
        'Cleared localStorage',
        'Cleared sessionStorage'
      ]
    }
    
    console.log('📊 Cleanup report:', JSON.stringify(cleanupReport, null, 2))
    console.log('🎉 Global teardown completed successfully!')
    
  } catch (error) {
    console.error('❌ Global teardown encountered an error:', error)
    // Don't throw error in teardown to avoid masking test failures
  } finally {
    await context.close()
    await browser.close()
  }
}

export default globalTeardown