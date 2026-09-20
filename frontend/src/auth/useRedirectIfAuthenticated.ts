import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from './AuthContext'

export function useRedirectIfAuthenticated(): void {
  const { user, isAuthenticated, isLoading } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    if (!isLoading && isAuthenticated) {
      navigate(user?.mustChangePassword ? '/change-password' : '/dashboard', { replace: true })
    }
  }, [isLoading, isAuthenticated, user, navigate])
}
