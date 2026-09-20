import { useEffect, useState, type FormEvent } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import Button from '@mui/material/Button'
import Dialog from '@mui/material/Dialog'
import DialogActions from '@mui/material/DialogActions'
import DialogContent from '@mui/material/DialogContent'
import DialogTitle from '@mui/material/DialogTitle'
import MenuItem from '@mui/material/MenuItem'
import Stack from '@mui/material/Stack'
import TextField from '@mui/material/TextField'
import type { ActivityType } from '../api/activityTypes'
import { ApiError } from '../api/client'
import {
  createRoomActivity,
  deleteRoomActivity,
  listEligibleUsers,
  updateRoomActivity,
  type RoomActivity,
} from '../api/roomActivities'
import { useAuth } from '../auth/AuthContext'

// "2026-09-19T13:00:00+00:00" -> { date: "2026-09-19", time: "13:00" }
function splitDateTime(iso: string): { date: string; time: string } {
  const date = new Date(iso)
  const pad = (n: number) => String(n).padStart(2, '0')

  return {
    date: `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`,
    time: `${pad(date.getHours())}:${pad(date.getMinutes())}`,
  }
}

export interface RoomActivityDraft {
  date: string
  start: string
  end: string
}

// Crear o editar una actividad puntual de una sala. Al elegir fecha/horario
// se resuelve en vivo el listado de empleados disponibles (con turno en ese
// horario, y sin otra actividad ya agendada), que es lo que puebla el
// select de "Empleado".
export function RoomActivityDialog({
  open,
  onClose,
  onSaved,
  roomId,
  activityTypes,
  editingActivity,
  draft,
}: {
  open: boolean
  onClose: () => void
  onSaved: () => void
  roomId: string
  activityTypes: ActivityType[]
  editingActivity: RoomActivity | null
  draft: RoomActivityDraft | null
}) {
  const { token } = useAuth()
  const [date, setDate] = useState('')
  const [start, setStart] = useState('')
  const [end, setEnd] = useState('')
  const [activityTypeId, setActivityTypeId] = useState('')
  const [userId, setUserId] = useState('')
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!open) {
      return
    }

    if (editingActivity) {
      const startSplit = splitDateTime(editingActivity.startAt)
      const endSplit = splitDateTime(editingActivity.endAt)
      setDate(startSplit.date)
      setStart(startSplit.time)
      setEnd(endSplit.time)
      setActivityTypeId(editingActivity.activityTypeId)
      setUserId(editingActivity.userId)
    } else if (draft) {
      setDate(draft.date)
      setStart(draft.start)
      setEnd(draft.end)
      setActivityTypeId('')
      setUserId('')
    }
    setError(null)
  }, [open, editingActivity, draft])

  const startAtIso = date && start ? `${date}T${start}:00` : ''
  const endAtIso = date && end ? `${date}T${end}:00` : ''

  const eligibleUsersQuery = useQuery({
    queryKey: ['room-eligible-users', roomId, startAtIso, endAtIso, editingActivity?.id],
    queryFn: () => listEligibleUsers(token!, roomId, startAtIso, endAtIso, editingActivity?.id),
    enabled: !!token && open && !!startAtIso && !!endAtIso,
  })

  const saveMutation = useMutation({
    mutationFn: () =>
      editingActivity
        ? updateRoomActivity(token!, editingActivity.id, roomId, activityTypeId, userId, startAtIso, endAtIso)
        : createRoomActivity(token!, roomId, activityTypeId, userId, startAtIso, endAtIso),
    onSuccess: () => {
      onSaved()
      onClose()
    },
    onError: (err: unknown) => {
      setError(err instanceof ApiError ? err.message : 'No se pudo guardar la actividad.')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: () => deleteRoomActivity(token!, editingActivity!.id),
    onSuccess: () => {
      onSaved()
      onClose()
    },
    onError: (err: unknown) => {
      setError(err instanceof ApiError ? err.message : 'No se pudo eliminar la actividad.')
    },
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    saveMutation.mutate()
  }

  const eligibleUsers = eligibleUsersQuery.data ?? []
  // El empleado ya elegido puede haber quedado fuera del listado (p. ej. al
  // reabrir para editar con un horario que ya no es exactamente el mismo);
  // se lo agrega igual para no perder la selección actual.
  const showCurrentUserFallback =
    !!editingActivity && userId === editingActivity.userId && !eligibleUsers.some((u) => u.id === userId)

  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>{editingActivity ? 'Editar actividad' : 'Nueva actividad'}</DialogTitle>
      <Box component="form" onSubmit={handleSubmit}>
        <DialogContent>
          <Stack spacing={2}>
            <TextField
              label="Fecha"
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              slotProps={{ inputLabel: { shrink: true } }}
              fullWidth
              required
            />
            <TextField
              label="Hora inicio"
              type="time"
              value={start}
              onChange={(e) => setStart(e.target.value)}
              slotProps={{ inputLabel: { shrink: true } }}
              fullWidth
              required
            />
            <TextField
              label="Hora fin"
              type="time"
              value={end}
              onChange={(e) => setEnd(e.target.value)}
              slotProps={{ inputLabel: { shrink: true } }}
              fullWidth
              required
            />
            <TextField
              select
              label="Actividad"
              value={activityTypeId}
              onChange={(e) => setActivityTypeId(e.target.value)}
              fullWidth
              required
            >
              {activityTypes.map((activityType) => (
                <MenuItem key={activityType.id} value={activityType.id}>
                  {activityType.name}
                </MenuItem>
              ))}
            </TextField>
            <TextField
              select
              label="Empleado"
              value={userId}
              onChange={(e) => setUserId(e.target.value)}
              fullWidth
              required
              disabled={eligibleUsersQuery.isFetching}
              helperText={
                !eligibleUsersQuery.isFetching && eligibleUsers.length === 0
                  ? 'No hay empleados con turno disponible en ese horario.'
                  : undefined
              }
            >
              {showCurrentUserFallback && (
                <MenuItem value={editingActivity!.userId}>
                  {editingActivity!.userName ?? editingActivity!.userEmail}
                </MenuItem>
              )}
              {eligibleUsers.map((eligibleUser) => (
                <MenuItem key={eligibleUser.id} value={eligibleUser.id}>
                  {eligibleUser.name ?? eligibleUser.email}
                </MenuItem>
              ))}
            </TextField>
            {error && <Alert severity="error">{error}</Alert>}
          </Stack>
        </DialogContent>
        <DialogActions sx={{ justifyContent: editingActivity ? 'space-between' : 'flex-end', px: 3, pb: 2 }}>
          {editingActivity && (
            <Button
              color="error"
              onClick={() => deleteMutation.mutate()}
              disabled={deleteMutation.isPending || saveMutation.isPending}
            >
              {deleteMutation.isPending ? 'Eliminando…' : 'Eliminar'}
            </Button>
          )}
          <Stack direction="row" spacing={1}>
            <Button onClick={onClose}>Cancelar</Button>
            <Button type="submit" variant="contained" disabled={saveMutation.isPending || deleteMutation.isPending}>
              {saveMutation.isPending ? 'Guardando…' : 'Guardar'}
            </Button>
          </Stack>
        </DialogActions>
      </Box>
    </Dialog>
  )
}
