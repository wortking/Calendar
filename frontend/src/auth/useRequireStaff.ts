import { useEffect } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from './AuthContext'

// Solo UX: oculta la sección a quien no la va a poder usar. La autorización
// real la hace el backend con el permiso granular (p. ej. room_activities.manage);
// acá usamos ROLE_ADMIN/ROLE_COORDINADOR como proxy porque el frontend no
// conoce los permisos finos del usuario.
export function useRequireStaff(): void {
  const { user, isAuthenticated, isLoading } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  useEffect(() => {
    if (isLoading) {
      return
    }

    const isStaff = user?.roles.includes('ROLE_ADMIN') || user?.roles.includes('ROLE_COORDINADOR')

    if (!isAuthenticated || !isStaff) {
      navigate('/', { replace: true })
      return
    }

    if (user?.mustChangePassword && location.pathname !== '/change-password') {
      navigate('/change-password', { replace: true })
    }
  }, [isLoading, isAuthenticated, user, location.pathname, navigate])
}
