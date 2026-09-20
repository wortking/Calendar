import { useEffect } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from './AuthContext'

// Solo UX: oculta la sección a quien no la va a poder usar. La autorización
// real la hace el backend con los permisos granulares (roles.manage /
// permissions.manage); acá usamos ROLE_ADMIN como proxy porque el frontend
// no conoce los permisos finos del usuario.
export function useRequireAdmin(): void {
  const { user, isAuthenticated, isLoading } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  useEffect(() => {
    if (isLoading) {
      return
    }

    if (!isAuthenticated || !user?.roles.includes('ROLE_ADMIN')) {
      navigate('/', { replace: true })
      return
    }

    if (user?.mustChangePassword && location.pathname !== '/change-password') {
      navigate('/change-password', { replace: true })
    }
  }, [isLoading, isAuthenticated, user, location.pathname, navigate])
}
