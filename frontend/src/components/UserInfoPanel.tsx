import Avatar from '@mui/material/Avatar'
import Chip from '@mui/material/Chip'
import Divider from '@mui/material/Divider'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import type { Me } from '../api/auth'

const ROLE_LABELS: Record<string, string> = {
  ROLE_ADMIN: 'Administrador',
  ROLE_COORDINADOR: 'Coordinador',
  ROLE_USER: 'Empleado',
}

function roleLabel(role: string): string {
  return ROLE_LABELS[role] ?? role
}

export function UserInfoPanel({ user }: { user: Me }) {
  const displayName = [user.firstName, user.lastName].filter(Boolean).join(' ') || user.email
  // Defensivo: una sesión cacheada en localStorage antes de que /me devolviera
  // "departments" no tendría el campo hasta el próximo refresh.
  const departments = user.departments ?? []

  return (
    <Paper elevation={0} variant="outlined" sx={{ borderRadius: 3, p: 3 }}>
      <Stack direction="row" spacing={2} sx={{ alignItems: 'center' }}>
        <Avatar src={user.avatarUrl ?? undefined} sx={{ width: 72, height: 72, fontSize: 28, flexShrink: 0 }}>
          {displayName.charAt(0).toUpperCase()}
        </Avatar>

        <Stack spacing={0.5} sx={{ minWidth: 0 }}>
          <Typography variant="h6" sx={{ fontWeight: 700 }} noWrap>
            {displayName}
          </Typography>
          <Typography variant="body2" color="text.secondary" noWrap>
            {user.email}
          </Typography>
        </Stack>
      </Stack>

      <Divider sx={{ my: 2 }} />

      <Stack spacing={2}>
        <Stack spacing={1}>
          <Typography variant="overline" color="text.secondary">
            Departamento
          </Typography>
          {departments.length > 0 ? (
            <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap', gap: 1 }}>
              {departments.map((department) => (
                <Chip key={department.id} label={department.name} size="small" />
              ))}
            </Stack>
          ) : (
            <Typography variant="body2" color="text.secondary">
              Sin departamento asignado
            </Typography>
          )}
        </Stack>

        <Stack spacing={1}>
          <Typography variant="overline" color="text.secondary">
            Rol
          </Typography>
          <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap', gap: 1 }}>
            {user.roles.map((role) => (
              <Chip key={role} label={roleLabel(role)} size="small" color="primary" variant="outlined" />
            ))}
          </Stack>
        </Stack>
      </Stack>
    </Paper>
  )
}
