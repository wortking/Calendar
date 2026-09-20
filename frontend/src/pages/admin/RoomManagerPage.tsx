import { useEffect, useMemo, useRef, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import FullCalendar from '@fullcalendar/react'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import esLocale from '@fullcalendar/core/locales/es'
import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import CircularProgress from '@mui/material/CircularProgress'
import Container from '@mui/material/Container'
import MenuItem from '@mui/material/MenuItem'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import TextField from '@mui/material/TextField'
import Typography from '@mui/material/Typography'
import { listActivityTypes, type ActivityType } from '../../api/activityTypes'
import { listDepartments } from '../../api/admin'
import { listCompanies } from '../../api/companies'
import { listRooms } from '../../api/rooms'
import { listRoomActivities, type RoomActivity } from '../../api/roomActivities'
import { useAuth } from '../../auth/AuthContext'
import { useRequireStaff } from '../../auth/useRequireStaff'
import { Footer } from '../../components/Footer'
import { Header } from '../../components/Header'
import { RoomActivityDialog, type RoomActivityDraft } from '../../components/RoomActivityDialog'
import { RoomActivitiesOfDayPanel } from '../../components/RoomActivitiesOfDayPanel'

// Estructura mínima que necesitamos del "select" de FullCalendar (rango
// elegido arrastrando en el calendario). Se define acá en vez de importar
// el tipo real de @fullcalendar/core por el mismo desfasaje de tipos entre
// ese paquete y @fullcalendar/react que ya afecta a otras props de
// CalendarView (plugins, locale, eventContent, select).
interface SelectedRange {
  startStr: string
  endStr: string
  allDay: boolean
}

interface RoomActivityEventProps {
  activity: RoomActivity
}

function toLocalParts(iso: string): { date: string; time: string } {
  const date = new Date(iso)
  const pad = (n: number) => String(n).padStart(2, '0')

  return {
    date: `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`,
    time: `${pad(date.getHours())}:${pad(date.getMinutes())}`,
  }
}

// Fecha local en formato YYYY-MM-DD, sin pasar por toISOString() (que
// convierte a UTC primero y puede correr la fecha un día si el desfase
// horario local cae del otro lado de medianoche).
function toLocalDateIso(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

function todayLocalIso(): string {
  return toLocalDateIso(new Date())
}

export function RoomManagerPage() {
  useRequireStaff()
  const { token, user } = useAuth()
  const queryClient = useQueryClient()
  const calendarRef = useRef<InstanceType<typeof FullCalendar>>(null)
  const isAdmin = user?.roles.includes('ROLE_ADMIN')

  const [selectedCompanyId, setSelectedCompanyId] = useState('')
  const [selectedRoomId, setSelectedRoomId] = useState('')
  const [activeDate, setActiveDate] = useState(todayLocalIso)
  const [draft, setDraft] = useState<RoomActivityDraft | null>(null)
  const [editingActivity, setEditingActivity] = useState<RoomActivity | null>(null)

  // Admin elige la empresa entre todas; un Coordinador gestiona directamente
  // la suya (no tiene permiso para listar el resto).
  const companiesQuery = useQuery({
    queryKey: ['admin-companies-for-rooms'],
    queryFn: () => listCompanies(token!),
    enabled: !!token && !!isAdmin,
  })

  useEffect(() => {
    if (!isAdmin && user?.company) {
      setSelectedCompanyId(user.company.id)
    }
  }, [isAdmin, user?.company])

  const roomsQuery = useQuery({
    queryKey: ['rooms-for-manager', selectedCompanyId],
    queryFn: () => listRooms(token!, selectedCompanyId),
    enabled: !!token && !!selectedCompanyId,
  })

  const rooms = roomsQuery.data ?? []

  useEffect(() => {
    setSelectedRoomId('')
  }, [selectedCompanyId])

  // La sala hereda el horario de apertura/cierre de su empresa, igual que
  // el calendario principal: se acota la franja visible del calendario a
  // ese rango para todos los roles, en vez de mostrar el día completo.
  const selectedCompany = isAdmin
    ? (companiesQuery.data ?? []).find((company) => company.id === selectedCompanyId)
    : user?.company?.id === selectedCompanyId
      ? user.company
      : undefined

  const { slotMinTime, slotMaxTime } = useMemo(() => {
    if (!selectedCompany) {
      return { slotMinTime: '00:00:00', slotMaxTime: '24:00:00' }
    }

    return {
      slotMinTime: `${selectedCompany.openingTime}:00`,
      slotMaxTime: `${selectedCompany.closingTime}:00`,
    }
  }, [selectedCompany])

  // Sala compartida por todos los departamentos, así que el catálogo de
  // actividades a ofrecer sale de TODOS los departamentos de la empresa, no
  // de uno en particular.
  const departmentsQuery = useQuery({
    queryKey: ['departments-for-rooms'],
    queryFn: () => listDepartments(token!),
    enabled: !!token,
  })

  const companyDepartmentIds = useMemo(
    () => (departmentsQuery.data ?? []).filter((d) => d.companyId === selectedCompanyId).map((d) => d.id),
    [departmentsQuery.data, selectedCompanyId],
  )

  const activityTypesQuery = useQuery({
    queryKey: ['activity-types-for-rooms', companyDepartmentIds],
    queryFn: () => listActivityTypes(token!, companyDepartmentIds),
    enabled: !!token && companyDepartmentIds.length > 0,
  })

  const activityTypes: ActivityType[] = activityTypesQuery.data ?? []

  const { rangeStart, rangeEnd } = useMemo(() => {
    const start = new Date()
    start.setMonth(start.getMonth() - 1)
    const end = new Date()
    end.setMonth(end.getMonth() + 3)

    return { rangeStart: start.toISOString().slice(0, 10), rangeEnd: end.toISOString().slice(0, 10) }
  }, [])

  const activitiesQuery = useQuery({
    queryKey: ['room-activities', selectedRoomId, rangeStart, rangeEnd],
    queryFn: () => listRoomActivities(token!, selectedRoomId, rangeStart, rangeEnd),
    enabled: !!token && !!selectedRoomId,
  })

  function invalidateActivities() {
    queryClient.invalidateQueries({ queryKey: ['room-activities'] })
    queryClient.invalidateQueries({ queryKey: ['room-activities-of-day'] })
  }

  const events = (activitiesQuery.data ?? []).map((activity) => {
    const color = activity.activityTypeColor || '#1976d2'

    return {
      id: activity.id,
      title: activity.activityTypeName ?? 'Actividad',
      start: activity.startAt,
      end: activity.endAt,
      backgroundColor: color,
      borderColor: color,
      extendedProps: { activity } satisfies RoomActivityEventProps,
    }
  })

  const renderEventContent: NonNullable<React.ComponentProps<typeof FullCalendar>['eventContent']> = (eventInfo) => {
    const { activity } = eventInfo.event.extendedProps as RoomActivityEventProps
    const userLabel = activity.userName ?? activity.userEmail

    return (
      <Box
        title={`${activity.activityTypeName ?? ''} — ${userLabel}`}
        sx={{ fontSize: 11, px: 0.5, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}
      >
        {eventInfo.timeText} {eventInfo.event.title} · {userLabel}
      </Box>
    )
  }

  // En vista de mes la selección es de días completos (sin horario), así
  // que no alcanza para agendar una actividad puntual: se ignora ahí y solo
  // se usa en día/semana, donde arrastrar sí define un horario concreto.
  const handleSelect: NonNullable<React.ComponentProps<typeof FullCalendar>['select']> = (info: SelectedRange) => {
    if (info.allDay) {
      return
    }

    const startParts = toLocalParts(info.startStr)
    const endParts = toLocalParts(info.endStr)
    setEditingActivity(null)
    setDraft({ date: startParts.date, start: startParts.time, end: endParts.time })
  }

  // Click en el número de un día desde la vista de mes: salta directo a la
  // vista de día de esa fecha (ahí sí se puede arrastrar para agendar).
  function handleNavLinkDayClick(date: Date) {
    calendarRef.current?.getApi().changeView('timeGridDay', date)
  }

  function openEditActivity(activity: RoomActivity) {
    setDraft(null)
    setEditingActivity(activity)
  }

  const handleEventClick: NonNullable<React.ComponentProps<typeof FullCalendar>['eventClick']> = (info) => {
    const { activity } = info.event.extendedProps as RoomActivityEventProps
    openEditActivity(activity)
  }

  // "Actividades del día" no sigue al calendario al pasar a semana/mes (solo
  // se actualiza al navegar en vista de día), mismo criterio que "Turnos
  // del día" en el calendario principal.
  const handleDatesSet: NonNullable<React.ComponentProps<typeof FullCalendar>['datesSet']> = (info) => {
    if (info.view.type === 'timeGridDay') {
      setActiveDate(toLocalDateIso(info.view.currentStart))
    }
  }

  const isDialogOpen = !!draft || !!editingActivity

  return (
    <Box sx={{ minHeight: '100svh', display: 'flex', flexDirection: 'column', bgcolor: 'background.default' }}>
      <Header />

      <Container maxWidth="xl" sx={{ py: 3, flexGrow: 1 }}>
        <Typography variant="h5" sx={{ fontWeight: 700, mb: 2 }}>
          Gestor de salas
        </Typography>

        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} sx={{ mb: 3 }}>
          {isAdmin && (
            <TextField
              select
              label="Empresa"
              value={selectedCompanyId}
              onChange={(e) => setSelectedCompanyId(e.target.value)}
              sx={{ minWidth: 240 }}
            >
              {(companiesQuery.data ?? []).map((company) => (
                <MenuItem key={company.id} value={company.id}>
                  {company.name}
                </MenuItem>
              ))}
            </TextField>
          )}

          <TextField
            select
            label="Sala"
            value={selectedRoomId}
            onChange={(e) => setSelectedRoomId(e.target.value)}
            disabled={!selectedCompanyId}
            sx={{ minWidth: 240 }}
          >
            {rooms.map((room) => (
              <MenuItem key={room.id} value={room.id}>
                {room.name}
              </MenuItem>
            ))}
          </TextField>
        </Stack>

        {!selectedCompanyId && (
          <Alert severity="info">Elegí una empresa para ver sus salas.</Alert>
        )}

        {selectedCompanyId && rooms.length === 0 && !roomsQuery.isLoading && (
          <Alert severity="info">Esta empresa todavía no tiene salas. Podés crearlas desde "Empresas".</Alert>
        )}

        {selectedCompanyId && !selectedRoomId && rooms.length > 0 && (
          <Alert severity="info">Elegí una sala para ver y agendar sus actividades.</Alert>
        )}

        {selectedRoomId && activityTypes.length === 0 && !activityTypesQuery.isLoading && (
          <Alert severity="warning" sx={{ mb: 2 }}>
            Esta empresa todavía no tiene tipos de actividad. Creá al menos uno desde "Empresas" antes de agendar.
          </Alert>
        )}

        {selectedRoomId && (
          <Stack direction={{ xs: 'column', md: 'row' }} spacing={2}>
            <Paper elevation={0} variant="outlined" sx={{ p: { xs: 1, sm: 2 }, borderRadius: 3, flexGrow: 1, minWidth: 0 }}>
              {activitiesQuery.isLoading ? (
                <Stack sx={{ py: 6, alignItems: 'center' }}>
                  <CircularProgress size={28} />
                </Stack>
              ) : (
                <FullCalendar
                  ref={calendarRef}
                  plugins={[timeGridPlugin, dayGridPlugin, interactionPlugin]}
                  initialView="timeGridDay"
                  locale={esLocale}
                  headerToolbar={{ left: 'prev,next today', center: 'title', right: 'timeGridDay,timeGridWeek,dayGridMonth' }}
                  slotLabelFormat={{ hour: '2-digit', minute: '2-digit', hour12: false }}
                  eventTimeFormat={{ hour: '2-digit', minute: '2-digit', hour12: false }}
                  slotMinTime={slotMinTime}
                  slotMaxTime={slotMaxTime}
                  allDaySlot={false}
                  height="auto"
                  datesSet={handleDatesSet}
                  navLinks
                  navLinkDayClick={handleNavLinkDayClick}
                  selectable
                  select={handleSelect}
                  events={events}
                  eventContent={renderEventContent}
                  eventClick={handleEventClick}
                />
              )}
            </Paper>

            <Box sx={{ width: { xs: '100%', md: 320 }, flexShrink: 0 }}>
              <RoomActivitiesOfDayPanel roomId={selectedRoomId} date={activeDate} onEdit={openEditActivity} />
            </Box>
          </Stack>
        )}
      </Container>

      <Footer />

      {selectedRoomId && (
        <RoomActivityDialog
          open={isDialogOpen}
          onClose={() => {
            setDraft(null)
            setEditingActivity(null)
          }}
          onSaved={invalidateActivities}
          roomId={selectedRoomId}
          activityTypes={activityTypes}
          editingActivity={editingActivity}
          draft={draft}
        />
      )}
    </Box>
  )
}
