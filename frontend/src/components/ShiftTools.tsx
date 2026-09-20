import { useRef, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import Button from '@mui/material/Button'
import Checkbox from '@mui/material/Checkbox'
import CircularProgress from '@mui/material/CircularProgress'
import Dialog from '@mui/material/Dialog'
import DialogActions from '@mui/material/DialogActions'
import DialogContent from '@mui/material/DialogContent'
import DialogContentText from '@mui/material/DialogContentText'
import DialogTitle from '@mui/material/DialogTitle'
import FormControlLabel from '@mui/material/FormControlLabel'
import List from '@mui/material/List'
import ListItem from '@mui/material/ListItem'
import ListItemText from '@mui/material/ListItemText'
import Stack from '@mui/material/Stack'
import {
  copyPreviousWeekShifts,
  downloadShiftsImportTemplate,
  importShifts,
  type CopyWeekResult,
  type ImportShiftsResult,
} from '../api/shifts'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/AuthContext'

function startOfWeek(date: Date): Date {
  const start = new Date(date)
  const day = start.getDay()
  const diffToMonday = (day === 0 ? -6 : 1) - day

  start.setDate(start.getDate() + diffToMonday)
  start.setHours(0, 0, 0, 0)

  return start
}

function toLocalDateIso(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatDayLabel(date: Date): string {
  return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

function invalidateShiftQueries(queryClient: ReturnType<typeof useQueryClient>) {
  queryClient.invalidateQueries({ queryKey: ['department-shifts'] })
  queryClient.invalidateQueries({ queryKey: ['shifts-by-date'] })
  queryClient.invalidateQueries({ queryKey: ['my-shifts-month'] })
}

// Botones de Admin/Coordinador para copiar la semana anterior y para
// importar turnos desde un Excel/CSV, con sus diálogos de confirmación y
// resultado. Vive en el dashboard, al lado del calendario.
export function ShiftTools({ activeDate }: { activeDate: string }) {
  const { token } = useAuth()
  const queryClient = useQueryClient()
  const fileInputRef = useRef<HTMLInputElement>(null)

  const [isCopyDialogOpen, setIsCopyDialogOpen] = useState(false)
  const [copyError, setCopyError] = useState<string | null>(null)
  const [copyResult, setCopyResult] = useState<CopyWeekResult | null>(null)
  const [copyIncludeActivities, setCopyIncludeActivities] = useState(false)

  const [isImportDialogOpen, setIsImportDialogOpen] = useState(false)
  const [importError, setImportError] = useState<string | null>(null)
  const [importResult, setImportResult] = useState<ImportShiftsResult | null>(null)
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [isDownloadingTemplate, setIsDownloadingTemplate] = useState(false)

  const weekStart = startOfWeek(new Date(`${activeDate}T00:00:00`))
  const weekEnd = new Date(weekStart)
  weekEnd.setDate(weekEnd.getDate() + 6)
  const sourceWeekStart = new Date(weekStart)
  sourceWeekStart.setDate(sourceWeekStart.getDate() - 7)
  const sourceWeekEnd = new Date(weekStart)
  sourceWeekEnd.setDate(sourceWeekEnd.getDate() - 1)

  const copyMutation = useMutation({
    mutationFn: () => copyPreviousWeekShifts(token!, toLocalDateIso(weekStart), copyIncludeActivities),
    onSuccess: (result) => {
      setCopyResult(result)
      invalidateShiftQueries(queryClient)
    },
    onError: (err: unknown) => {
      setCopyError(err instanceof ApiError ? err.message : 'No se pudo copiar la semana.')
    },
  })

  const importMutation = useMutation({
    mutationFn: () => importShifts(token!, selectedFile!),
    onSuccess: (result) => {
      setImportResult(result)
      invalidateShiftQueries(queryClient)
    },
    onError: (err: unknown) => {
      setImportError(err instanceof ApiError ? err.message : 'No se pudo importar el archivo.')
    },
  })

  function openCopyDialog() {
    setCopyError(null)
    setCopyResult(null)
    setCopyIncludeActivities(false)
    setIsCopyDialogOpen(true)
  }

  function openImportDialog() {
    setImportError(null)
    setImportResult(null)
    setSelectedFile(null)
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
    setIsImportDialogOpen(true)
  }

  async function handleDownloadTemplate() {
    setIsDownloadingTemplate(true)
    try {
      const blob = await downloadShiftsImportTemplate(token!)
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = 'plantilla_turnos.xlsx'
      document.body.appendChild(link)
      link.click()
      link.remove()
      URL.revokeObjectURL(url)
    } catch {
      setImportError('No se pudo descargar la plantilla.')
    } finally {
      setIsDownloadingTemplate(false)
    }
  }

  return (
    <>
      <Stack direction="row" spacing={1}>
        <Button size="small" variant="outlined" onClick={openCopyDialog}>
          Copiar semana anterior
        </Button>
        <Button size="small" variant="outlined" onClick={openImportDialog}>
          Importar turnos
        </Button>
      </Stack>

      <Dialog open={isCopyDialogOpen} onClose={() => setIsCopyDialogOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>Copiar semana anterior</DialogTitle>
        <DialogContent>
          {!copyResult && (
            <Stack spacing={1.5}>
              <DialogContentText>
                Se copiarán los turnos de la semana del <strong>{formatDayLabel(sourceWeekStart)}</strong> al{' '}
                <strong>{formatDayLabel(sourceWeekEnd)}</strong> a la semana del <strong>{formatDayLabel(weekStart)}</strong>{' '}
                al <strong>{formatDayLabel(weekEnd)}</strong>. Los turnos que ya existan en el destino no se duplican.
              </DialogContentText>
              <FormControlLabel
                control={
                  <Checkbox
                    checked={copyIncludeActivities}
                    onChange={(e) => setCopyIncludeActivities(e.target.checked)}
                  />
                }
                label="Copiar también las actividades de sala de esos días"
              />
            </Stack>
          )}

          {copyResult && (
            <Alert severity="success" sx={{ mt: 1 }}>
              Se copiaron {copyResult.copied} turno{copyResult.copied === 1 ? '' : 's'}
              {copyResult.skipped > 0 ? ` (${copyResult.skipped} ya existían y se omitieron)` : ''}.
              {copyIncludeActivities && (
                <>
                  {' '}
                  Se copiaron {copyResult.activitiesCopied} actividad{copyResult.activitiesCopied === 1 ? '' : 'es'} de sala
                  {copyResult.activitiesSkipped > 0
                    ? ` (${copyResult.activitiesSkipped} se omitieron por conflicto o falta de turno)`
                    : ''}
                  .
                </>
              )}
            </Alert>
          )}

          {copyError && (
            <Alert severity="error" sx={{ mt: 1 }} onClose={() => setCopyError(null)}>
              {copyError}
            </Alert>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setIsCopyDialogOpen(false)}>{copyResult ? 'Cerrar' : 'Cancelar'}</Button>
          {!copyResult && (
            <Button variant="contained" onClick={() => copyMutation.mutate()} disabled={copyMutation.isPending}>
              {copyMutation.isPending ? 'Copiando…' : 'Copiar'}
            </Button>
          )}
        </DialogActions>
      </Dialog>

      <Dialog open={isImportDialogOpen} onClose={() => setIsImportDialogOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>Importar turnos</DialogTitle>
        <DialogContent>
          <Stack spacing={2}>
            <DialogContentText>
              Subí un archivo .xlsx/.xls/.csv con las columnas Email, Fecha, Hora inicio y Hora fin.
            </DialogContentText>

            <Button variant="text" size="small" onClick={handleDownloadTemplate} disabled={isDownloadingTemplate} sx={{ alignSelf: 'flex-start' }}>
              {isDownloadingTemplate ? 'Descargando…' : 'Descargar plantilla'}
            </Button>

            <Box>
              <input
                ref={fileInputRef}
                type="file"
                accept=".xlsx,.xls,.csv"
                onChange={(e) => setSelectedFile(e.target.files?.[0] ?? null)}
              />
            </Box>

            {importMutation.isPending && (
              <Stack sx={{ alignItems: 'center', py: 2 }}>
                <CircularProgress size={24} />
              </Stack>
            )}

            {importResult && (
              <Stack spacing={1}>
                <Alert severity={importResult.errors.length > 0 ? 'warning' : 'success'}>
                  Se crearon {importResult.created} turno{importResult.created === 1 ? '' : 's'}
                  {importResult.errors.length > 0 ? `, con ${importResult.errors.length} fila(s) con error.` : '.'}
                </Alert>
                {importResult.errors.length > 0 && (
                  <List dense sx={{ maxHeight: 200, overflow: 'auto' }}>
                    {importResult.errors.map((rowError, index) => (
                      <ListItem key={index} disablePadding>
                        <ListItemText
                          primary={`Fila ${rowError.row}`}
                          secondary={rowError.message}
                          slotProps={{ primary: { variant: 'body2', sx: { fontWeight: 600 } }, secondary: { variant: 'caption' } }}
                        />
                      </ListItem>
                    ))}
                  </List>
                )}
              </Stack>
            )}

            {importError && (
              <Alert severity="error" onClose={() => setImportError(null)}>
                {importError}
              </Alert>
            )}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setIsImportDialogOpen(false)}>{importResult ? 'Cerrar' : 'Cancelar'}</Button>
          {!importResult && (
            <Button
              variant="contained"
              onClick={() => importMutation.mutate()}
              disabled={!selectedFile || importMutation.isPending}
            >
              {importMutation.isPending ? 'Importando…' : 'Importar'}
            </Button>
          )}
        </DialogActions>
      </Dialog>
    </>
  )
}
