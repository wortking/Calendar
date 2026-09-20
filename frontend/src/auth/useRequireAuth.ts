import { useEffect } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from './AuthContext'

export function useRequireAuth(): void {
  const { user, isAuthenticated, isLoading } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  useEffect(() => {
    if (isLoading) {
      return
    }

    if (!isAuthenticated) {
      navigate('/', { replace: true })
      return
    }

    if (user?.mustChangePassword && location.pathname !== '/change-password') {
      navigate('/change-password', { replace: true })
    }
  }, [isLoading, isAuthenticated, user, location.pathname, navigate])
}
