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
import Select from '@mui/material/Select'
import Stack from '@mui/material/Stack'
import TextField from '@mui/material/TextField'
import { listUsers } from '../api/admin'
import { ApiError } from '../api/client'
import { createShift } from '../api/shifts'
import { useAuth } from '../auth/AuthContext'

export interface CalendarSlot {
  startStr: string
  endStr: string
}

// "2026-09-21T10:00:00" -> { date: "2026-09-21", time: "10:00" }; una
// selección de día completo en la vista de mes llega sin parte horaria
// ("2026-09-21"), en cuyo caso la hora queda vacía para completar a mano.
function splitSlot(str: string): { date: string; time: string } {
  const [date, time] = str.split('T')
  return { date: date ?? '', time: time ? time.slice(0, 5) : '' }
}

// Diálogo para crear un turno directamente desde el calendario principal,
// arrastrando sobre una franja horaria libre: a diferencia de "Asignar
// turno" en la ficha de un usuario (donde el empleado ya está fijo por la
// fila), acá hace falta elegirlo. Reutiliza el mismo endpoint/permiso que
// esa pantalla (shifts.manage + alcance de departamento del backend), así
// que ya funciona igual para Admin y para Coordinador.
export function CreateShiftDialog({
  slot,
  onClose,
  onSaved,
}: {
  slot: CalendarSlot | null
  onClose: () => void
  onSaved: () => void
}) {
  const { token, user } = useAuth()
  const [userId, setUserId] = useState('')
  const [date, setDate] = useState('')
  const [start, setStart] = useState('')
  const [end, setEnd] = useState('')
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!slot) {
      return
    }

    const startSplit = splitSlot(slot.startStr)
    const endSplit = splitSlot(slot.endStr)

    setUserId('')
    setDate(startSplit.date)
    setStart(startSplit.time)
    setEnd(endSplit.time)
    setError(null)
  }, [slot])

  const usersQuery = useQuery({
    queryKey: ['admin-users-for-shift-create'],
    queryFn: () => listUsers(token!, 1, 100),
    enabled: !!token && !!slot,
  })

  const createMutation = useMutation({
    mutationFn: () => createShift(token!, userId, `${date}T${start}:00`, `${date}T${end}:00`),
    onSuccess: () => {
      onSaved()
      onClose()
    },
    onError: (err: unknown) => {
      setError(err instanceof ApiError ? err.message : 'No se pudo crear el turno.')
    },
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    createMutation.mutate()
  }

  return (
    <Dialog open={!!slot} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>Nuevo turno</DialogTitle>
      <Box component="form" onSubmit={handleSubmit}>
        <DialogContent>
          <Stack spacing={2}>
            <Select
              value={userId}
              onChange={(e) => setUserId(e.target.value)}
              displayEmpty
              fullWidth
              required
              disabled={usersQuery.isLoading}
            >
              <MenuItem value="" disabled>
                Empleado
              </MenuItem>
              {(usersQuery.data?.items ?? []).map((u) => (
                <MenuItem key={u.id} value={u.id}>
                  {u.id === user?.id ? 'Yo' : [u.firstName, u.lastName].filter(Boolean).join(' ') || u.email}
                </MenuItem>
              ))}
            </Select>
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
            {error && <Alert severity="error">{error}</Alert>}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="contained" disabled={!userId || createMutation.isPending}>
            {createMutation.isPending ? 'Creando…' : 'Crear'}
          </Button>
        </DialogActions>
      </Box>
    </Dialog>
  )
}
