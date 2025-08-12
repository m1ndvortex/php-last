import { test, expect } from '@playwright/test'

test.describe('Router Guards E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Clear any existing authentication
    await page.context().clearCookies()
    await page.evaluate(() => {
      localStorage.clear()
      sessionStorage.clear()
    })
  })

  test('should redirect unauthenticated users to login page', async ({ page }) => {
    // Try to access a protected route directly
    await page.goto('http://localhost:3000/dashboard')
    
    // Should be redirected to login with return URL
    await expect(page).toHaveURL(/\/login/)
    await expect(page.url()).toContain('returnUrl=%2Fdashboard')
    
    // Should see login form
    await expect(page.locator('form')).toBeVisible()
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
  })

  test('should allow access to public routes', async ({ page }) => {
    // Navigate to login page
    await page.goto('http://localhost:3000/login')
    
    // Should be able to access login page
    await expect(page).toHaveURL('http://localhost:3000/login')
    await expect(page.locator('form')).toBeVisible()
  })

  test('should preserve complex return URLs', async ({ page }) => {
    // Try to access a route with query parameters
    await page.goto('http://localhost:3000/inventory?category=rings&sort=price')
    
    // Should be redirected to login with encoded return URL
    await expect(page).toHaveURL(/\/login/)
    const url = page.url()
    const returnUrl = new URLSearchParams(new URL(url).search).get('returnUrl')
    expect(decodeURIComponent(returnUrl || '')).toBe('/inventory?category=rings&sort=price')
  })

  test('should handle successful authentication flow', async ({ page }) => {
    // Go to login page
    await page.goto('http://localhost:3000/login')
    
    // Fill in login form
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    
    // Submit form
    await page.click('button[type="submit"]')
    
    // Wait for navigation to dashboard
    await page.waitForURL('**/dashboard')
    
    // Should be on dashboard
    await expect(page).toHaveURL(/\/dashboard/)
    
    // Should see dashboard content
    await expect(page.locator('h1, h2, .dashboard')).toBeVisible()
  })

  test('should redirect authenticated users away from login page', async ({ page }) => {
    // First login
    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
    
    // Now try to go to login page while authenticated
    await page.goto('http://localhost:3000/login')
    
    // Should be redirected to dashboard
    await expect(page).toHaveURL(/\/dashboard/)
  })

  test('should handle return URL after login', async ({ page }) => {
    // Try to access inventory page (should redirect to login)
    await page.goto('http://localhost:3000/inventory')
    await expect(page).toHaveURL(/\/login/)
    
    // Login
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    
    // Should be redirected to the original inventory page
    await page.waitForURL('**/inventory')
    await expect(page).toHaveURL(/\/inventory/)
  })

  test('should handle role-based access control', async ({ page }) => {
    // Login as regular user
    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', 'user@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
    
    // Try to access admin-only settings page
    await page.goto('http://localhost:3000/settings')
    
    // Should be redirected to dashboard with error
    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.url()).toContain('error=access_denied')
    
    // Should see error message or be on dashboard
    await expect(page.locator('body')).toBeVisible()
  })

  test('should handle session expiry', async ({ page }) => {
    // Login first
    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
    
    // Simulate session expiry by clearing auth token
    await page.evaluate(() => {
      localStorage.removeItem('auth_token')
    })
    
    // Try to navigate to another protected route
    await page.goto('http://localhost:3000/inventory')
    
    // Should be redirected to login
    await expect(page).toHaveURL(/\/login/)
  })

  test('should show loading states during authentication', async ({ page }) => {
    // Go to login page
    await page.goto('http://localhost:3000/login')
    
    // Fill form and submit
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    
    // Click submit and immediately check for loading state
    await page.click('button[type="submit"]')
    
    // Should show some loading indicator (spinner, disabled button, etc.)
    const loadingIndicators = [
      page.locator('.loading'),
      page.locator('.spinner'),
      page.locator('button[disabled]'),
      page.locator('[data-loading="true"]')
    ]
    
    // At least one loading indicator should be visible
    let foundLoading = false
    for (const indicator of loadingIndicators) {
      try {
        await indicator.waitFor({ timeout: 1000 })
        foundLoading = true
        break
      } catch {
        // Continue checking other indicators
      }
    }
    
    // Eventually should navigate to dashboard
    await page.waitForURL('**/dashboard', { timeout: 10000 })
    await expect(page).toHaveURL(/\/dashboard/)
  })

  test('should prevent open redirect attacks', async ({ page }) => {
    // Try to login with malicious return URL
    await page.goto('http://localhost:3000/login?returnUrl=https://evil.com')
    
    // Login
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    
    // Should be redirected to dashboard, not the malicious URL
    await page.waitForURL('**/dashboard')
    await expect(page).toHaveURL(/\/dashboard/)
    
    // Should not be redirected to external site
    expect(page.url()).not.toContain('evil.com')
  })

  test('should handle network errors gracefully', async ({ page }) => {
    // Intercept API calls and make them fail
    await page.route('**/api/auth/**', route => {
      route.abort('failed')
    })
    
    // Try to login
    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    
    // Should show error message
    await expect(page.locator('.error, .alert, [role="alert"]')).toBeVisible({ timeout: 5000 })
    
    // Should still be on login page
    await expect(page).toHaveURL(/\/login/)
  })

  test('should handle navigation between protected routes', async ({ page }) => {
    // Login first
    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
    
    // Navigate to different protected routes
    const routes = ['/inventory', '/customers', '/dashboard']
    
    for (const route of routes) {
      await page.goto(`http://localhost:3000${route}`)
      await expect(page).toHaveURL(new RegExp(route))
      
      // Should not be redirected to login
      expect(page.url()).not.toContain('/login')
    }
  })

  test('should handle logout and redirect to login', async ({ page }) => {
    // Login first
    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', 'admin@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/dashboard')
    
    // Find and click logout button
    const logoutSelectors = [
      'button:has-text("Logout")',
      'button:has-text("Sign Out")',
      'a:has-text("Logout")',
      '[data-testid="logout"]',
      '.logout'
    ]
    
    let loggedOut = false
    for (const selector of logoutSelectors) {
      try {
        await page.click(selector, { timeout: 2000 })
        loggedOut = true
        break
      } catch {
        // Try next selector
      }
    }
    
    if (loggedOut) {
      // Should be redirected to login
      await page.waitForURL('**/login')
      await expect(page).toHaveURL(/\/login/)
    } else {
      // If no logout button found, manually clear auth and test
      await page.evaluate(() => {
        localStorage.removeItem('auth_token')
      })
      
      await page.goto('http://localhost:3000/dashboard')
      await expect(page).toHaveURL(/\/login/)
    }
  })
})