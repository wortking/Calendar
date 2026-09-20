import { useEffect, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import Avatar from '@mui/material/Avatar'
import CircularProgress from '@mui/material/CircularProgress'
import Divider from '@mui/material/Divider'
import IconButton from '@mui/material/IconButton'
import List from '@mui/material/List'
import ListItem from '@mui/material/ListItem'
import Pagination from '@mui/material/Pagination'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { EditShiftDialog } from './EditShiftDialog'
import { listShiftsForDate, type ShiftWithUser } from '../api/shifts'
import { useAuth } from '../auth/AuthContext'

const PAGE_SIZE = 10

function formatTime(iso: string): string {
  const date = new Date(iso)
  return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false })
}

function formatDayLabel(dateIso: string): string {
  const date = new Date(`${dateIso}T00:00:00`)
  return date.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' })
}

export function ShiftsOfDayPanel({ date }: { date: string }) {
  const { token, user } = useAuth()
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const canManage = user?.roles.includes('ROLE_ADMIN') || user?.roles.includes('ROLE_COORDINADOR')

  const [editingShift, setEditingShift] = useState<ShiftWithUser | null>(null)

  const shiftsQuery = useQuery({
    queryKey: ['shifts-by-date', date],
    queryFn: () => listShiftsForDate(token!, date),
    enabled: !!token,
  })

  const shifts = shiftsQuery.data ?? []
  const totalPages = Math.max(1, Math.ceil(shifts.length / PAGE_SIZE))
  const pageItems = shifts.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE)

  // Si cambia el día que se está mirando (o la lista se achica), volver a la
  // primera página en vez de quedar en una página vacía.
  useEffect(() => {
    setPage(1)
  }, [date])

  return (
    <Paper
      elevation={0}
      variant="outlined"
      sx={{ borderRadius: 3, p: 2, height: { md: '100%' }, display: 'flex', flexDirection: 'column' }}
    >
      <Stack spacing={0.5} sx={{ mb: 1 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
          Turnos del día
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ textTransform: 'capitalize' }}>
          {formatDayLabel(date)}
        </Typography>
      </Stack>

      <Divider sx={{ mb: 1 }} />

      {shiftsQuery.isLoading && (
        <Stack sx={{ py: 3, alignItems: 'center' }}>
          <CircularProgress size={22} />
        </Stack>
      )}

      {!shiftsQuery.isLoading && shifts.length === 0 && (
        <Typography variant="body2" color="text.secondary" sx={{ py: 1 }}>
          Nadie tiene turno este día.
        </Typography>
      )}

      <List dense disablePadding sx={{ flexGrow: 1, overflow: 'auto' }}>
        {pageItems.map((shift) => {
          const label = shift.userName ?? shift.userEmail

          return (
            <ListItem
              key={shift.id}
              disablePadding
              sx={{
                py: 0.75,
                px: 1,
                borderRadius: 2,
                '&:hover': canManage ? { bgcolor: 'action.hover' } : undefined,
              }}
            >
              <Stack direction="row" spacing={1.5} sx={{ width: '100%', alignItems: 'center' }}>
                <Avatar src={shift.avatarUrl ?? undefined} sx={{ width: 32, height: 32, fontSize: 14, flexShrink: 0 }}>
                  {label.charAt(0).toUpperCase()}
                </Avatar>

                <Stack sx={{ minWidth: 0, flexGrow: 1 }}>
                  <Typography variant="body2" sx={{ fontWeight: 600 }} noWrap>
                    {label}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    {formatTime(shift.startAt)} – {formatTime(shift.endAt)}
                    {shift.roomActivities.length > 0
                      ? ` · ${shift.roomActivities.map((a) => a.activityTypeName).filter(Boolean).join(', ')}`
                      : ''}
                  </Typography>
                </Stack>

                {canManage && (
                  <IconButton
                    size="small"
                    onClick={() => setEditingShift(shift)}
                    aria-label="Editar turno"
                    sx={{ flexShrink: 0 }}
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                    </svg>
                  </IconButton>
                )}
              </Stack>
            </ListItem>
          )
        })}
      </List>

      {totalPages > 1 && (
        <Stack sx={{ pt: 1, alignItems: 'center' }}>
          <Pagination size="small" count={totalPages} page={page} onChange={(_, value) => setPage(value)} />
        </Stack>
      )}

      <EditShiftDialog
        shift={editingShift}
        onClose={() => setEditingShift(null)}
        onSaved={() => queryClient.invalidateQueries({ queryKey: ['shifts-by-date'] })}
      />
    </Paper>
  )
}
