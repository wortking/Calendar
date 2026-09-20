import { useQuery } from '@tanstack/react-query'
import Box from '@mui/material/Box'
import CircularProgress from '@mui/material/CircularProgress'
import Divider from '@mui/material/Divider'
import IconButton from '@mui/material/IconButton'
import List from '@mui/material/List'
import ListItem from '@mui/material/ListItem'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { listRoomActivities, type RoomActivity } from '../api/roomActivities'
import { useAuth } from '../auth/AuthContext'

function formatTime(iso: string): string {
  const date = new Date(iso)
  return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false })
}

function formatDayLabel(dateIso: string): string {
  const date = new Date(`${dateIso}T00:00:00`)
  return date.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })
}

function nextDayIso(dateIso: string): string {
  const date = new Date(`${dateIso}T00:00:00`)
  date.setDate(date.getDate() + 1)
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

// Listado de las actividades agendadas ese día en la sala elegida, con su
// empleado y horario — mismo rol que "Turnos del día" para el calendario
// principal, pero a nivel de sala.
export function RoomActivitiesOfDayPanel({
  roomId,
  date,
  onEdit,
}: {
  roomId: string
  date: string
  onEdit: (activity: RoomActivity) => void
}) {
  const { token } = useAuth()

  const activitiesQuery = useQuery({
    queryKey: ['room-activities-of-day', roomId, date],
    queryFn: () => listRoomActivities(token!, roomId, `${date}T00:00:00`, `${nextDayIso(date)}T00:00:00`),
    enabled: !!token && !!roomId,
  })

  const activities = [...(activitiesQuery.data ?? [])].sort((a, b) => a.startAt.localeCompare(b.startAt))

  return (
    <Paper
      elevation={0}
      variant="outlined"
      sx={{ borderRadius: 3, p: 2, height: { md: '100%' }, display: 'flex', flexDirection: 'column' }}
    >
      <Stack spacing={0.5} sx={{ mb: 1 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
          Actividades del día
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ textTransform: 'capitalize' }}>
          {formatDayLabel(date)}
        </Typography>
      </Stack>

      <Divider sx={{ mb: 1 }} />

      {activitiesQuery.isLoading && (
        <Stack sx={{ py: 3, alignItems: 'center' }}>
          <CircularProgress size={22} />
        </Stack>
      )}

      {!activitiesQuery.isLoading && activities.length === 0 && (
        <Typography variant="body2" color="text.secondary" sx={{ py: 1 }}>
          Esta sala no tiene actividades agendadas este día.
        </Typography>
      )}

      <List dense disablePadding sx={{ flexGrow: 1, overflow: 'auto' }}>
        {activities.map((activity) => {
          const label = activity.userName ?? activity.userEmail

          return (
            <ListItem
              key={activity.id}
              disablePadding
              sx={{ py: 0.75, px: 1, borderRadius: 2, '&:hover': { bgcolor: 'action.hover' } }}
            >
              <Stack direction="row" spacing={1.5} sx={{ width: '100%', alignItems: 'center' }}>
                <Box
                  sx={{
                    width: 10,
                    height: 10,
                    borderRadius: '50%',
                    bgcolor: activity.activityTypeColor || 'primary.main',
                    flexShrink: 0,
                  }}
                />

                <Stack sx={{ minWidth: 0, flexGrow: 1 }}>
                  <Typography variant="body2" sx={{ fontWeight: 600 }} noWrap>
                    {activity.activityTypeName ?? 'Actividad'}
                  </Typography>
                  <Typography variant="caption" color="text.secondary" noWrap>
                    {formatTime(activity.startAt)} – {formatTime(activity.endAt)} · {label}
                  </Typography>
                </Stack>

                <IconButton size="small" onClick={() => onEdit(activity)} aria-label="Editar actividad" sx={{ flexShrink: 0 }}>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                  </svg>
                </IconButton>
              </Stack>
            </ListItem>
          )
        })}
      </List>
    </Paper>
  )
}
