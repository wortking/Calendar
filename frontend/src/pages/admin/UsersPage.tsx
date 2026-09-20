import { useEffect, useMemo, useState, type FormEvent } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import Button from '@mui/material/Button'
import Chip from '@mui/material/Chip'
import CircularProgress from '@mui/material/CircularProgress'
import Container from '@mui/material/Container'
import Dialog from '@mui/material/Dialog'
import DialogActions from '@mui/material/DialogActions'
import DialogContent from '@mui/material/DialogContent'
import DialogTitle from '@mui/material/DialogTitle'
import Divider from '@mui/material/Divider'
import MenuItem from '@mui/material/MenuItem'
import Pagination from '@mui/material/Pagination'
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
import {
  assignDepartmentToUser,
  assignRoleToUser,
  createUser,
  deactivateUser,
  listDepartments,
  listRoles,
  listUsers,
  revokeDepartmentFromUser,
  revokeRoleFromUser,
  type DeactivateUserResult,
  type UserSummary,
} from '../../api/admin'
import { ApiError } from '../../api/client'
import { createShift } from '../../api/shifts'
import { useAuth } from '../../auth/AuthContext'
import { useRequireStaff } from '../../auth/useRequireStaff'
import { Footer } from '../../components/Footer'
import { Header } from '../../components/Header'

export function UsersPage() {
  useRequireStaff()
  const { token, user } = useAuth()
  const isAdmin = user?.roles.includes('ROLE_ADMIN') ?? false
  const ownDepartmentIds = useMemo(() => new Set((user?.departments ?? []).map((d) => d.id)), [user])
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const [emailFilterInput, setEmailFilterInput] = useState('')
  const [emailFilter, setEmailFilter] = useState('')
  const [departmentFilter, setDepartmentFilter] = useState('')
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [newEmail, setNewEmail] = useState('')
  const [createError, setCreateError] = useState<string | null>(null)

  // Diálogo "Gestionar usuario": agrupa roles, departamentos y turno de una
  // fila en un solo lugar en vez de selects sueltos en cada celda.
  const [manageDialogUser, setManageDialogUser] = useState<UserSummary | null>(null)
  const [roleToAssign, setRoleToAssign] = useState('')
  const [departmentToAssign, setDepartmentToAssign] = useState('')
  const [actionError, setActionError] = useState<string | null>(null)

  const [shiftDialogUser, setShiftDialogUser] = useState<UserSummary | null>(null)
  const [shiftDate, setShiftDate] = useState('')
  const [shiftStart, setShiftStart] = useState('')
  const [shiftEnd, setShiftEnd] = useState('')
  const [shiftError, setShiftError] = useState<string | null>(null)

  // Diálogo de confirmación para dar de baja (baja lógica): el usuario deja
  // de poder iniciar sesión, sus turnos pendientes se cancelan, y las
  // actividades de sala que tenía pasan a quien ejecuta la baja.
  const [deactivateDialogUser, setDeactivateDialogUser] = useState<UserSummary | null>(null)
  const [deactivateError, setDeactivateError] = useState<string | null>(null)
  const [deactivateResult, setDeactivateResult] = useState<DeactivateUserResult | null>(null)

  // El email se busca con un pequeño debounce para no disparar una request
  // por cada tecla; el filtro de departamento se aplica al toque.
  useEffect(() => {
    const handle = setTimeout(() => {
      setEmailFilter(emailFilterInput.trim())
      setPage(1)
    }, 400)
    return () => clearTimeout(handle)
  }, [emailFilterInput])

  const usersQuery = useQuery({
    queryKey: ['admin-users', page, emailFilter, departmentFilter],
    queryFn: () =>
      listUsers(token!, page, 10, {
        email: emailFilter || undefined,
        departmentId: departmentFilter || undefined,
      }),
    enabled: !!token,
  })

  const rolesQuery = useQuery({
    queryKey: ['admin-roles'],
    queryFn: () => listRoles(token!),
    enabled: !!token,
  })

  const departmentsQuery = useQuery({
    queryKey: ['admin-departments'],
    queryFn: () => listDepartments(token!),
    enabled: !!token,
  })

  const filterableDepartments = useMemo(
    () => (isAdmin ? (departmentsQuery.data ?? []) : (departmentsQuery.data ?? []).filter((d) => ownDepartmentIds.has(d.id))),
    [isAdmin, departmentsQuery.data, ownDepartmentIds],
  )

  const roleIdByName = useMemo(() => {
    const map = new Map<string, string>()
    for (const role of rolesQuery.data ?? []) {
      map.set(role.name, role.id)
    }
    return map
  }, [rolesQuery.data])

  // El usuario que se está gestionando puede haber cambiado (roles/deptos)
  // desde que se abrió el diálogo; usamos siempre la versión fresca de la
  // lista para que los chips se actualicen sin cerrar y reabrir.
  const manageUser = manageDialogUser
    ? (usersQuery.data?.items.find((u) => u.id === manageDialogUser.id) ?? manageDialogUser)
    : null

  const createMutation = useMutation({
    mutationFn: () => createUser(token!, newEmail),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      setIsCreateOpen(false)
      setNewEmail('')
      setCreateError(null)
    },
    onError: (err: unknown) => {
      setCreateError(err instanceof ApiError ? err.message : 'No se pudo crear el usuario.')
    },
  })

  const assignMutation = useMutation({
    mutationFn: ({ userId, roleName }: { userId: string; roleName: string }) => assignRoleToUser(token!, userId, roleName),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      setRoleToAssign('')
    },
    onError: (err: unknown) => setActionError(err instanceof ApiError ? err.message : 'No se pudo asignar el rol.'),
  })

  const revokeMutation = useMutation({
    mutationFn: ({ userId, roleId }: { userId: string; roleId: string }) => revokeRoleFromUser(token!, userId, roleId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-users'] }),
    onError: (err: unknown) => setActionError(err instanceof ApiError ? err.message : 'No se pudo quitar el rol.'),
  })

  const assignDepartmentMutation = useMutation({
    mutationFn: ({ userId, departmentId }: { userId: string; departmentId: string }) =>
      assignDepartmentToUser(token!, userId, departmentId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      setDepartmentToAssign('')
    },
    onError: (err: unknown) => setActionError(err instanceof ApiError ? err.message : 'No se pudo asignar el departamento.'),
  })

  const revokeDepartmentMutation = useMutation({
    mutationFn: ({ userId, departmentId }: { userId: string; departmentId: string }) =>
      revokeDepartmentFromUser(token!, userId, departmentId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-users'] }),
    onError: (err: unknown) => setActionError(err instanceof ApiError ? err.message : 'No se pudo quitar el departamento.'),
  })

  const createShiftMutation = useMutation({
    mutationFn: () =>
      createShift(token!, shiftDialogUser!.id, `${shiftDate}T${shiftStart}:00`, `${shiftDate}T${shiftEnd}:00`),
    onSuccess: () => {
      setShiftDialogUser(null)
    },
    onError: (err: unknown) => {
      setShiftError(err instanceof ApiError ? err.message : 'No se pudo asignar el turno.')
    },
  })

  const deactivateMutation = useMutation({
    mutationFn: () => deactivateUser(token!, deactivateDialogUser!.id),
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      setDeactivateResult(result)
    },
    onError: (err: unknown) => {
      setDeactivateError(err instanceof ApiError ? err.message : 'No se pudo dar de baja al usuario.')
    },
  })

  function handleCreateSubmit(event: FormEvent) {
    event.preventDefault()
    setCreateError(null)
    createMutation.mutate()
  }

  function openManageDialog(rowUser: UserSummary) {
    setManageDialogUser(rowUser)
    setRoleToAssign('')
    setDepartmentToAssign('')
    setActionError(null)
  }

  function openShiftDialog(rowUser: UserSummary) {
    setManageDialogUser(null)
    setShiftDialogUser(rowUser)
    setShiftDate('')
    setShiftStart('')
    setShiftEnd('')
    setShiftError(null)
  }

  function handleShiftSubmit(event: FormEvent) {
    event.preventDefault()
    setShiftError(null)
    createShiftMutation.mutate()
  }

  function openDeactivateDialog(rowUser: UserSummary) {
    setManageDialogUser(null)
    setDeactivateDialogUser(rowUser)
    setDeactivateError(null)
    setDeactivateResult(null)
  }

  function closeDeactivateDialog() {
    setDeactivateDialogUser(null)
    setDeactivateError(null)
    setDeactivateResult(null)
  }

  const assignableRoles = manageUser
    ? isAdmin
      ? (rolesQuery.data ?? [])
      : (rolesQuery.data ?? []).filter((role) => role.name === 'ROLE_USER')
    : []
  const availableRoles = manageUser ? assignableRoles.filter((role) => !manageUser.roles.includes(role.name)) : []

  const manageAssignedDepartmentIds = new Set((manageUser?.departments ?? []).map((department) => department.id))
  const manageAssignableDepartments = manageUser
    ? isAdmin
      ? (departmentsQuery.data ?? [])
      : (departmentsQuery.data ?? []).filter((department) => ownDepartmentIds.has(department.id))
    : []
  const manageAvailableDepartments = manageAssignableDepartments.filter(
    (department) => !manageAssignedDepartmentIds.has(department.id),
  )

  return (
    <Box sx={{ minHeight: '100svh', display: 'flex', flexDirection: 'column', bgcolor: 'background.default' }}>
      <Header />

      <Container maxWidth="lg" sx={{ py: 3, flexGrow: 1 }}>
        <Stack direction="row" sx={{ mb: 2, alignItems: 'center' }}>
          <Typography variant="h5" sx={{ flexGrow: 1, fontWeight: 700 }}>
            Usuarios
          </Typography>
          <Button variant="contained" onClick={() => setIsCreateOpen(true)}>
            Nuevo usuario
          </Button>
        </Stack>

        <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} sx={{ mb: 2 }}>
          <TextField
            size="small"
            label="Buscar por email"
            value={emailFilterInput}
            onChange={(e) => setEmailFilterInput(e.target.value)}
            sx={{ minWidth: 240 }}
          />
          <TextField
            select
            size="small"
            label="Departamento"
            value={departmentFilter}
            onChange={(e) => {
              setDepartmentFilter(e.target.value)
              setPage(1)
            }}
            sx={{ minWidth: 200 }}
          >
            <MenuItem value="">Todos</MenuItem>
            {filterableDepartments.map((department) => (
              <MenuItem key={department.id} value={department.id}>
                {department.name}
              </MenuItem>
            ))}
          </TextField>
        </Stack>

        <Paper elevation={0} variant="outlined" sx={{ borderRadius: 3 }}>
          <TableContainer>
            <Table size="small">
              <TableHead>
                <TableRow>
                  <TableCell>Email</TableCell>
                  <TableCell>Nombre</TableCell>
                  <TableCell>Empresa</TableCell>
                  <TableCell>Roles</TableCell>
                  <TableCell>Departamentos</TableCell>
                  <TableCell>Estado</TableCell>
                  <TableCell align="right">Acciones</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {usersQuery.isLoading && (
                  <TableRow>
                    <TableCell colSpan={7} align="center" sx={{ py: 4 }}>
                      <CircularProgress size={24} />
                    </TableCell>
                  </TableRow>
                )}

                {!usersQuery.isLoading && usersQuery.data?.items.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={7} align="center" sx={{ py: 4 }}>
                      <Typography color="text.secondary">No se encontraron usuarios con esos filtros.</Typography>
                    </TableCell>
                  </TableRow>
                )}

                {usersQuery.data?.items.map((rowUser) => (
                  <TableRow key={rowUser.id} hover>
                    <TableCell>{rowUser.email}</TableCell>
                    <TableCell>{[rowUser.firstName, rowUser.lastName].filter(Boolean).join(' ') || '—'}</TableCell>
                    <TableCell>
                      {rowUser.company ? (
                        rowUser.company.name
                      ) : (
                        <Typography variant="body2" color="text.secondary">
                          —
                        </Typography>
                      )}
                    </TableCell>
                    <TableCell>
                      <Stack direction="row" spacing={0.5} sx={{ flexWrap: 'wrap', gap: 0.5 }}>
                        {rowUser.roles.map((roleName) => (
                          <Chip key={roleName} label={roleName} size="small" />
                        ))}
                      </Stack>
                    </TableCell>
                    <TableCell>
                      <Stack direction="row" spacing={0.5} sx={{ flexWrap: 'wrap', gap: 0.5 }}>
                        {rowUser.departments.length === 0 ? (
                          <Typography variant="body2" color="text.secondary">
                            —
                          </Typography>
                        ) : (
                          rowUser.departments.map((department) => (
                            <Chip key={department.id} label={department.name} size="small" variant="outlined" />
                          ))
                        )}
                      </Stack>
                    </TableCell>
                    <TableCell>
                      {rowUser.deactivatedAt ? (
                        <Chip label="Dado de baja" size="small" color="default" variant="outlined" />
                      ) : (
                        <Chip label="Activo" size="small" color="success" variant="outlined" />
                      )}
                    </TableCell>
                    <TableCell align="right">
                      <Stack direction="row" spacing={1} sx={{ justifyContent: 'flex-end' }}>
                        <Button size="small" onClick={() => openManageDialog(rowUser)}>
                          Gestionar
                        </Button>
                        {!rowUser.deactivatedAt && rowUser.id !== user?.id && (
                          <Button size="small" color="error" onClick={() => openDeactivateDialog(rowUser)}>
                            Dar de baja
                          </Button>
                        )}
                      </Stack>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        </Paper>

        {usersQuery.data && usersQuery.data.totalPages > 1 && (
          <Stack sx={{ mt: 3, alignItems: 'center' }}>
            <Pagination count={usersQuery.data.totalPages} page={page} onChange={(_, value) => setPage(value)} />
          </Stack>
        )}
      </Container>

      <Footer />

      <Dialog open={isCreateOpen} onClose={() => setIsCreateOpen(false)} maxWidth="xs" fullWidth>
        <DialogTitle>Nuevo usuario</DialogTitle>
        <Box component="form" onSubmit={handleCreateSubmit}>
          <DialogContent>
            <Stack spacing={2}>
              <Typography variant="body2" color="text.secondary">
                Se le va a generar una contraseña temporal y se le va a enviar por email junto con el link para
                confirmar su cuenta. Va a tener que cambiarla la primera vez que inicie sesión.
              </Typography>
              <TextField
                label="Email"
                type="email"
                value={newEmail}
                onChange={(e) => setNewEmail(e.target.value)}
                autoFocus
                fullWidth
                required
              />
              {createError && <Alert severity="error">{createError}</Alert>}
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setIsCreateOpen(false)}>Cancelar</Button>
            <Button type="submit" variant="contained" disabled={createMutation.isPending}>
              {createMutation.isPending ? 'Creando…' : 'Crear'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={!!manageUser} onClose={() => setManageDialogUser(null)} maxWidth="xs" fullWidth>
        <DialogTitle>{manageUser?.email}</DialogTitle>
        <DialogContent>
          <Stack spacing={2.5} sx={{ pt: 0.5 }}>
            {actionError && (
              <Alert severity="error" onClose={() => setActionError(null)}>
                {actionError}
              </Alert>
            )}

            <Stack spacing={1}>
              <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                Roles
              </Typography>
              <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap', gap: 1 }}>
                {manageUser?.roles.map((roleName) => {
                  const roleId = roleIdByName.get(roleName)
                  // Un coordinador solo puede tocar el rol Empleado (igual que el
                  // backend); los demás roles se muestran sin botón de quitar.
                  const canRevoke = isAdmin || roleName === 'ROLE_USER'
                  return (
                    <Chip
                      key={roleName}
                      label={roleName}
                      size="small"
                      onDelete={
                        roleId && canRevoke
                          ? () => revokeMutation.mutate({ userId: manageUser.id, roleId })
                          : undefined
                      }
                      disabled={revokeMutation.isPending}
                    />
                  )
                })}
              </Stack>
              {availableRoles.length > 0 && (
                <Stack direction="row" spacing={1}>
                  <TextField
                    select
                    size="small"
                    label="Agregar rol"
                    value={roleToAssign}
                    onChange={(e) => setRoleToAssign(e.target.value)}
                    fullWidth
                  >
                    {availableRoles.map((role) => (
                      <MenuItem key={role.id} value={role.name}>
                        {role.name}
                      </MenuItem>
                    ))}
                  </TextField>
                  <Button
                    variant="outlined"
                    disabled={!roleToAssign || assignMutation.isPending}
                    onClick={() => manageUser && roleToAssign && assignMutation.mutate({ userId: manageUser.id, roleName: roleToAssign })}
                  >
                    Agregar
                  </Button>
                </Stack>
              )}
            </Stack>

            <Divider />

            <Stack spacing={1}>
              <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                Departamentos
              </Typography>
              <Stack direction="row" spacing={1} sx={{ flexWrap: 'wrap', gap: 1 }}>
                {manageUser?.departments.length === 0 && (
                  <Typography variant="body2" color="text.secondary">
                    Sin departamentos asignados.
                  </Typography>
                )}
                {manageUser?.departments.map((department) => {
                  // Un coordinador solo puede quitar SU propio departamento
                  // (igual que el backend); el resto se muestra sin botón.
                  const canRevoke = isAdmin || ownDepartmentIds.has(department.id)
                  return (
                    <Chip
                      key={department.id}
                      label={department.name}
                      size="small"
                      variant="outlined"
                      onDelete={
                        canRevoke
                          ? () => revokeDepartmentMutation.mutate({ userId: manageUser.id, departmentId: department.id })
                          : undefined
                      }
                      disabled={revokeDepartmentMutation.isPending}
                    />
                  )
                })}
              </Stack>
              {manageAvailableDepartments.length > 0 && (
                <Stack direction="row" spacing={1}>
                  <TextField
                    select
                    size="small"
                    label="Agregar departamento"
                    value={departmentToAssign}
                    onChange={(e) => setDepartmentToAssign(e.target.value)}
                    fullWidth
                  >
                    {manageAvailableDepartments.map((department) => (
                      <MenuItem key={department.id} value={department.id}>
                        {department.name}
                      </MenuItem>
                    ))}
                  </TextField>
                  <Button
                    variant="outlined"
                    disabled={!departmentToAssign || assignDepartmentMutation.isPending}
                    onClick={() =>
                      manageUser &&
                      departmentToAssign &&
                      assignDepartmentMutation.mutate({ userId: manageUser.id, departmentId: departmentToAssign })
                    }
                  >
                    Agregar
                  </Button>
                </Stack>
              )}
            </Stack>

            <Divider />

            <Stack spacing={1}>
              <Typography variant="subtitle2" sx={{ fontWeight: 700 }}>
                Turno
              </Typography>
              <Button variant="outlined" onClick={() => manageUser && openShiftDialog(manageUser)} sx={{ alignSelf: 'flex-start' }}>
                Asignar turno
              </Button>
            </Stack>
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setManageDialogUser(null)}>Cerrar</Button>
        </DialogActions>
      </Dialog>

      <Dialog open={!!shiftDialogUser} onClose={() => setShiftDialogUser(null)} maxWidth="xs" fullWidth>
        <DialogTitle>Asignar turno {shiftDialogUser ? `a ${shiftDialogUser.email}` : ''}</DialogTitle>
        <Box component="form" onSubmit={handleShiftSubmit}>
          <DialogContent>
            <Stack spacing={2}>
              <TextField
                label="Fecha"
                type="date"
                value={shiftDate}
                onChange={(e) => setShiftDate(e.target.value)}
                slotProps={{ inputLabel: { shrink: true } }}
                autoFocus
                fullWidth
                required
              />
              <TextField
                label="Hora inicio"
                type="time"
                value={shiftStart}
                onChange={(e) => setShiftStart(e.target.value)}
                slotProps={{ inputLabel: { shrink: true } }}
                fullWidth
                required
              />
              <TextField
                label="Hora fin"
                type="time"
                value={shiftEnd}
                onChange={(e) => setShiftEnd(e.target.value)}
                slotProps={{ inputLabel: { shrink: true } }}
                fullWidth
                required
              />
              {shiftError && <Alert severity="error">{shiftError}</Alert>}
            </Stack>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setShiftDialogUser(null)}>Cancelar</Button>
            <Button type="submit" variant="contained" disabled={createShiftMutation.isPending}>
              {createShiftMutation.isPending ? 'Guardando…' : 'Asignar'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>

      <Dialog open={!!deactivateDialogUser} onClose={closeDeactivateDialog} maxWidth="xs" fullWidth>
        <DialogTitle>Dar de baja</DialogTitle>
        <DialogContent>
          {!deactivateResult ? (
            <Stack spacing={2} sx={{ pt: 0.5 }}>
              <Typography>
                ¿Dar de baja a <strong>{deactivateDialogUser?.email}</strong>? Ya no va a poder iniciar sesión. Se
                cancelarán sus turnos pendientes (el historial ya cumplido no se toca) y las actividades de sala que
                tenía agendadas pasarán a vos: vas a tener que reasignarlas a otra persona.
              </Typography>
              {deactivateError && <Alert severity="error">{deactivateError}</Alert>}
            </Stack>
          ) : (
            <Alert severity="success" sx={{ mt: 0.5 }}>
              Usuario dado de baja. Se cancelaron {deactivateResult.shiftsCancelled} turno
              {deactivateResult.shiftsCancelled === 1 ? '' : 's'} pendiente
              {deactivateResult.shiftsCancelled === 1 ? '' : 's'} y se te reasignaron{' '}
              {deactivateResult.activitiesReassigned} actividad{deactivateResult.activitiesReassigned === 1 ? '' : 'es'}{' '}
              de sala
              {deactivateResult.activitiesSkipped > 0
                ? ` (${deactivateResult.activitiesSkipped} no se pudieron reasignar por un conflicto de horario tuyo y quedaron a nombre del usuario dado de baja: revisalas manualmente).`
                : '.'}
            </Alert>
          )}
        </DialogContent>
        <DialogActions>
          {!deactivateResult ? (
            <>
              <Button onClick={closeDeactivateDialog}>Cancelar</Button>
              <Button
                color="error"
                variant="contained"
                onClick={() => deactivateMutation.mutate()}
                disabled={deactivateMutation.isPending}
              >
                {deactivateMutation.isPending ? 'Dando de baja…' : 'Dar de baja'}
              </Button>
            </>
          ) : (
            <Button onClick={closeDeactivateDialog}>Cerrar</Button>
          )}
        </DialogActions>
      </Dialog>
    </Box>
  )
}
