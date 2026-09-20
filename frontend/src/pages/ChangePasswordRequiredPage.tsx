import { useEffect, useState, type FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import Button from '@mui/material/Button'
import Container from '@mui/material/Container'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import TextField from '@mui/material/TextField'
import Typography from '@mui/material/Typography'
import { changePassword } from '../api/auth'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/AuthContext'
import { useRequireAuth } from '../auth/useRequireAuth'

// Pantalla obligatoria para quien todavía tiene la contraseña temporal que
// le generó el sistema al crear su cuenta (ver User.mustChangePassword):
// useRequireAuth/useRequireStaff/useRequireAdmin redirigen acá a cualquier
// ruta protegida hasta que la cambie, así que no hay forma de esquivarla
// navegando a otra página. No lleva Header (sin links a los que escapar).
export function ChangePasswordRequiredPage() {
  useRequireAuth()
  const { user, token, logout, refreshUser } = useAuth()
  const navigate = useNavigate()
  const [currentPassword, setCurrentPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  // Si por algún motivo ya no hace falta cambiarla (por ejemplo, la cambió
  // en otra pestaña), no tiene sentido dejarla varada acá.
  useEffect(() => {
    if (user && !user.mustChangePassword) {
      navigate('/dashboard', { replace: true })
    }
  }, [user, navigate])

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)

    if (newPassword !== confirmPassword) {
      setError('Las contraseñas nuevas no coinciden.')
      return
    }

    setIsSubmitting(true)
    try {
      await changePassword(token!, currentPassword, newPassword)
      await refreshUser()
      navigate('/dashboard', { replace: true })
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo cambiar la contraseña.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Box
      sx={{
        minHeight: '100svh',
        display: 'flex',
        alignItems: 'center',
        bgcolor: 'background.default',
      }}
    >
      <Container maxWidth="xs">
        <Paper elevation={0} variant="outlined" sx={{ p: 4, borderRadius: 3 }}>
          <Stack spacing={0.5} sx={{ mb: 3 }}>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              Cambiá tu contraseña
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Tu cuenta se creó con una contraseña temporal. Antes de continuar, tenés que elegir una nueva.
            </Typography>
          </Stack>

          <Box component="form" onSubmit={handleSubmit}>
            <Stack spacing={2}>
              <TextField
                label="Contraseña temporal"
                type="password"
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                autoComplete="current-password"
                autoFocus
                fullWidth
                required
              />
              <TextField
                label="Contraseña nueva"
                type="password"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                helperText="Mínimo 8 caracteres, con una mayúscula, un número y un símbolo."
                autoComplete="new-password"
                fullWidth
                required
              />
              <TextField
                label="Confirmar contraseña nueva"
                type="password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                autoComplete="new-password"
                fullWidth
                required
              />
              {error && <Alert severity="error">{error}</Alert>}
              <Button type="submit" variant="contained" size="large" disabled={isSubmitting} fullWidth>
                {isSubmitting ? 'Guardando…' : 'Cambiar contraseña'}
              </Button>
              <Button onClick={() => void logout()} size="small">
                Cerrar sesión
              </Button>
            </Stack>
          </Box>
        </Paper>
      </Container>
    </Box>
  )
}
