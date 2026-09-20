import type { Department } from './admin'
import { apiFetch } from './client'

export interface AuthTokens {
  token: string
  refreshToken: string
}

export interface MeCompany {
  id: string
  name: string
  openingTime: string
  closingTime: string
}

export interface Me {
  id: string
  email: string
  roles: string[]
  firstName: string | null
  lastName: string | null
  dni: string | null
  sex: string | null
  lastLoginAt: string | null
  emailVerifiedAt: string | null
  avatarUrl: string | null
  departments: Department[]
  company: MeCompany | null
  mustChangePassword: boolean
}

export interface UpdateProfileInput {
  firstName: string | null
  lastName: string | null
  dni: string | null
  sex: string | null
}

export function login(email: string, password: string): Promise<AuthTokens> {
  return apiFetch<AuthTokens>('/login_check', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export function refreshAccessToken(refreshToken: string): Promise<AuthTokens> {
  return apiFetch<AuthTokens>('/token/refresh', {
    method: 'POST',
    body: JSON.stringify({ refreshToken }),
  })
}

export function fetchMe(token: string): Promise<Me> {
  return apiFetch<Me>('/me', { method: 'GET' }, token)
}

export function logout(token: string, refreshToken: string | null): Promise<null> {
  return apiFetch<null>(
    '/logout',
    {
      method: 'POST',
      body: JSON.stringify({ refreshToken }),
    },
    token,
  )
}

export function requestPasswordReset(email: string): Promise<null> {
  return apiFetch<null>('/forgot-password', {
    method: 'POST',
    body: JSON.stringify({ email }),
  })
}

export function resetPassword(email: string, code: string, newPassword: string): Promise<null> {
  return apiFetch<null>('/reset-password', {
    method: 'POST',
    body: JSON.stringify({ email, code, newPassword }),
  })
}

export function verifyEmail(token: string): Promise<null> {
  return apiFetch<null>('/verify-email', {
    method: 'POST',
    body: JSON.stringify({ token }),
  })
}

export function resendVerificationEmail(token: string): Promise<null> {
  return apiFetch<null>('/resend-verification-email', { method: 'POST' }, token)
}

export function changePassword(token: string, currentPassword: string, newPassword: string): Promise<null> {
  return apiFetch<null>(
    '/me/password',
    {
      method: 'POST',
      body: JSON.stringify({ currentPassword, newPassword }),
    },
    token,
  )
}

export function updateProfile(token: string, input: UpdateProfileInput): Promise<null> {
  return apiFetch<null>(
    '/me',
    {
      method: 'PATCH',
      body: JSON.stringify(input),
    },
    token,
  )
}

export function uploadAvatar(token: string, file: File): Promise<{ avatarUrl: string }> {
  const formData = new FormData()
  formData.append('image', file)

  return apiFetch<{ avatarUrl: string }>(
    '/me/avatar',
    {
      method: 'POST',
      body: formData,
    },
    token,
  )
}
