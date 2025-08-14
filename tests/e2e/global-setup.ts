import { chromium, FullConfig } from '@playwright/test'

/**
 * Global setup for Playwright E2E tests
 * Ensures test environment is properly configured before running tests
 */
async function globalSetup(config: FullConfig) {
  console.log('🚀 Setting up Playwright E2E test environment...')
  
  const baseURL = config.projects[0].use.baseURL || 'http://localhost:8080'
  
  // Launch browser for setup
  const browser = await chromium.launch()
  const context = await browser.newContext()
  const page = await context.newPage()
  
  try {
    // Wait for application to be ready
    console.log('⏳ Waiting for application to be ready...')
    await page.goto(`${baseURL}/login`, { waitUntil: 'networkidle' })
    
    // Verify login page is accessible
    const loginForm = page.locator('form[data-testid="login-form"]')
    if (await loginForm.isVisible()) {
      console.log('✅ Login page is accessible')
    } else {
      console.log('⚠️  Login form not found, checking for alternative selectors...')
      // Try alternative selectors
      const emailInput = page.locator('input[type="email"]')
      const passwordInput = page.locator('input[type="password"]')
      
      if (await emailInput.isVisible() && await passwordInput.isVisible()) {
        console.log('✅ Login inputs are accessible')
      } else {
        throw new Error('Login page is not properly accessible')
      }
    }
    
    // Verify test user can authenticate
    console.log('🔐 Verifying test user authentication...')
    await page.fill('input[type="email"]', 'test@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    
    // Wait for successful login
    await page.waitForURL('**/dashboard', { timeout: 15000 })
    console.log('✅ Test user authentication verified')
    
    // Logout to clean state
    const userMenu = page.locator('[data-testid="user-menu"]')
    if (await userMenu.isVisible()) {
      await userMenu.click()
      const logoutButton = page.locator('[data-testid="logout-button"]')
      if (await logoutButton.isVisible()) {
        await logoutButton.click()
        await page.waitForURL('**/login')
        console.log('✅ Test user logout verified')
      }
    }
    
    console.log('🎉 Global setup completed successfully!')
    
  } catch (error) {
    console.error('❌ Global setup failed:', error)
    throw error
  } finally {
    await context.close()
    await browser.close()
  }
}

export default globalSetup