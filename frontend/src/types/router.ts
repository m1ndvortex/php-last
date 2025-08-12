import 'vue-router'

// Extend Vue Router's RouteMeta interface
declare module 'vue-router' {
  interface RouteMeta {
    // Authentication requirements
    requiresAuth?: boolean
    
    // Role-based access control
    roles?: string[]
    
    // Permission-based access control
    permissions?: string[]
    
    // Page metadata
    title?: string
    description?: string
    
    // Layout configuration
    layout?: 'default' | 'auth' | 'minimal'
    
    // Performance optimization
    preload?: boolean
    
    // Navigation behavior
    keepAlive?: boolean
    
    // Breadcrumb configuration
    breadcrumb?: {
      label: string
      parent?: string
    }
    
    // SEO metadata
    meta?: {
      keywords?: string
      author?: string
      robots?: string
    }
    
    // Feature flags
    features?: string[]
    
    // Loading states
    showLoading?: boolean
    
    // Error handling
    errorBoundary?: boolean
  }
}

// Router guard types
export interface RouteGuardContext {
  isAuthenticated: boolean
  user: any | null
  userRole: string | null
  userPermissions: string[]
  sessionValid: boolean
}

export interface NavigationGuardResult {
  allow: boolean
  redirect?: string
  error?: string
}

// Route access control types
export interface AccessControlRule {
  roles?: string[]
  permissions?: string[]
  custom?: (context: RouteGuardContext) => boolean | Promise<boolean>
}

// Loading state types for router
export interface RouterLoadingState {
  isNavigating: boolean
  isAuthenticating: boolean
  isValidatingSession: boolean
  currentRoute: string | null
  targetRoute: string | null
}

export default {}