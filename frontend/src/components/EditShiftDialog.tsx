import { useEffect, useState, type FormEvent } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import Button from '@mui/material/Button'
import Collapse from '@mui/material/Collapse'
import Dialog from '@mui/material/Dialog'
import DialogActions from '@mui/material/DialogActions'
import DialogContent from '@mui/material/DialogContent'
import DialogTitle from '@mui/material/DialogTitle'
import Divider from '@mui/material/Divider'
import MenuItem from '@mui/material/MenuItem'
import Select from '@mui/material/Select'
import Stack from '@mui/material/Stack'
import TextField from '@mui/material/TextField'
import Typography from '@mui/material/Typography'
import { listUsers } from '../api/admin'
import { ApiError } from '../api/client'
import { coverShift, updateShift, type CoverShiftResult, type ShiftWithUser } from '../api/shifts'
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

// Diálogo de "editar turno" compartido por el panel "Turnos del día" y el
// doble click sobre un turno en el calendario: reasignar empleado y
// cambiar fecha/horario, o cubrir el resto del turno con un reemplazante
// (ej. una urgencia a mitad de turno). Las actividades puntuales de sala
// se gestionan aparte, desde el calendario propio de cada sala, salvo las
// que "cubrir turno" reasigna automáticamente. `shift` no nulo determina
// si el diálogo está abierto (mismo patrón que un Dialog controlado por
// su propio estado).
export function EditShiftDialog({
  shift,
  onClose,
  onSaved,
}: {
  shift: ShiftWithUser | null
  onClose: () => void
  onSaved: () => void
}) {
  const { token } = useAuth()
  const [userId, setUserId] = useState('')
  const [date, setDate] = useState('')
  const [start, setStart] = useState('')
  const [end, setEnd] = useState('')
  const [error, setError] = useState<string | null>(null)

  const [isCoverOpen, setIsCoverOpen] = useState(false)
  const [cutoffTime, setCutoffTime] = useState('')
  const [replacementUserId, setReplacementUserId] = useState('')
  const [coverError, setCoverError] = useState<string | null>(null)
  const [coverResult, setCoverResult] = useState<CoverShiftResult | null>(null)

  useEffect(() => {
    if (!shift) {
      return
    }

    const startSplit = splitDateTime(shift.startAt)
    const endSplit = splitDateTime(shift.endAt)

    setUserId(shift.userId)
    setDate(startSplit.date)
    setStart(startSplit.time)
    setEnd(endSplit.time)
    setError(null)

    setIsCoverOpen(false)
    setCutoffTime('')
    setReplacementUserId('')
    setCoverError(null)
    setCoverResult(null)
  }, [shift])

  // Alcanza con la primera página para poblar el selector de "reasignar a": a
  // un coordinador el backend ya le devuelve solo su propio departamento.
  const usersQuery = useQuery({
    queryKey: ['admin-users-for-shift-edit'],
    queryFn: () => listUsers(token!, 1, 100),
    enabled: !!token && !!shift,
  })

  const updateMutation = useMutation({
    mutationFn: () => updateShift(token!, shift!.id, userId, `${date}T${start}:00`, `${date}T${end}:00`),
    onSuccess: () => {
      onSaved()
      onClose()
    },
    onError: (err: unknown) => {
      setError(err instanceof ApiError ? err.message : 'No se pudo actualizar el turno.')
    },
  })

  const coverMutation = useMutation({
    mutationFn: () => {
      const shiftDate = splitDateTime(shift!.startAt).date
      return coverShift(token!, shift!.id, `${shiftDate}T${cutoffTime}:00`, replacementUserId)
    },
    onSuccess: (result) => {
      setCoverResult(result)
      onSaved()
    },
    onError: (err: unknown) => {
      setCoverError(err instanceof ApiError ? err.message : 'No se pudo cubrir el turno.')
    },
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    updateMutation.mutate()
  }

  function handleCoverSubmit(event: FormEvent) {
    event.preventDefault()
    setCoverError(null)
    coverMutation.mutate()
  }

  return (
    <Dialog open={!!shift} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>Editar turno</DialogTitle>
      <Box component="form" onSubmit={handleSubmit}>
        <DialogContent>
          <Stack spacing={2}>
            <Select value={userId} onChange={(e) => setUserId(e.target.value)} displayEmpty fullWidth disabled={usersQuery.isLoading}>
              {shift && <MenuItem value={shift.userId}>{shift.userName ?? shift.userEmail}</MenuItem>}
              {(usersQuery.data?.items ?? [])
                .filter((u) => u.id !== shift?.userId)
                .map((u) => (
                  <MenuItem key={u.id} value={u.id}>
                    {[u.firstName, u.lastName].filter(Boolean).join(' ') || u.email}
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
            {shift && shift.roomActivities.length > 0 && (
              <Alert severity="info">
                Este turno tiene {shift.roomActivities.length} actividad{shift.roomActivities.length === 1 ? '' : 'es'} de
                sala agendada{shift.roomActivities.length === 1 ? '' : 's'}. Se gestionan desde el calendario de la sala.
              </Alert>
            )}
            {error && <Alert severity="error">{error}</Alert>}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="contained" disabled={updateMutation.isPending}>
            {updateMutation.isPending ? 'Guardando…' : 'Guardar'}
          </Button>
        </DialogActions>
      </Box>

      <Divider />

      <Box sx={{ px: 3, py: 2 }}>
        {!coverResult ? (
          <>
            <Button size="small" onClick={() => setIsCoverOpen((v) => !v)}>
              {isCoverOpen ? 'Ocultar' : '¿Necesita dejar el turno antes de tiempo?'}
            </Button>
            <Collapse in={isCoverOpen} unmountOnExit>
              <Box component="form" onSubmit={handleCoverSubmit} sx={{ pt: 1.5 }}>
                <Stack spacing={2}>
                  <Typography variant="body2" color="text.secondary">
                    Corta el turno de {shift?.userName ?? shift?.userEmail} a la hora indicada y crea uno nuevo con el
                    reemplazante para lo que queda. Las actividades de sala agendadas después de esa hora se le
                    reasignan automáticamente (las que no pueda tomar por otro compromiso se avisan al terminar).
                  </Typography>
                  <TextField
                    label="Hora de corte"
                    type="time"
                    value={cutoffTime}
                    onChange={(e) => setCutoffTime(e.target.value)}
                    slotProps={{ inputLabel: { shrink: true } }}
                    fullWidth
                    required
                  />
                  <Select
                    value={replacementUserId}
                    onChange={(e) => setReplacementUserId(e.target.value)}
                    displayEmpty
                    fullWidth
                    required
                  >
                    <MenuItem value="" disabled>
                      Reemplazante
                    </MenuItem>
                    {(usersQuery.data?.items ?? [])
                      .filter((u) => u.id !== shift?.userId)
                      .map((u) => (
                        <MenuItem key={u.id} value={u.id}>
                          {[u.firstName, u.lastName].filter(Boolean).join(' ') || u.email}
                        </MenuItem>
                      ))}
                  </Select>
                  {coverError && <Alert severity="error">{coverError}</Alert>}
                  <Button
                    type="submit"
                    variant="outlined"
                    color="warning"
                    disabled={coverMutation.isPending || !cutoffTime || !replacementUserId}
                  >
                    {coverMutation.isPending ? 'Cubriendo…' : 'Cubrir turno'}
                  </Button>
                </Stack>
              </Box>
            </Collapse>
          </>
        ) : (
          <Stack spacing={1.5}>
            <Alert severity="success">
              Turno cubierto. Se creó el turno del reemplazante y se reasignaron {coverResult.activitiesReassigned}{' '}
              actividad{coverResult.activitiesReassigned === 1 ? '' : 'es'} de sala
              {coverResult.activitiesSkipped > 0
                ? ` (${coverResult.activitiesSkipped} no se pudieron reasignar por otro compromiso del reemplazante y quedaron con el empleado original).`
                : '.'}
            </Alert>
            <Button onClick={onClose}>Cerrar</Button>
          </Stack>
        )}
      </Box>
    </Dialog>
  )
}
