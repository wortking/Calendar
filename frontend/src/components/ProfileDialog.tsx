import { useRef, useState, type FormEvent } from 'react'
import { useMutation } from '@tanstack/react-query'
import Alert from '@mui/material/Alert'
import Avatar from '@mui/material/Avatar'
import Box from '@mui/material/Box'
import Button from '@mui/material/Button'
import CircularProgress from '@mui/material/CircularProgress'
import Dialog from '@mui/material/Dialog'
import DialogActions from '@mui/material/DialogActions'
import DialogContent from '@mui/material/DialogContent'
import DialogTitle from '@mui/material/DialogTitle'
import Stack from '@mui/material/Stack'
import TextField from '@mui/material/TextField'
import Typography from '@mui/material/Typography'
import { updateProfile, uploadAvatar } from '../api/auth'
import { ApiError } from '../api/client'
import { useAuth } from '../auth/AuthContext'

export function ProfileDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { token, user, refreshUser } = useAuth()
  const fileInputRef = useRef<HTMLInputElement>(null)

  const [firstName, setFirstName] = useState(user?.firstName ?? '')
  const [lastName, setLastName] = useState(user?.lastName ?? '')
  const [formError, setFormError] = useState<string | null>(null)
  const [avatarError, setAvatarError] = useState<string | null>(null)
  const [saved, setSaved] = useState(false)

  // El diálogo se monta una sola vez (vive en el Header); al abrirlo de
  // nuevo hay que releer los valores actuales por si cambiaron mientras
  // estaba cerrado (p. ej. tras un refresh de sesión).
  function handleEnter() {
    setFirstName(user?.firstName ?? '')
    setLastName(user?.lastName ?? '')
    setFormError(null)
    setAvatarError(null)
    setSaved(false)
  }

  const saveMutation = useMutation({
    mutationFn: () =>
      updateProfile(token!, {
        firstName: firstName.trim() || null,
        lastName: lastName.trim() || null,
        dni: user?.dni ?? null,
        sex: user?.sex ?? null,
      }),
    onSuccess: async () => {
      await refreshUser()
      setSaved(true)
    },
    onError: (err: unknown) => {
      setFormError(err instanceof ApiError ? err.message : 'No se pudo guardar el perfil.')
    },
  })

  const avatarMutation = useMutation({
    mutationFn: (file: File) => uploadAvatar(token!, file),
    onSuccess: async () => {
      await refreshUser()
    },
    onError: (err: unknown) => {
      setAvatarError(err instanceof ApiError ? err.message : 'No se pudo subir la imagen.')
    },
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setFormError(null)
    setSaved(false)
    saveMutation.mutate()
  }

  function handleAvatarChange(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0]
    if (file) {
      setAvatarError(null)
      avatarMutation.mutate(file)
    }
    event.target.value = ''
  }

  if (!user) {
    return null
  }

  const displayName = [user.firstName, user.lastName].filter(Boolean).join(' ') || user.email

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="xs"
      fullWidth
      slotProps={{ transition: { onEnter: handleEnter } }}
    >
      <DialogTitle>Mi perfil</DialogTitle>
      <Box component="form" onSubmit={handleSubmit}>
        <DialogContent>
          <Stack spacing={2.5}>
            <Stack spacing={1} sx={{ alignItems: 'center' }}>
              <Box sx={{ position: 'relative' }}>
                <Avatar src={user.avatarUrl ?? undefined} sx={{ width: 88, height: 88, fontSize: 32 }}>
                  {displayName.charAt(0).toUpperCase()}
                </Avatar>
                {avatarMutation.isPending && (
                  <Box
                    sx={{
                      position: 'absolute',
                      inset: 0,
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      bgcolor: 'rgba(0,0,0,0.4)',
                      borderRadius: '50%',
                    }}
                  >
                    <CircularProgress size={28} sx={{ color: 'common.white' }} />
                  </Box>
                )}
              </Box>
              <input
                ref={fileInputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                hidden
                onChange={handleAvatarChange}
              />
              <Button size="small" onClick={() => fileInputRef.current?.click()} disabled={avatarMutation.isPending}>
                Cambiar foto
              </Button>
              {avatarError && (
                <Alert severity="error" sx={{ width: '100%' }} onClose={() => setAvatarError(null)}>
                  {avatarError}
                </Alert>
              )}
            </Stack>

            <Stack spacing={0.5}>
              <Typography variant="body2" color="text.secondary">
                {user.email}
              </Typography>
            </Stack>

            <TextField
              label="Nombre"
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              fullWidth
            />
            <TextField
              label="Apellido"
              value={lastName}
              onChange={(e) => setLastName(e.target.value)}
              fullWidth
            />

            {saved && !formError && <Alert severity="success">Perfil actualizado correctamente.</Alert>}
            {formError && <Alert severity="error">{formError}</Alert>}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>Cerrar</Button>
          <Button type="submit" variant="contained" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Guardando…' : 'Guardar'}
          </Button>
        </DialogActions>
      </Box>
    </Dialog>
  )
}
