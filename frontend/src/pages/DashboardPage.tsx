import { useState } from 'react'
import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import { CalendarView } from '../components/CalendarView'
import { Footer } from '../components/Footer'
import { Header } from '../components/Header'
import { ShiftsOfDayPanel } from '../components/ShiftsOfDayPanel'
import { ShiftTools } from '../components/ShiftTools'
import { UserInfoPanel } from '../components/UserInfoPanel'
import { useAuth } from '../auth/AuthContext'
import { useRequireAuth } from '../auth/useRequireAuth'

const SIDEBAR_WIDTH = 280

function todayLocalIso(): string {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')

  return `${now.getFullYear()}-${month}-${day}`
}

export function DashboardPage() {
  useRequireAuth()
  const { user } = useAuth()
  const [activeDate, setActiveDate] = useState(todayLocalIso)

  if (!user) {
    return null
  }

  const isStaff = user.roles.includes('ROLE_ADMIN') || user.roles.includes('ROLE_COORDINADOR')

  return (
    <Box
      sx={{
        minHeight: '100svh',
        height: { md: '100svh' },
        display: 'flex',
        flexDirection: 'column',
        bgcolor: 'background.default',
        overflow: { md: 'hidden' },
      }}
    >
      <Header />

      <Box sx={{ px: 3, py: 3, flexGrow: 1, minHeight: { md: 0 }, display: 'flex' }}>
        <Stack direction={{ xs: 'column', md: 'row' }} spacing={3} sx={{ width: '100%', height: { md: '100%' } }}>
          <Box sx={{ width: { xs: '100%', md: SIDEBAR_WIDTH }, flexShrink: 0 }}>
            <UserInfoPanel user={user} />
          </Box>

          <Box
            sx={{
              flexGrow: 1,
              minWidth: 0,
              minHeight: { md: 0 },
              width: '100%',
              height: { md: '100%' },
              display: 'flex',
              flexDirection: 'column',
              gap: 1,
            }}
          >
            {isStaff && (
              <Stack direction="row" sx={{ justifyContent: 'flex-end' }}>
                <ShiftTools activeDate={activeDate} />
              </Stack>
            )}
            <Box sx={{ flexGrow: 1, minHeight: 0 }}>
              <CalendarView onActiveDateChange={setActiveDate} />
            </Box>
          </Box>

          <Box sx={{ width: { xs: '100%', md: SIDEBAR_WIDTH }, flexShrink: 0, minHeight: { md: 0 }, height: { md: '100%' } }}>
            <ShiftsOfDayPanel date={activeDate} />
          </Box>
        </Stack>
      </Box>

      <Footer />
    </Box>
  )
}
