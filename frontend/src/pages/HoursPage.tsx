import { useMemo, useState, type ReactNode } from 'react'
import { useQuery } from '@tanstack/react-query'
import Box from '@mui/material/Box'
import CircularProgress from '@mui/material/CircularProgress'
import Container from '@mui/material/Container'
import Divider from '@mui/material/Divider'
import IconButton from '@mui/material/IconButton'
import MenuItem from '@mui/material/MenuItem'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import Table from '@mui/material/Table'
import TableBody from '@mui/material/TableBody'
import TableCell from '@mui/material/TableCell'
import TableContainer from '@mui/material/TableContainer'
import TableHead from '@mui/material/TableHead'
import TableRow from '@mui/material/TableRow'
import TextField from '@mui/material/TextField'
import Typography from '@mui/material/Typography'
import { useTheme } from '@mui/material/styles'
import useMediaQuery from '@mui/material/useMediaQuery'
import { listUsers } from '../api/admin'
import { listMyShifts, listShiftsForUser, type Shift } from '../api/shifts'
import { useAuth } from '../auth/AuthContext'
import { useRequireAuth } from '../auth/useRequireAuth'
import { Footer } from '../components/Footer'
import { Header } from '../components/Header'

function toLocalDateIso(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function startOfWeek(date: Date): Date {
  const start = new Date(date)
  const day = start.getDay()
  const diffToMonday = (day === 0 ? -6 : 1) - day

  start.setDate(start.getDate() + diffToMonday)
  start.setHours(0, 0, 0, 0)

  return start
}

function formatHours(hours: number): string {
  const totalMinutes = Math.round(hours * 60)
  const h = Math.floor(totalMinutes / 60)
  const m = totalMinutes % 60

  if (h === 0) {
    return `${m} min`
  }

  return m === 0 ? `${h} h` : `${h} h ${m} min`
}

function formatCount(count: number): string {
  return `${count}`
}

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false })
}

function formatDayLabel(dateIso: string): string {
  return new Date(`${dateIso}T00:00:00`).toLocaleDateString('es-ES', {
    weekday: 'long',
    day: 'numeric',
    month: 'short',
  })
}

function formatWeekLabel(weekStartIso: string, weekEndIso: string): string {
  const start = new Date(`${weekStartIso}T00:00:00`)
  const end = new Date(`${weekEndIso}T00:00:00`)
  const startLabel = start.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
  const endLabel = end.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })

  return `${startLabel} – ${endLabel}`
}

interface DaySummary {
  date: string
  hours: number
  shifts: Shift[]
}

interface WeekSummary {
  weekStart: string
  weekEnd: string
  hours: number
}

// Las horas trabajadas no son un dato propio, se derivan de los turnos ya
// asignados (endAt - startAt), agrupados por día y por semana (lunes a
// domingo). Se asume que un turno no cruza medianoche, igual que en el resto
// de la app.
function summarizeShifts(shifts: Shift[]): { totalHours: number; days: DaySummary[]; weeks: WeekSummary[] } {
  let totalHours = 0
  const dayMap = new Map<string, DaySummary>()
  const weekHours = new Map<string, number>()

  for (const shift of shifts) {
    const start = new Date(shift.startAt)
    const end = new Date(shift.endAt)
    const hours = (end.getTime() - start.getTime()) / 3_600_000
    totalHours += hours

    const dayKey = toLocalDateIso(start)
    const day = dayMap.get(dayKey) ?? { date: dayKey, hours: 0, shifts: [] }
    day.hours += hours
    day.shifts.push(shift)
    dayMap.set(dayKey, day)

    const weekKey = toLocalDateIso(startOfWeek(start))
    weekHours.set(weekKey, (weekHours.get(weekKey) ?? 0) + hours)
  }

  const days = Array.from(dayMap.values()).sort((a, b) => a.date.localeCompare(b.date))
  for (const day of days) {
    day.shifts.sort((a, b) => a.startAt.localeCompare(b.startAt))
  }

  const weeks = Array.from(weekHours.entries())
    .map(([weekStart, hours]) => {
      const weekEndDate = new Date(`${weekStart}T00:00:00`)
      weekEndDate.setDate(weekEndDate.getDate() + 6)

      return { weekStart, weekEnd: toLocalDateIso(weekEndDate), hours }
    })
    .sort((a, b) => a.weekStart.localeCompare(b.weekStart))

  return { totalHours, days, weeks }
}

interface DayActivityCount {
  date: string
  count: number
}

interface WeekActivityCount {
  weekStart: string
  weekEnd: string
  count: number
}

// El contador de actividades sale de las actividades de sala anidadas en
// cada turno (ya vienen incluidas en la respuesta de turnos), agrupadas
// por día y por semana igual que las horas.
function summarizeActivityCounts(shifts: Shift[]): { totalCount: number; days: DayActivityCount[]; weeks: WeekActivityCount[] } {
  let totalCount = 0
  const dayMap = new Map<string, number>()
  const weekMap = new Map<string, number>()

  for (const shift of shifts) {
    for (const activity of shift.roomActivities) {
      totalCount += 1

      const start = new Date(activity.startAt)
      const dayKey = toLocalDateIso(start)
      dayMap.set(dayKey, (dayMap.get(dayKey) ?? 0) + 1)

      const weekKey = toLocalDateIso(startOfWeek(start))
      weekMap.set(weekKey, (weekMap.get(weekKey) ?? 0) + 1)
    }
  }

  const days = Array.from(dayMap.entries())
    .map(([date, count]) => ({ date, count }))
    .sort((a, b) => a.date.localeCompare(b.date))

  const weeks = Array.from(weekMap.entries())
    .map(([weekStart, count]) => {
      const weekEndDate = new Date(`${weekStart}T00:00:00`)
      weekEndDate.setDate(weekEndDate.getDate() + 6)

      return { weekStart, weekEnd: toLocalDateIso(weekEndDate), count }
    })
    .sort((a, b) => a.weekStart.localeCompare(b.weekStart))

  return { totalCount, days, weeks }
}

// Sección "por día / por semana / por mes" reutilizada tanto para horas
// (con su detalle de horario por turno) como para el contador de
// actividades (solo la cantidad); el orden de las tres subsecciones es
// siempre día, semana y mes al final.
function SummarySection({
  title,
  totalLabel,
  totalValue,
  formatValue,
  days,
  weeks,
  emptyMessage,
  renderDayDetail,
}: {
  title: string
  totalLabel: string
  totalValue: number
  formatValue: (value: number) => string
  days: Array<{ date: string; value: number }>
  weeks: Array<{ weekStart: string; weekEnd: string; value: number }>
  emptyMessage: string
  renderDayDetail?: (dateIso: string) => ReactNode
}) {
  return (
    <Stack spacing={2}>
      <Typography variant="h6" sx={{ fontWeight: 700 }}>
        {title}
      </Typography>

      <Paper elevation={0} variant="outlined" sx={{ borderRadius: 3, p: 2 }}>
        <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
          Por día
        </Typography>
        {days.length === 0 ? (
          <Typography variant="body2" color="text.secondary">
            {emptyMessage}
          </Typography>
        ) : (
          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell>Día</TableCell>
                  {renderDayDetail && <TableCell>Horario</TableCell>}
                  <TableCell align="right">{totalLabel.split(' ')[0]}</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {days.map((day) => (
                  <TableRow key={day.date}>
                    <TableCell sx={{ textTransform: 'capitalize' }}>{formatDayLabel(day.date)}</TableCell>
                    {renderDayDetail && <TableCell>{renderDayDetail(day.date)}</TableCell>}
                    <TableCell align="right" sx={{ fontWeight: 600 }}>
                      {formatValue(day.value)}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        )}
      </Paper>

      <Paper elevation={0} variant="outlined" sx={{ borderRadius: 3, p: 2 }}>
        <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
          Por semana
        </Typography>
        {weeks.length === 0 ? (
          <Typography variant="body2" color="text.secondary">
            {emptyMessage}
          </Typography>
        ) : (
          <TableContainer>
            <Table size="small">
              <TableBody>
                {weeks.map((week) => (
                  <TableRow key={week.weekStart}>
                    <TableCell>{formatWeekLabel(week.weekStart, week.weekEnd)}</TableCell>
                    <TableCell align="right" sx={{ fontWeight: 600 }}>
                      {formatValue(week.value)}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        )}
      </Paper>

      <Paper elevation={0} variant="outlined" sx={{ borderRadius: 3, p: 3, textAlign: 'center' }}>
        <Typography variant="body2" color="text.secondary">
          Por mes · {totalLabel}
        </Typography>
        <Typography variant="h4" sx={{ fontWeight: 700 }}>
          {formatValue(totalValue)}
        </Typography>
      </Paper>
    </Stack>
  )
}

export function HoursPage() {
  useRequireAuth()
  const { token, user } = useAuth()
  const isStaff = user?.roles.includes('ROLE_ADMIN') || user?.roles.includes('ROLE_COORDINADOR')
  const [monthOffset, setMonthOffset] = useState(0)
  const [selectedUserId, setSelectedUserId] = useState('')

  const { monthStartIso, monthEndExclusiveIso, monthLabel } = useMemo(() => {
    const now = new Date()
    const start = new Date(now.getFullYear(), now.getMonth() + monthOffset, 1)
    const nextMonthStart = new Date(start.getFullYear(), start.getMonth() + 1, 1)

    return {
      monthStartIso: toLocalDateIso(start),
      monthEndExclusiveIso: toLocalDateIso(nextMonthStart),
      monthLabel: start.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' }),
    }
  }, [monthOffset])

  // Admin/Coordinador pueden elegir ver el resumen de otro empleado (el
  // backend re-valida el alcance de todos modos: Coordinador solo puede
  // consultar empleados de su propio departamento).
  const usersQuery = useQuery({
    queryKey: ['staff-users-for-hours'],
    queryFn: () => listUsers(token!, 1, 100),
    enabled: !!token && !!isStaff,
  })

  const viewingUserId = isStaff && selectedUserId ? selectedUserId : null

  const shiftsQuery = useQuery({
    queryKey: ['my-shifts-month', monthStartIso, monthEndExclusiveIso, viewingUserId],
    queryFn: () =>
      viewingUserId
        ? listShiftsForUser(token!, viewingUserId, monthStartIso, monthEndExclusiveIso)
        : listMyShifts(token!, monthStartIso, monthEndExclusiveIso),
    enabled: !!token,
  })

  // "Realizadas" = el turno ya terminó (endAt en el pasado). "Asignadas a
  // futuro" = todavía no terminó (incluye uno en curso ahora mismo).
  const { pastShifts, futureShifts } = useMemo(() => {
    const now = new Date()
    const shifts = shiftsQuery.data ?? []

    return {
      pastShifts: shifts.filter((shift) => new Date(shift.endAt) <= now),
      futureShifts: shifts.filter((shift) => new Date(shift.endAt) > now),
    }
  }, [shiftsQuery.data])

  const pastSummary = useMemo(() => summarizeShifts(pastShifts), [pastShifts])
  const futureSummary = useMemo(() => summarizeShifts(futureShifts), [futureShifts])

  const allShifts = shiftsQuery.data ?? []
  const activitySummary = useMemo(() => summarizeActivityCounts(allShifts), [allShifts])

  function renderActivityDayDetail(dateIso: string) {
    const activities = allShifts
      .flatMap((shift) => shift.roomActivities)
      .filter((activity) => toLocalDateIso(new Date(activity.startAt)) === dateIso)
      .sort((a, b) => a.startAt.localeCompare(b.startAt))

    return activities
      .map((activity) => `${formatTime(activity.startAt)} ${activity.activityTypeName ?? 'Actividad'}`)
      .join(', ')
  }

  const theme = useTheme()
  const isDesktop = useMediaQuery(theme.breakpoints.up('md'))

  return (
    <Box sx={{ minHeight: '100svh', display: 'flex', flexDirection: 'column', bgcolor: 'background.default' }}>
      <Header />

      <Container maxWidth="lg" sx={{ py: 3, flexGrow: 1 }}>
        {isStaff && (
          <Stack sx={{ mb: 2, alignItems: 'center' }}>
            <TextField
              select
              size="small"
              label="Empleado"
              value={selectedUserId}
              onChange={(e) => setSelectedUserId(e.target.value)}
              sx={{ minWidth: 260 }}
            >
              <MenuItem value="">Yo mismo</MenuItem>
              {(usersQuery.data?.items ?? [])
                .filter((u) => u.id !== user?.id)
                .map((u) => (
                  <MenuItem key={u.id} value={u.id}>
                    {[u.firstName, u.lastName].filter(Boolean).join(' ') || u.email}
                  </MenuItem>
                ))}
            </TextField>
          </Stack>
        )}

        <Stack direction="row" sx={{ mb: 3, alignItems: 'center', justifyContent: 'center', gap: 1 }}>
          <IconButton onClick={() => setMonthOffset((offset) => offset - 1)} aria-label="Mes anterior">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
            </svg>
          </IconButton>
          <Typography variant="h6" sx={{ fontWeight: 700, textTransform: 'capitalize', minWidth: 180, textAlign: 'center' }}>
            {monthLabel}
          </Typography>
          <IconButton onClick={() => setMonthOffset((offset) => offset + 1)} aria-label="Mes siguiente">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="m8.59 16.59 1.41 1.41 6-6-6-6-1.41 1.41L13.17 12z" />
            </svg>
          </IconButton>
        </Stack>

        {shiftsQuery.isLoading ? (
          <Stack sx={{ py: 6, alignItems: 'center' }}>
            <CircularProgress size={28} />
          </Stack>
        ) : (
          <Stack spacing={4}>
            <Stack
              direction={isDesktop ? 'row' : 'column'}
              spacing={4}
              divider={<Divider orientation={isDesktop ? 'vertical' : 'horizontal'} flexItem />}
              sx={{ alignItems: 'stretch' }}
            >
              <Box sx={{ flex: 1, minWidth: 0 }}>
                <SummarySection
                  title="Horas realizadas"
                  totalLabel={`Trabajado en ${monthLabel}`}
                  totalValue={pastSummary.totalHours}
                  formatValue={formatHours}
                  days={pastSummary.days.map((d) => ({ date: d.date, value: d.hours }))}
                  weeks={pastSummary.weeks.map((w) => ({ weekStart: w.weekStart, weekEnd: w.weekEnd, value: w.hours }))}
                  emptyMessage="Todavía no realizaste turnos este mes."
                  renderDayDetail={(dateIso) => {
                    const day = pastSummary.days.find((d) => d.date === dateIso)
                    return day?.shifts.map((shift) => `${formatTime(shift.startAt)}–${formatTime(shift.endAt)}`).join(', ')
                  }}
                />
              </Box>

              <Box sx={{ flex: 1, minWidth: 0 }}>
                <SummarySection
                  title="Horas asignadas (a futuro)"
                  totalLabel={`Asignado en ${monthLabel}`}
                  totalValue={futureSummary.totalHours}
                  formatValue={formatHours}
                  days={futureSummary.days.map((d) => ({ date: d.date, value: d.hours }))}
                  weeks={futureSummary.weeks.map((w) => ({ weekStart: w.weekStart, weekEnd: w.weekEnd, value: w.hours }))}
                  emptyMessage="No tenés turnos asignados a futuro este mes."
                  renderDayDetail={(dateIso) => {
                    const day = futureSummary.days.find((d) => d.date === dateIso)
                    return day?.shifts.map((shift) => `${formatTime(shift.startAt)}–${formatTime(shift.endAt)}`).join(', ')
                  }}
                />
              </Box>
            </Stack>

            <Divider />

            <Box sx={{ maxWidth: isDesktop ? '50%' : undefined }}>
              <SummarySection
                title="Actividades"
                totalLabel={`Actividades en ${monthLabel}`}
                totalValue={activitySummary.totalCount}
                formatValue={formatCount}
                days={activitySummary.days.map((d) => ({ date: d.date, value: d.count }))}
                weeks={activitySummary.weeks.map((w) => ({ weekStart: w.weekStart, weekEnd: w.weekEnd, value: w.count }))}
                emptyMessage="No hay actividades de sala agendadas este mes."
                renderDayDetail={renderActivityDayDetail}
              />
            </Box>
          </Stack>
        )}
      </Container>

      <Footer />
    </Box>
  )
}
