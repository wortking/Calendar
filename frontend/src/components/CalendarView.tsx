import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import esLocale from '@fullcalendar/core/locales/es'
import Alert from '@mui/material/Alert'
import Avatar from '@mui/material/Avatar'
import Box from '@mui/material/Box'
import Paper from '@mui/material/Paper'
import Snackbar from '@mui/material/Snackbar'
import Stack from '@mui/material/Stack'
import { useTheme } from '@mui/material/styles'
import useMediaQuery from '@mui/material/useMediaQuery'
import { updateShift, listDepartmentShifts, type ShiftWithUser } from '../api/shifts'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/AuthContext'
import { CreateShiftDialog, type CalendarSlot } from './CreateShiftDialog'
import { EditShiftDialog } from './EditShiftDialog'

interface ShiftEventActivity {
  id: string
  name: string
  color: string | null
  time: string
}

interface ShiftEventProps {
  shiftUserId: string
  avatarUrl: string | null
  initial: string
  fullName: string
  activities: ShiftEventActivity[]
}

// Estructura mínima que necesitamos de un EventApi de FullCalendar (id,
// horario y nuestros extendedProps). Se define acá en vez de importar el
// tipo real de @fullcalendar/core para no chocar con el mismo desfasaje de
// tipos entre ese paquete y @fullcalendar/react que ya afecta a otras props
// de este componente (plugins, locale, eventContent).
interface DraggedShiftEvent {
  id: string
  startStr: string
  endStr: string
  extendedProps: Record<string, unknown>
}

// Cada nivel de zoom reduce la duración de la franja (más resolución). El
// nivel 0 ("día completo") usa franjas de 1 hora: con solo N filas el rango
// entero entra sin scroll (a diferencia de forzar una altura de fila por CSS,
// esto no choca con el alto mínimo que impone el propio texto de la
// etiqueta). Desde ahí, cada "+" acerca el detalle hasta llegar a los 5
// minutos, activando el scroll centrado en la hora actual.
const ZOOM_LEVELS = [
  { slotDuration: '01:00:00', slotLabelInterval: '01:00:00' },
  { slotDuration: '00:30:00', slotLabelInterval: '01:00:00' },
  { slotDuration: '00:15:00', slotLabelInterval: '00:30:00' },
  { slotDuration: '00:05:00', slotLabelInterval: '00:15:00' },
] as const

const MAX_ZOOM = ZOOM_LEVELS.length - 1

// Fecha local en formato YYYY-MM-DD. No usa toISOString() porque convierte a
// UTC primero, lo que puede correr la fecha un día si el desfase horario
// local cae del otro lado de medianoche.
function toLocalDateIso(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatTime(iso: string): string {
  const date = new Date(iso)
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function hourOf(time: string, fallback: number): number {
  const hour = parseInt(time.split(':')[0] ?? '', 10)
  return Number.isNaN(hour) ? fallback : hour
}

export function CalendarView({ onActiveDateChange }: { onActiveDateChange?: (dateIso: string) => void }) {
  const theme = useTheme()
  // En escritorio el Paper recibe una altura acotada (100% del layout de
  // DashboardPage) para que sea el propio FullCalendar el que scrollee
  // internamente. En mobile no hay altura fija disponible, así que se
  // mantiene el comportamiento de siempre (altura según contenido).
  const isDesktop = useMediaQuery(theme.breakpoints.up('md'))
  const calendarRef = useRef<InstanceType<typeof FullCalendar>>(null)
  const { token, user } = useAuth()
  const queryClient = useQueryClient()
  const [dragError, setDragError] = useState<string | null>(null)
  const [editingShift, setEditingShift] = useState<ShiftWithUser | null>(null)
  const [creatingSlot, setCreatingSlot] = useState<CalendarSlot | null>(null)
  const lastClickRef = useRef<{ id: string; time: number } | null>(null)

  // Solo Admin/Coordinador pueden arrastrar o redimensionar un turno para
  // reprogramarlo, o doble-clickearlo para editarlo; un Empleado ve el
  // calendario en modo solo lectura.
  const canEditShifts = user?.roles.includes('ROLE_ADMIN') || user?.roles.includes('ROLE_COORDINADOR')

  // Si el usuario pertenece a una empresa, el calendario se acota a su
  // horario de apertura/cierre (los turnos ya están validados server-side
  // para no salirse de ese rango). Sin empresa, se muestra el día completo.
  const { slotMinTime, slotMaxTime, slotMinHour, slotMaxHour } = useMemo(() => {
    const company = user?.company

    if (!company) {
      return { slotMinTime: '00:00:00', slotMaxTime: '24:00:00', slotMinHour: 0, slotMaxHour: 24 }
    }

    return {
      slotMinTime: `${company.openingTime}:00`,
      slotMaxTime: `${company.closingTime}:00`,
      slotMinHour: hourOf(company.openingTime, 0),
      slotMaxHour: hourOf(company.closingTime, 24),
    }
  }, [user?.company])

  // Scrollea el timeGrid a una hora antes de la actual (acotada al rango
  // visible de slots) para que la franja de "ahora" quede a la vista sin
  // tener que bajar manualmente, en vez de abrir siempre arriba del todo.
  const currentScrollTime = useCallback(() => {
    const hour = Math.min(slotMaxHour, Math.max(slotMinHour, new Date().getHours() - 1))

    return `${String(hour).padStart(2, '0')}:00:00`
  }, [slotMinHour, slotMaxHour])

  const [zoom, setZoom] = useState(0)
  const isFitToDay = zoom === 0
  const { slotDuration, slotLabelInterval } = ZOOM_LEVELS[zoom]

  useEffect(() => {
    if (zoom > 0) {
      calendarRef.current?.getApi().scrollToTime(currentScrollTime())
    }
  }, [zoom, currentScrollTime])

  // Rango fijo (hoy −1 mes a hoy +3 meses) traído una sola vez al montar: sin
  // refetch dinámico al navegar el calendario todavía, alcanza para ver los
  // turnos asignados alrededor de la fecha actual.
  const { rangeStart, rangeEnd } = useMemo(() => {
    const start = new Date()
    start.setMonth(start.getMonth() - 1)
    const end = new Date()
    end.setMonth(end.getMonth() + 3)

    return { rangeStart: start.toISOString().slice(0, 10), rangeEnd: end.toISOString().slice(0, 10) }
  }, [])

  // Turnos de todos los compañeros que comparten departamento con el usuario
  // logueado (el backend cae a mostrar solo los propios si no tiene
  // departamento asignado).
  const shiftsQuery = useQuery({
    queryKey: ['department-shifts', rangeStart, rangeEnd],
    queryFn: () => listDepartmentShifts(token!, rangeStart, rangeEnd),
    enabled: !!token,
  })

  const events = (shiftsQuery.data ?? []).map((shift) => {
    const isMine = shift.userId === user?.id
    // Verde suave si el turno ya terminó (propio o no). Si todavía está
    // pendiente: violeta si es propio, amarillo claro si es de un
    // compañero. MUI no trae un tono amarillo en la paleta por defecto, así
    // que va fijo.
    const isCompleted = new Date(shift.endAt) <= new Date()
    const color = isCompleted ? theme.palette.success.light : isMine ? 'rgba(152, 22, 139, 0.5)' : 'rgba(235, 201, 0, 0.5)'
    const fullName = isMine ? 'Yo' : (shift.userName ?? shift.userEmail)
    const activities: ShiftEventActivity[] = shift.roomActivities
      .filter((a) => a.activityTypeName)
      .map((a) => ({
        id: a.id,
        name: a.activityTypeName as string,
        color: a.activityTypeColor,
        time: `${formatTime(a.startAt)}–${formatTime(a.endAt)}`,
      }))

    return {
      id: shift.id,
      title: fullName,
      start: shift.startAt,
      end: shift.endAt,
      backgroundColor: color,
      borderColor: color,
      extendedProps: {
        shiftUserId: shift.userId,
        avatarUrl: shift.avatarUrl,
        initial: fullName.charAt(0).toUpperCase(),
        fullName,
        activities,
      } satisfies ShiftEventProps,
    }
  })

  // Reemplaza el nombre por el avatar del empleado (con sus iniciales como
  // respaldo si no tiene foto), y suma nombre y apellido junto con la hora y
  // la actividad; el texto completo también queda como tooltip nativo del
  // navegador para cuando el bloque es angosto y se corta con "…".
  //
  // Tipado como prop de <FullCalendar> (en vez de anotar el parámetro con
  // EventContentArg de @fullcalendar/core) para sortear el mismo desfasaje
  // de tipos entre @fullcalendar/react y @fullcalendar/core que ya afecta a
  // "plugins"/"locale" más abajo: son builds distintos de los mismos tipos.
  const renderEventContent: NonNullable<React.ComponentProps<typeof FullCalendar>['eventContent']> = (eventInfo) => {
    const { avatarUrl, initial, fullName, activities } = eventInfo.event.extendedProps as ShiftEventProps
    const tooltip = [
      `${fullName}${eventInfo.timeText ? ` · ${eventInfo.timeText}` : ''}`,
      ...activities.map((a) => `${a.time} ${a.name}`),
    ].join('\n')

    return (
      <Stack title={tooltip} sx={{ overflow: 'hidden', width: '100%', height: '100%', px: 0.5, py: 0.25 }}>
        <Stack direction="row" spacing={0.5} sx={{ alignItems: 'center', flexShrink: 0 }}>
          <Avatar src={avatarUrl ?? undefined} sx={{ width: 18, height: 18, fontSize: 10, flexShrink: 0 }}>
            {initial}
          </Avatar>
          <Box
            component="span"
            sx={{ fontSize: 11, fontWeight: 600, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}
          >
            {fullName}
            {eventInfo.timeText ? ` · ${eventInfo.timeText}` : ''}
          </Box>
        </Stack>

        {activities.length > 0 && (
          <Stack sx={{ overflow: 'hidden', mt: 0.25 }}>
            {activities.map((activity) => (
              <Stack key={activity.id} direction="row" spacing={0.5} sx={{ alignItems: 'center' }}>
                <Box
                  sx={{
                    width: 6,
                    height: 6,
                    borderRadius: '50%',
                    bgcolor: activity.color || 'grey.300',
                    flexShrink: 0,
                  }}
                />
                <Box
                  component="span"
                  sx={{ fontSize: 10, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}
                >
                  {activity.time} {activity.name}
                </Box>
              </Stack>
            ))}
          </Stack>
        )}
      </Stack>
    )
  }

  // Arrastrar (mover de horario) o redimensionar (cambiar duración) un
  // turno dispara el mismo update: se manda el nuevo horario tal cual lo
  // deja FullCalendar (startStr/endStr, en hora local sin conversión — el
  // mismo formato "naive" que ya usa el resto de la app para horarios), sin
  // tocar usuario ni actividad. Si el backend lo rechaza (p. ej. se sale del
  // horario de la empresa), se revierte visualmente y se avisa el motivo.
  function invalidateShiftQueries() {
    queryClient.invalidateQueries({ queryKey: ['department-shifts'] })
    queryClient.invalidateQueries({ queryKey: ['shifts-by-date'] })
    queryClient.invalidateQueries({ queryKey: ['my-shifts-month'] })
  }

  async function applyShiftReschedule(event: DraggedShiftEvent, revert: () => void) {
    const { shiftUserId } = event.extendedProps as unknown as ShiftEventProps

    try {
      await updateShift(token!, event.id, shiftUserId, event.startStr, event.endStr)
      invalidateShiftQueries()
    } catch (err) {
      revert()
      setDragError(err instanceof ApiError ? err.message : 'No se pudo mover el turno.')
    }
  }

  const handleEventDrop: NonNullable<React.ComponentProps<typeof FullCalendar>['eventDrop']> = (info) => {
    void applyShiftReschedule(info.event, info.revert)
  }

  const handleEventResize: NonNullable<React.ComponentProps<typeof FullCalendar>['eventResize']> = (info) => {
    void applyShiftReschedule(info.event, info.revert)
  }

  // Un solo click no hace nada; dos clicks seguidos sobre el mismo turno
  // (dentro de 350ms) abren el editor. No hay un "eventDoubleClick" nativo
  // en FullCalendar, así que se detecta a mano comparando contra el click
  // anterior en vez de atar un listener nativo al DOM del evento.
  const handleEventClick: NonNullable<React.ComponentProps<typeof FullCalendar>['eventClick']> = (info) => {
    if (!canEditShifts) {
      return
    }

    const now = Date.now()
    const last = lastClickRef.current
    lastClickRef.current = { id: info.event.id, time: now }

    if (last && last.id === info.event.id && now - last.time < 350) {
      lastClickRef.current = null
      const shift = (shiftsQuery.data ?? []).find((s) => s.id === info.event.id)
      if (shift) {
        setEditingShift(shift)
      }
    }
  }

  // Arrastrar sobre una franja libre (o un solo click, que selecciona una
  // franja de la duración del zoom actual) abre el diálogo de "nuevo turno"
  // con esa fecha/horario ya cargados. Se deja la selección visible
  // (unselectAuto=false) mientras el diálogo está abierto, y se limpia a
  // mano al cerrarlo.
  const handleSelect: NonNullable<React.ComponentProps<typeof FullCalendar>['select']> = (info) => {
    if (!canEditShifts) {
      return
    }

    setCreatingSlot({ startStr: info.startStr, endStr: info.endStr })
  }

  function closeCreateDialog() {
    setCreatingSlot(null)
    calendarRef.current?.getApi().unselect()
  }

  // Ver el comentario de handleEventDrop más arriba: se tipa desde las
  // props del propio <FullCalendar> para sortear el desfasaje de tipos
  // entre @fullcalendar/react y @fullcalendar/core.
  const handleDatesSet: NonNullable<React.ComponentProps<typeof FullCalendar>['datesSet']> = (info) => {
    // Solo en vista de día el rango visible ES el día que interesa. Al pasar
    // a semana/mes, currentStart cae en el lunes o el día 1 del mes: seguir
    // ese valor haría que "Turnos del día" saltara a esa fecha en vez de
    // quedarse en el día que se estaba mirando.
    if (info.view.type === 'timeGridDay') {
      onActiveDateChange?.(toLocalDateIso(info.view.currentStart))
    }
  }

  return (
    <Paper
      elevation={0}
      variant="outlined"
      sx={{
        p: { xs: 1, sm: 2 },
        borderRadius: 3,
        height: { md: '100%' },
        overflow: 'hidden',
      }}
    >
      <FullCalendar
        ref={calendarRef}
        plugins={[timeGridPlugin, dayGridPlugin, interactionPlugin]}
        initialView="timeGridDay"
        initialDate={new Date()}
        locale={esLocale}
        customButtons={{
          zoomOut: {
            text: '−',
            click: () => setZoom((current) => Math.max(0, current - 1)),
          },
          zoomIn: {
            text: '+',
            click: () => setZoom((current) => Math.min(MAX_ZOOM, current + 1)),
          },
        }}
        headerToolbar={{
          left: 'prev,next today',
          center: 'title',
          right: 'zoomOut,zoomIn timeGridDay,timeGridWeek,dayGridMonth',
        }}
        datesSet={handleDatesSet}
        slotMinTime={slotMinTime}
        slotMaxTime={slotMaxTime}
        slotDuration={slotDuration}
        slotLabelInterval={slotLabelInterval}
        slotLabelFormat={{ hour: '2-digit', minute: '2-digit', hour12: false }}
        eventTimeFormat={{ hour: '2-digit', minute: '2-digit', hour12: false }}
        scrollTime={currentScrollTime()}
        allDaySlot={false}
        nowIndicator
        height={isDesktop ? '100%' : 'auto'}
        expandRows={isFitToDay}
        events={events}
        eventContent={renderEventContent}
        editable={canEditShifts}
        selectable={canEditShifts}
        unselectAuto={false}
        select={handleSelect}
        eventDrop={handleEventDrop}
        eventResize={handleEventResize}
        eventClick={handleEventClick}
      />

      <Snackbar open={!!dragError} autoHideDuration={5000} onClose={() => setDragError(null)}>
        <Alert severity="error" onClose={() => setDragError(null)} sx={{ width: '100%' }}>
          {dragError}
        </Alert>
      </Snackbar>

      <EditShiftDialog
        shift={editingShift}
        onClose={() => setEditingShift(null)}
        onSaved={invalidateShiftQueries}
      />

      <CreateShiftDialog
        slot={creatingSlot}
        onClose={closeCreateDialog}
        onSaved={invalidateShiftQueries}
      />
    </Paper>
  )
}
