import { useState } from 'react'
import { Link as RouterLink, useLocation } from 'react-router-dom'
import AppBar from '@mui/material/AppBar'
import Avatar from '@mui/material/Avatar'
import Box from '@mui/material/Box'
import ButtonBase from '@mui/material/ButtonBase'
import Button from '@mui/material/Button'
import Toolbar from '@mui/material/Toolbar'
import Typography from '@mui/material/Typography'
import { useAuth } from '../auth/AuthContext'
import { ProfileDialog } from './ProfileDialog'

export function Header() {
  const { user, logout } = useAuth()
  const location = useLocation()
  const [isProfileOpen, setIsProfileOpen] = useState(false)

  if (!user) {
    return null
  }

  const displayName = user.firstName ?? user.email
  const isAdmin = user.roles.includes('ROLE_ADMIN')
  const isStaff = isAdmin || user.roles.includes('ROLE_COORDINADOR')

  return (
    <AppBar position="static" color="transparent" sx={{ borderBottom: 1, borderColor: 'divider' }}>
      <Toolbar sx={{ gap: 2 }}>
        <Typography
          component={RouterLink}
          to="/dashboard"
          variant="h6"
          sx={{ fontWeight: 700, color: 'inherit', textDecoration: 'none' }}
        >
          Calendar
        </Typography>

        <Button
          component={RouterLink}
          to="/hours"
          color="inherit"
          size="small"
          variant={location.pathname === '/hours' ? 'outlined' : 'text'}
        >
          Mis horas
        </Button>

        {isStaff && (
          <Button
            component={RouterLink}
            to="/admin/users"
            color="inherit"
            size="small"
            variant={location.pathname === '/admin/users' ? 'outlined' : 'text'}
          >
            Usuarios
          </Button>
        )}

        {isStaff && (
          <Button
            component={RouterLink}
            to="/admin/rooms"
            color="inherit"
            size="small"
            variant={location.pathname === '/admin/rooms' ? 'outlined' : 'text'}
          >
            Salas
          </Button>
        )}

        {isAdmin && (
          <Button
            component={RouterLink}
            to="/admin/companies"
            color="inherit"
            size="small"
            variant={location.pathname === '/admin/companies' ? 'outlined' : 'text'}
          >
            Empresas
          </Button>
        )}

        <Box sx={{ flexGrow: 1 }} />

        <ButtonBase
          onClick={() => setIsProfileOpen(true)}
          sx={{ display: 'flex', alignItems: 'center', gap: 1, borderRadius: 2, px: 1, py: 0.5 }}
        >
          <Avatar src={user.avatarUrl ?? undefined} sx={{ width: 32, height: 32 }}>
            {displayName.charAt(0).toUpperCase()}
          </Avatar>
          <Box sx={{ display: { xs: 'none', sm: 'block' } }}>
            <Typography variant="body2">{displayName}</Typography>
          </Box>
        </ButtonBase>
        <Button onClick={() => logout()} color="inherit" size="small">
          Cerrar sesión
        </Button>
      </Toolbar>

      <ProfileDialog open={isProfileOpen} onClose={() => setIsProfileOpen(false)} />
    </AppBar>
  )
}
