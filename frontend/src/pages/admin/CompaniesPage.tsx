import { Fragment, useEffect, useMemo, useState, type FormEvent } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import Button from '@mui/material/Button'
import Chip from '@mui/material/Chip'
import CircularProgress from '@mui/material/CircularProgress'
import Collapse from '@mui/material/Collapse'
import Container from '@mui/material/Container'
import Dialog from '@mui/material/Dialog'
import DialogActions from '@mui/material/DialogActions'
import DialogContent from '@mui/material/DialogContent'
import DialogTitle from '@mui/material/DialogTitle'
import IconButton from '@mui/material/IconButton'
import List from '@mui/material/List'
import ListItem from '@mui/material/ListItem'
import MenuItem from '@mui/material/MenuItem'
import Pagination from '@mui/material/Pagination'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import Table from '@mui/material/Table'
import TableBody from '@mui/material/TableBody'
import TableCell from '@mui/material/TableCell'
import TableContainer from '@mui/material/TableContainer'
import TableRow from '@mui/material/TableRow'
import TextField from '@mui/material/TextField'
import Typography from '@mui/material/Typography'
import { createActivityType, listActivityTypes, updateActivityType, type ActivityType } from '../../api/activityTypes'
import { createDepartment, deleteDepartment, listDepartments, updateDepartment, type Department } from '../../api/admin'
import { ApiError } from '../../api/client'
import { createCompany, deleteCompany, listCompanies, updateCompany, type Company } from '../../api/companies'
import { createRoom, deleteRoom, listRooms, updateRoom, type Room } from '../../api/rooms'
import { useAuth } from '../../auth/AuthContext'
import { useRequireAdmin } from '../../auth/useRequireAdmin'
import { Footer } from '../../components/Footer'
import { Header } from '../../components/Header'

// Todos los listados de esta página (empresas, departamentos, actividades)
// muestran 10 filas por página.
const PAGE_SIZE = 10

function paginate<T>(items: T[], page: number): T[] {
  return items.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE)
}

function pageCount(itemCount: number): number {
  return Math.max(1, Math.ceil(itemCount / PAGE_SIZE))
}

// Botón "+"/"−" para expandir/colapsar una fila e ir revelando el nivel de
// abajo (empresa -> departamentos -> actividades), en vez de un acordeón.
function ExpandButton({ expanded, onClick, label }: { expanded: boolean; onClick: () => void; label: string }) {
  return (
    <IconButton
      size="small"
      onClick={onClick}
      aria-label={label}
      aria-expanded={expanded}
      sx={{
        width: 28,
        height: 28,
        border: 1,
        borderColor: 'divider',
        fontSize: 16,
        lineHeight: 1,
      }}
    >
      {expanded ? '−' : '+'}
    </IconButton>
  )
}

// Diálogo genérico de "empresa": crear o editar nombre/horario.
function CompanyDialog({
  open,
  onClose,
  editingCompany,
  token,
}: {
  open: boolean
  onClose: () => void
  editingCompany: Company | null
  token: string
}) {
  const queryClient = useQueryClient()
  const [name, setName] = useState('')
  const [openingTime, setOpeningTime] = useState('')
  const [closingTime, setClosingTime] = useState('')
  const [formError, setFormError] = useState<string | null>(null)

  // El diálogo no se desmonta al cerrarse (vive siempre en el árbol), así
  // que hay que releer los valores al abrirlo de nuevo: si no, reabrirlo
  // para otra empresa (o para crear una después de haber editado una)
  // mostraría los datos de la anterior.
  useEffect(() => {
    if (open) {
      setName(editingCompany?.name ?? '')
      setOpeningTime(editingCompany?.openingTime ?? '')
      setClosingTime(editingCompany?.closingTime ?? '')
      setFormError(null)
    }
  }, [open, editingCompany])

  const saveMutation = useMutation({
    mutationFn: () =>
      editingCompany
        ? updateCompany(token, editingCompany.id, name, openingTime, closingTime)
        : createCompany(token, name, openingTime, closingTime),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-companies'] })
      onClose()
    },
    onError: (err: unknown) => {
      setFormError(err instanceof ApiError ? err.message : 'No se pudo guardar la empresa.')
    },
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setFormError(null)
    saveMutation.mutate()
  }

  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>{editingCompany ? 'Editar empresa' : 'Nueva empresa'}</DialogTitle>
      <Box component="form" onSubmit={handleSubmit}>
        <DialogContent>
          <Stack spacing={2}>
            <TextField label="Nombre" value={name} onChange={(e) => setName(e.target.value)} autoFocus fullWidth required />
            <TextField
              label="Hora de apertura"
              type="time"
              value={openingTime}
              onChange={(e) => setOpeningTime(e.target.value)}
              slotProps={{ inputLabel: { shrink: true } }}
              fullWidth
              required
            />
            <TextField
              label="Hora de cierre"
              type="time"
              value={closingTime}
              onChange={(e) => setClosingTime(e.target.value)}
              slotProps={{ inputLabel: { shrink: true } }}
              fullWidth
              required
            />
            {formError && <Alert severity="error">{formError}</Alert>}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="contained" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Guardando…' : 'Guardar'}
          </Button>
        </DialogActions>
      </Box>
    </Dialog>
  )
}

// Confirmación de borrado: borra en cascada los departamentos de la empresa
// y las actividades de esos departamentos. El backend lo rechaza (y acá se
// muestra el motivo) si algún departamento todavía tiene usuarios asignados.
function DeleteCompanyDialog({
  company,
  departmentCount,
  onClose,
  token,
}: {
  company: Company | null
  departmentCount: number
  onClose: () => void
  token: string
}) {
  const queryClient = useQueryClient()
  const [formError, setFormError] = useState<string | null>(null)

  const deleteMutation = useMutation({
    mutationFn: () => deleteCompany(token, company!.id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-companies'] })
      queryClient.invalidateQueries({ queryKey: ['admin-departments'] })
      queryClient.invalidateQueries({ queryKey: ['admin-activity-types'] })
      onClose()
    },
    onError: (err: unknown) => {
      setFormError(err instanceof ApiError ? err.message : 'No se pudo eliminar la empresa.')
    },
  })

  function handleClose() {
    setFormError(null)
    onClose()
  }

  return (
    <Dialog open={!!company} onClose={handleClose} maxWidth="xs" fullWidth>
      <DialogTitle>Eliminar empresa</DialogTitle>
      <DialogContent>
        <Stack spacing={2}>
          <Typography>
            ¿Eliminar <strong>{company?.name}</strong>?
            {departmentCount > 0
              ? ` Se eliminarán también ${departmentCount} departamento${departmentCount === 1 ? '' : 's'} y sus actividades. Esta acción no se puede deshacer.`
              : ' Esta acción no se puede deshacer.'}
          </Typography>
          {formError && <Alert severity="error">{formError}</Alert>}
        </Stack>
      </DialogContent>
      <DialogActions>
        <Button onClick={handleClose}>Cancelar</Button>
        <Button color="error" variant="contained" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>
          {deleteMutation.isPending ? 'Eliminando…' : 'Eliminar'}
        </Button>
      </DialogActions>
    </Dialog>
  )
}

// Diálogo de "departamento": la empresa es obligatoria (un departamento ya
// no puede existir sin una) y se pre-selecciona con la que disparó el
// "+ Nuevo departamento", pero se puede cambiar por otra.
function DepartmentDialog({
  open,
  onClose,
  editingDepartment,
  defaultCompanyId,
  companies,
  token,
}: {
  open: boolean
  onClose: () => void
  editingDepartment: Department | null
  defaultCompanyId: string
  companies: Company[]
  token: string
}) {
  const queryClient = useQueryClient()
  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [companyId, setCompanyId] = useState('')
  const [formError, setFormError] = useState<string | null>(null)

  // El diálogo no se desmonta al cerrarse (vive siempre en el árbol), así
  // que hay que releer los valores al abrirlo de nuevo: si no, reabrirlo
  // para otro departamento mostraría los datos del anterior.
  useEffect(() => {
    if (open) {
      setName(editingDepartment?.name ?? '')
      setDescription(editingDepartment?.description ?? '')
      setCompanyId(editingDepartment?.companyId ?? defaultCompanyId)
      setFormError(null)
    }
  }, [open, editingDepartment, defaultCompanyId])

  const saveMutation = useMutation({
    mutationFn: () =>
      editingDepartment
        ? updateDepartment(token, editingDepartment.id, name, companyId, description)
        : createDepartment(token, name, companyId, description),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-departments'] })
      onClose()
    },
    onError: (err: unknown) => {
      setFormError(err instanceof ApiError ? err.message : 'No se pudo guardar el departamento.')
    },
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setFormError(null)
    saveMutation.mutate()
  }

  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>{editingDepartment ? 'Editar departamento' : 'Nuevo departamento'}</DialogTitle>
      <Box component="form" onSubmit={handleSubmit}>
        <DialogContent>
          <Stack spacing={2}>
            <TextField label="Nombre" value={name} onChange={(e) => setName(e.target.value)} autoFocus fullWidth required />
            <TextField
              label="Descripción"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              fullWidth
              multiline
              minRows={2}
            />
            <TextField select label="Empresa" value={companyId} onChange={(e) => setCompanyId(e.target.value)} fullWidth required>
              {companies.map((company) => (
                <MenuItem key={company.id} value={company.id}>
                  {company.name}
                </MenuItem>
              ))}
            </TextField>
            {formError && <Alert severity="error">{formError}</Alert>}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="contained" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Guardando…' : 'Guardar'}
          </Button>
        </DialogActions>
      </Box>
    </Dialog>
  )
}

// Confirmación de borrado de departamento: borra en cascada sus tipos de
// actividad. El backend lo rechaza (y acá se muestra el motivo) si el
// departamento todavía tiene usuarios asignados.
function DeleteDepartmentDialog({
  department,
  onClose,
  token,
}: {
  department: Department | null
  onClose: () => void
  token: string
}) {
  const queryClient = useQueryClient()
  const [formError, setFormError] = useState<string | null>(null)

  const deleteMutation = useMutation({
    mutationFn: () => deleteDepartment(token, department!.id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-departments'] })
      queryClient.invalidateQueries({ queryKey: ['admin-activity-types'] })
      onClose()
    },
    onError: (err: unknown) => {
      setFormError(err instanceof ApiError ? err.message : 'No se pudo eliminar el departamento.')
    },
  })

  function handleClose() {
    setFormError(null)
    onClose()
  }

  return (
    <Dialog open={!!department} onClose={handleClose} maxWidth="xs" fullWidth>
      <DialogTitle>Eliminar departamento</DialogTitle>
      <DialogContent>
        <Stack spacing={2}>
          <Typography>
            ¿Eliminar <strong>{department?.name}</strong>? Se eliminarán también sus tipos de actividad. Esta acción
            no se puede deshacer.
          </Typography>
          {formError && <Alert severity="error">{formError}</Alert>}
        </Stack>
      </DialogContent>
      <DialogActions>
        <Button onClick={handleClose}>Cancelar</Button>
        <Button color="error" variant="contained" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>
          {deleteMutation.isPending ? 'Eliminando…' : 'Eliminar'}
        </Button>
      </DialogActions>
    </Dialog>
  )
}

// Diálogo de "sala": la empresa se fija a la que disparó el "+ Nueva sala"
// y no se puede cambiar (igual que un departamento no cambia de empresa
// desde acá tampoco... salvo que sí puede, a diferencia de la sala).
function RoomDialog({
  open,
  onClose,
  editingRoom,
  companyId,
  token,
}: {
  open: boolean
  onClose: () => void
  editingRoom: Room | null
  companyId: string
  token: string
}) {
  const queryClient = useQueryClient()
  const [name, setName] = useState('')
  const [formError, setFormError] = useState<string | null>(null)

  useEffect(() => {
    if (open) {
      setName(editingRoom?.name ?? '')
      setFormError(null)
    }
  }, [open, editingRoom])

  const saveMutation = useMutation({
    mutationFn: () => (editingRoom ? updateRoom(token, editingRoom.id, name) : createRoom(token, companyId, name)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-rooms'] })
      onClose()
    },
    onError: (err: unknown) => {
      setFormError(err instanceof ApiError ? err.message : 'No se pudo guardar la sala.')
    },
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setFormError(null)
    saveMutation.mutate()
  }

  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>{editingRoom ? 'Editar sala' : 'Nueva sala'}</DialogTitle>
      <Box component="form" onSubmit={handleSubmit}>
        <DialogContent>
          <Stack spacing={2}>
            <TextField label="Nombre" value={name} onChange={(e) => setName(e.target.value)} autoFocus fullWidth required />
            {formError && <Alert severity="error">{formError}</Alert>}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="contained" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Guardando…' : 'Guardar'}
          </Button>
        </DialogActions>
      </Box>
    </Dialog>
  )
}

// Confirmación de borrado de sala: se borran en cascada las actividades
// puntuales agendadas en ella.
function DeleteRoomDialog({
  room,
  onClose,
  token,
}: {
  room: Room | null
  onClose: () => void
  token: string
}) {
  const queryClient = useQueryClient()
  const [formError, setFormError] = useState<string | null>(null)

  const deleteMutation = useMutation({
    mutationFn: () => deleteRoom(token, room!.id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-rooms'] })
      queryClient.invalidateQueries({ queryKey: ['admin-activity-types'] })
      onClose()
    },
    onError: (err: unknown) => {
      setFormError(err instanceof ApiError ? err.message : 'No se pudo eliminar la sala.')
    },
  })

  function handleClose() {
    setFormError(null)
    onClose()
  }

  return (
    <Dialog open={!!room} onClose={handleClose} maxWidth="xs" fullWidth>
      <DialogTitle>Eliminar sala</DialogTitle>
      <DialogContent>
        <Stack spacing={2}>
          <Typography>
            ¿Eliminar <strong>{room?.name}</strong>? Se eliminarán también las actividades agendadas en ella. Esta
            acción no se puede deshacer.
          </Typography>
          {formError && <Alert severity="error">{formError}</Alert>}
        </Stack>
      </DialogContent>
      <DialogActions>
        <Button onClick={handleClose}>Cancelar</Button>
        <Button color="error" variant="contained" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>
          {deleteMutation.isPending ? 'Eliminando…' : 'Eliminar'}
        </Button>
      </DialogActions>
    </Dialog>
  )
}

// Diálogo de "tipo de actividad": el departamento queda fijo al que
// disparó el "+ Nueva actividad" (no se puede reasignar, ni al crear ni al
// editar, igual que ya validaba el backend).
function ActivityTypeDialog({
  open,
  onClose,
  editingActivityType,
  departmentId,
  token,
}: {
  open: boolean
  onClose: () => void
  editingActivityType: ActivityType | null
  departmentId: string
  token: string
}) {
  const queryClient = useQueryClient()
  const [name, setName] = useState('')
  const [color, setColor] = useState('#1976d2')
  const [formError, setFormError] = useState<string | null>(null)

  useEffect(() => {
    if (open) {
      setName(editingActivityType?.name ?? '')
      setColor(editingActivityType?.color ?? '#1976d2')
      setFormError(null)
    }
  }, [open, editingActivityType])

  const saveMutation = useMutation({
    mutationFn: () =>
      editingActivityType
        ? updateActivityType(token, editingActivityType.id, name, color)
        : createActivityType(token, departmentId, name, color),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-activity-types'] })
      onClose()
    },
    onError: (err: unknown) => {
      setFormError(err instanceof ApiError ? err.message : 'No se pudo guardar el tipo de actividad.')
    },
  })

  function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setFormError(null)
    saveMutation.mutate()
  }

  return (
    <Dialog open={open} onClose={onClose} maxWidth="xs" fullWidth>
      <DialogTitle>{editingActivityType ? 'Editar actividad' : 'Nueva actividad'}</DialogTitle>
      <Box component="form" onSubmit={handleSubmit}>
        <DialogContent>
          <Stack spacing={2}>
            <TextField label="Nombre" value={name} onChange={(e) => setName(e.target.value)} autoFocus fullWidth required />
            <TextField label="Color" type="color" value={color} onChange={(e) => setColor(e.target.value)} fullWidth />
            {formError && <Alert severity="error">{formError}</Alert>}
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={onClose}>Cancelar</Button>
          <Button type="submit" variant="contained" disabled={saveMutation.isPending}>
            {saveMutation.isPending ? 'Guardando…' : 'Guardar'}
          </Button>
        </DialogActions>
      </Box>
    </Dialog>
  )
}

// Fila de departamento: su propio "+" revela la lista de actividades debajo.
function DepartmentRow({
  department,
  activities,
  isExpanded,
  onToggle,
  onEditDepartment,
  onDeleteDepartment,
  onCreateActivity,
  onEditActivity,
}: {
  department: Department
  activities: ActivityType[]
  isExpanded: boolean
  onToggle: () => void
  onEditDepartment: (department: Department) => void
  onDeleteDepartment: (department: Department) => void
  onCreateActivity: (departmentId: string) => void
  onEditActivity: (activityType: ActivityType) => void
}) {
  const [activitiesPage, setActivitiesPage] = useState(1)
  const activitiesTotalPages = pageCount(activities.length)
  const pagedActivities = paginate(activities, activitiesPage)

  return (
    <Fragment>
      <TableRow hover>
        <TableCell sx={{ width: 44, pr: 0 }}>
          <ExpandButton expanded={isExpanded} onClick={onToggle} label={`Actividades de ${department.name}`} />
        </TableCell>
        <TableCell>
          <Typography variant="body2" sx={{ fontWeight: 600 }}>
            {department.name}
          </Typography>
          {department.description && (
            <Typography variant="caption" color="text.secondary">
              {department.description}
            </Typography>
          )}
        </TableCell>
        <TableCell align="right" sx={{ color: 'text.secondary' }}>
          {activities.length} actividad{activities.length === 1 ? '' : 'es'}
        </TableCell>
        <TableCell align="right">
          <Stack direction="row" spacing={0.5} sx={{ justifyContent: 'flex-end' }}>
            <Button size="small" onClick={() => onEditDepartment(department)}>
              Editar
            </Button>
            <Button size="small" color="error" onClick={() => onDeleteDepartment(department)}>
              Eliminar
            </Button>
          </Stack>
        </TableCell>
      </TableRow>

      <TableRow>
        <TableCell colSpan={4} sx={{ p: 0, border: 0 }}>
          <Collapse in={isExpanded} unmountOnExit>
            <Box sx={{ pl: 7, pr: 2, py: 1.5, bgcolor: 'action.hover' }}>
              <Stack direction="row" sx={{ alignItems: 'center', mb: 1 }}>
                <Typography variant="subtitle2" sx={{ flexGrow: 1, fontWeight: 700 }}>
                  Actividades
                </Typography>
                <Button size="small" onClick={() => onCreateActivity(department.id)}>
                  Nueva actividad
                </Button>
              </Stack>

              {activities.length === 0 ? (
                <Typography variant="body2" color="text.secondary">
                  Este departamento todavía no tiene actividades.
                </Typography>
              ) : (
                <>
                  <List dense disablePadding>
                    {pagedActivities.map((activityType) => (
                      <ListItem
                        key={activityType.id}
                        disablePadding
                        sx={{ py: 0.5, justifyContent: 'space-between', gap: 1 }}
                      >
                        <Stack direction="row" spacing={1} sx={{ alignItems: 'center', minWidth: 0 }}>
                          {activityType.color && (
                            <Box
                              sx={{ width: 12, height: 12, borderRadius: '50%', bgcolor: activityType.color, flexShrink: 0 }}
                            />
                          )}
                          <Typography variant="body2" noWrap>
                            {activityType.name}
                          </Typography>
                        </Stack>
                        <Button size="small" onClick={() => onEditActivity(activityType)}>
                          Editar
                        </Button>
                      </ListItem>
                    ))}
                  </List>
                  {activitiesTotalPages > 1 && (
                    <Stack sx={{ pt: 1, alignItems: 'center' }}>
                      <Pagination
                        size="small"
                        count={activitiesTotalPages}
                        page={activitiesPage}
                        onChange={(_, value) => setActivitiesPage(value)}
                      />
                    </Stack>
                  )}
                </>
              )}
            </Box>
          </Collapse>
        </TableCell>
      </TableRow>
    </Fragment>
  )
}

// Fila de empresa: su "+" revela la tabla de departamentos debajo, cada uno
// con el suyo propio para llegar a las actividades.
function CompanyRow({
  company,
  departments,
  activitiesByDepartment,
  rooms,
  isExpanded,
  onToggle,
  expandedDepartmentIds,
  onToggleDepartment,
  onEditCompany,
  onDeleteCompany,
  onCreateDepartment,
  onEditDepartment,
  onDeleteDepartment,
  onCreateActivity,
  onEditActivity,
  onCreateRoom,
  onEditRoom,
  onDeleteRoom,
}: {
  company: Company
  departments: Department[]
  activitiesByDepartment: Map<string, ActivityType[]>
  rooms: Room[]
  isExpanded: boolean
  onToggle: () => void
  expandedDepartmentIds: Set<string>
  onToggleDepartment: (departmentId: string) => void
  onEditCompany: (company: Company) => void
  onDeleteCompany: (company: Company) => void
  onCreateDepartment: (companyId: string) => void
  onEditDepartment: (department: Department) => void
  onDeleteDepartment: (department: Department) => void
  onCreateActivity: (departmentId: string) => void
  onEditActivity: (activityType: ActivityType) => void
  onCreateRoom: (companyId: string) => void
  onEditRoom: (room: Room) => void
  onDeleteRoom: (room: Room) => void
}) {
  const [departmentsPage, setDepartmentsPage] = useState(1)
  const departmentsTotalPages = pageCount(departments.length)
  const pagedDepartments = paginate(departments, departmentsPage)

  const [roomsPage, setRoomsPage] = useState(1)
  const roomsTotalPages = pageCount(rooms.length)
  const pagedRooms = paginate(rooms, roomsPage)

  return (
    <Fragment>
      <TableRow hover>
        <TableCell sx={{ width: 44, pr: 0 }}>
          <ExpandButton expanded={isExpanded} onClick={onToggle} label={`Departamentos de ${company.name}`} />
        </TableCell>
        <TableCell>
          <Typography sx={{ fontWeight: 700 }}>{company.name}</Typography>
        </TableCell>
        <TableCell align="right">
          <Chip size="small" label={`${company.openingTime} – ${company.closingTime}`} />
        </TableCell>
        <TableCell align="right">
          <Stack direction="row" spacing={0.5} sx={{ justifyContent: 'flex-end' }}>
            <Button size="small" onClick={() => onEditCompany(company)}>
              Editar
            </Button>
            <Button size="small" color="error" onClick={() => onDeleteCompany(company)}>
              Eliminar
            </Button>
          </Stack>
        </TableCell>
      </TableRow>

      <TableRow>
        <TableCell colSpan={4} sx={{ p: 0, border: 0 }}>
          <Collapse in={isExpanded} unmountOnExit>
            <Box sx={{ pl: 3, pr: 2, py: 1.5 }}>
              <Stack direction="row" sx={{ alignItems: 'center', mb: 1 }}>
                <Typography variant="subtitle2" sx={{ flexGrow: 1, fontWeight: 700 }}>
                  Departamentos
                </Typography>
                <Button size="small" onClick={() => onCreateDepartment(company.id)}>
                  Nuevo departamento
                </Button>
              </Stack>

              {departments.length === 0 ? (
                <Typography variant="body2" color="text.secondary">
                  Esta empresa todavía no tiene departamentos.
                </Typography>
              ) : (
                <>
                  <TableContainer component={Paper} elevation={0} variant="outlined" sx={{ borderRadius: 2 }}>
                    <Table size="small">
                      <TableBody>
                        {pagedDepartments.map((department) => (
                          <DepartmentRow
                            key={department.id}
                            department={department}
                            activities={activitiesByDepartment.get(department.id) ?? []}
                            isExpanded={expandedDepartmentIds.has(department.id)}
                            onToggle={() => onToggleDepartment(department.id)}
                            onEditDepartment={onEditDepartment}
                            onDeleteDepartment={onDeleteDepartment}
                            onCreateActivity={onCreateActivity}
                            onEditActivity={onEditActivity}
                          />
                        ))}
                      </TableBody>
                    </Table>
                  </TableContainer>
                  {departmentsTotalPages > 1 && (
                    <Stack sx={{ pt: 1, alignItems: 'center' }}>
                      <Pagination
                        size="small"
                        count={departmentsTotalPages}
                        page={departmentsPage}
                        onChange={(_, value) => setDepartmentsPage(value)}
                      />
                    </Stack>
                  )}
                </>
              )}

              <Stack direction="row" sx={{ alignItems: 'center', mt: 3, mb: 1 }}>
                <Typography variant="subtitle2" sx={{ flexGrow: 1, fontWeight: 700 }}>
                  Salas
                </Typography>
                <Button size="small" onClick={() => onCreateRoom(company.id)}>
                  Nueva sala
                </Button>
              </Stack>

              {rooms.length === 0 ? (
                <Typography variant="body2" color="text.secondary">
                  Esta empresa todavía no tiene salas.
                </Typography>
              ) : (
                <>
                  <List dense disablePadding>
                    {pagedRooms.map((room) => (
                      <ListItem key={room.id} disablePadding sx={{ py: 0.5, justifyContent: 'space-between', gap: 1 }}>
                        <Typography variant="body2" noWrap>
                          {room.name}
                        </Typography>
                        <Stack direction="row" spacing={0.5}>
                          <Button size="small" onClick={() => onEditRoom(room)}>
                            Editar
                          </Button>
                          <Button size="small" color="error" onClick={() => onDeleteRoom(room)}>
                            Eliminar
                          </Button>
                        </Stack>
                      </ListItem>
                    ))}
                  </List>
                  {roomsTotalPages > 1 && (
                    <Stack sx={{ pt: 1, alignItems: 'center' }}>
                      <Pagination
                        size="small"
                        count={roomsTotalPages}
                        page={roomsPage}
                        onChange={(_, value) => setRoomsPage(value)}
                      />
                    </Stack>
                  )}
                </>
              )}
            </Box>
          </Collapse>
        </TableCell>
      </TableRow>
    </Fragment>
  )
}

function toggleInSet(set: Set<string>, id: string): Set<string> {
  const next = new Set(set)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  return next
}

export function CompaniesPage() {
  useRequireAdmin()
  const { token } = useAuth()

  const [expandedCompanyIds, setExpandedCompanyIds] = useState<Set<string>>(new Set())
  const [expandedDepartmentIds, setExpandedDepartmentIds] = useState<Set<string>>(new Set())
  const [companiesPage, setCompaniesPage] = useState(1)

  const [isCompanyDialogOpen, setIsCompanyDialogOpen] = useState(false)
  const [editingCompany, setEditingCompany] = useState<Company | null>(null)
  const [companyToDelete, setCompanyToDelete] = useState<Company | null>(null)

  const [isDepartmentDialogOpen, setIsDepartmentDialogOpen] = useState(false)
  const [editingDepartment, setEditingDepartment] = useState<Department | null>(null)
  const [departmentDialogCompanyId, setDepartmentDialogCompanyId] = useState('')
  const [departmentToDelete, setDepartmentToDelete] = useState<Department | null>(null)

  const [isActivityDialogOpen, setIsActivityDialogOpen] = useState(false)
  const [editingActivityType, setEditingActivityType] = useState<ActivityType | null>(null)
  const [activityDialogDepartmentId, setActivityDialogDepartmentId] = useState('')

  const [isRoomDialogOpen, setIsRoomDialogOpen] = useState(false)
  const [editingRoom, setEditingRoom] = useState<Room | null>(null)
  const [roomDialogCompanyId, setRoomDialogCompanyId] = useState('')
  const [roomToDelete, setRoomToDelete] = useState<Room | null>(null)

  const companiesQuery = useQuery({
    queryKey: ['admin-companies'],
    queryFn: () => listCompanies(token!),
    enabled: !!token,
  })

  const departmentsQuery = useQuery({
    queryKey: ['admin-departments'],
    queryFn: () => listDepartments(token!),
    enabled: !!token,
  })

  const activityTypesQuery = useQuery({
    queryKey: ['admin-activity-types'],
    queryFn: () => listActivityTypes(token!),
    enabled: !!token,
  })

  const roomsQuery = useQuery({
    queryKey: ['admin-rooms'],
    queryFn: () => listRooms(token!),
    enabled: !!token,
  })

  const departmentsByCompany = useMemo(() => {
    const map = new Map<string, Department[]>()
    for (const department of departmentsQuery.data ?? []) {
      const key = department.companyId
      map.set(key, [...(map.get(key) ?? []), department])
    }
    return map
  }, [departmentsQuery.data])

  const activitiesByDepartment = useMemo(() => {
    const map = new Map<string, ActivityType[]>()
    for (const activityType of activityTypesQuery.data ?? []) {
      map.set(activityType.departmentId, [...(map.get(activityType.departmentId) ?? []), activityType])
    }
    return map
  }, [activityTypesQuery.data])

  const roomsByCompany = useMemo(() => {
    const map = new Map<string, Room[]>()
    for (const room of roomsQuery.data ?? []) {
      map.set(room.companyId, [...(map.get(room.companyId) ?? []), room])
    }
    return map
  }, [roomsQuery.data])

  const companies = companiesQuery.data ?? []
  const companiesTotalPages = pageCount(companies.length)
  const pagedCompanies = paginate(companies, companiesPage)

  function toggleCompany(companyId: string) {
    setExpandedCompanyIds((current) => toggleInSet(current, companyId))
  }

  function toggleDepartment(departmentId: string) {
    setExpandedDepartmentIds((current) => toggleInSet(current, departmentId))
  }

  function openCreateCompany() {
    setEditingCompany(null)
    setIsCompanyDialogOpen(true)
  }

  function openEditCompany(company: Company) {
    setEditingCompany(company)
    setIsCompanyDialogOpen(true)
  }

  function openDeleteCompany(company: Company) {
    setCompanyToDelete(company)
  }

  function openCreateDepartment(companyId: string) {
    setEditingDepartment(null)
    setDepartmentDialogCompanyId(companyId)
    setIsDepartmentDialogOpen(true)
  }

  function openEditDepartment(department: Department) {
    setEditingDepartment(department)
    setDepartmentDialogCompanyId(department.companyId)
    setIsDepartmentDialogOpen(true)
  }

  function openDeleteDepartment(department: Department) {
    setDepartmentToDelete(department)
  }

  function openCreateActivity(departmentId: string) {
    setEditingActivityType(null)
    setActivityDialogDepartmentId(departmentId)
    setIsActivityDialogOpen(true)
  }

  function openEditActivity(activityType: ActivityType) {
    setEditingActivityType(activityType)
    setActivityDialogDepartmentId(activityType.departmentId)
    setIsActivityDialogOpen(true)
  }

  function openCreateRoom(companyId: string) {
    setEditingRoom(null)
    setRoomDialogCompanyId(companyId)
    setIsRoomDialogOpen(true)
  }

  function openEditRoom(room: Room) {
    setEditingRoom(room)
    setRoomDialogCompanyId(room.companyId)
    setIsRoomDialogOpen(true)
  }

  function openDeleteRoom(room: Room) {
    setRoomToDelete(room)
  }

  const isLoading =
    companiesQuery.isLoading || departmentsQuery.isLoading || activityTypesQuery.isLoading || roomsQuery.isLoading

  return (
    <Box sx={{ minHeight: '100svh', display: 'flex', flexDirection: 'column', bgcolor: 'background.default' }}>
      <Header />

      <Container maxWidth="lg" sx={{ py: 3, flexGrow: 1 }}>
        <Stack direction="row" sx={{ mb: 2, alignItems: 'center' }}>
          <Typography variant="h5" sx={{ flexGrow: 1, fontWeight: 700 }}>
            Empresas
          </Typography>
          <Button variant="contained" onClick={openCreateCompany}>
            Nueva empresa
          </Button>
        </Stack>

        {isLoading && (
          <Stack sx={{ py: 6, alignItems: 'center' }}>
            <CircularProgress size={28} />
          </Stack>
        )}

        {!isLoading && (
          <Stack spacing={3}>
            <Paper elevation={0} variant="outlined" sx={{ borderRadius: 3 }}>
              <TableContainer>
                <Table>
                  <TableBody>
                    {pagedCompanies.map((company) => (
                      <CompanyRow
                        key={company.id}
                        company={company}
                        departments={departmentsByCompany.get(company.id) ?? []}
                        activitiesByDepartment={activitiesByDepartment}
                        rooms={roomsByCompany.get(company.id) ?? []}
                        isExpanded={expandedCompanyIds.has(company.id)}
                        onToggle={() => toggleCompany(company.id)}
                        expandedDepartmentIds={expandedDepartmentIds}
                        onToggleDepartment={toggleDepartment}
                        onEditCompany={openEditCompany}
                        onDeleteCompany={openDeleteCompany}
                        onCreateDepartment={openCreateDepartment}
                        onEditDepartment={openEditDepartment}
                        onDeleteDepartment={openDeleteDepartment}
                        onCreateActivity={openCreateActivity}
                        onEditActivity={openEditActivity}
                        onCreateRoom={openCreateRoom}
                        onEditRoom={openEditRoom}
                        onDeleteRoom={openDeleteRoom}
                      />
                    ))}
                  </TableBody>
                </Table>
              </TableContainer>

              {companies.length === 0 && (
                <Typography color="text.secondary" sx={{ p: 3, textAlign: 'center' }}>
                  Todavía no hay empresas creadas.
                </Typography>
              )}

              {companiesTotalPages > 1 && (
                <Stack sx={{ py: 2, alignItems: 'center' }}>
                  <Pagination
                    count={companiesTotalPages}
                    page={companiesPage}
                    onChange={(_, value) => setCompaniesPage(value)}
                  />
                </Stack>
              )}
            </Paper>
          </Stack>
        )}
      </Container>

      <Footer />

      <CompanyDialog
        open={isCompanyDialogOpen}
        onClose={() => setIsCompanyDialogOpen(false)}
        editingCompany={editingCompany}
        token={token!}
      />

      <DeleteCompanyDialog
        company={companyToDelete}
        departmentCount={companyToDelete ? (departmentsByCompany.get(companyToDelete.id) ?? []).length : 0}
        onClose={() => setCompanyToDelete(null)}
        token={token!}
      />

      <DepartmentDialog
        open={isDepartmentDialogOpen}
        onClose={() => setIsDepartmentDialogOpen(false)}
        editingDepartment={editingDepartment}
        defaultCompanyId={departmentDialogCompanyId}
        companies={companiesQuery.data ?? []}
        token={token!}
      />

      <DeleteDepartmentDialog
        department={departmentToDelete}
        onClose={() => setDepartmentToDelete(null)}
        token={token!}
      />

      <ActivityTypeDialog
        open={isActivityDialogOpen}
        onClose={() => setIsActivityDialogOpen(false)}
        editingActivityType={editingActivityType}
        departmentId={activityDialogDepartmentId}
        token={token!}
      />

      <RoomDialog
        open={isRoomDialogOpen}
        onClose={() => setIsRoomDialogOpen(false)}
        editingRoom={editingRoom}
        companyId={roomDialogCompanyId}
        token={token!}
      />

      <DeleteRoomDialog room={roomToDelete} onClose={() => setRoomToDelete(null)} token={token!} />
    </Box>
  )
}
