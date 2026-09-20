import CssBaseline from '@mui/material/CssBaseline'
import { ThemeProvider } from '@mui/material/styles'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthContext'
import { ChangePasswordRequiredPage } from './pages/ChangePasswordRequiredPage'
import { DashboardPage } from './pages/DashboardPage'
import { HoursPage } from './pages/HoursPage'
import { LoginPage } from './pages/LoginPage'
import { CompaniesPage } from './pages/admin/CompaniesPage'
import { RoomManagerPage } from './pages/admin/RoomManagerPage'
import { UsersPage } from './pages/admin/UsersPage'
import { theme } from './theme'

const queryClient = new QueryClient()

function App() {
  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <QueryClientProvider client={queryClient}>
        <BrowserRouter>
          <AuthProvider>
            <Routes>
              <Route path="/" element={<LoginPage />} />
              <Route path="/change-password" element={<ChangePasswordRequiredPage />} />
              <Route path="/dashboard" element={<DashboardPage />} />
              <Route path="/hours" element={<HoursPage />} />
              <Route path="/admin/users" element={<UsersPage />} />
              <Route path="/admin/companies" element={<CompaniesPage />} />
              <Route path="/admin/rooms" element={<RoomManagerPage />} />
              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </AuthProvider>
        </BrowserRouter>
      </QueryClientProvider>
    </ThemeProvider>
  )
}

export default App
