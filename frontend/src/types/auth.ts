// Authentication related types
export interface User {
  id: number;
  name: string;
  email: string;
  preferred_language: string;
  role?: string;
  is_active: boolean;
  last_login_at?: string;
  two_factor_enabled?: boolean;
  created_at: string;
  updated_at: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
  remember?: boolean;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  preferred_language?: string;
}

export interface AuthResponse {
  user: User;
  token: string;
  expires_in: number;
}

export interface TwoFactorData {
  code: string;
  recovery_code?: string;
}

export interface PasswordResetData {
  email: string;
}

export interface PasswordUpdateData {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface ProfileUpdateData {
  name: string;
  email: string;
  preferred_language: string;
}

export interface SessionData {
  id: string;
  ip_address: string;
  user_agent: string;
  last_activity: string;
  is_current: boolean;
}
