import { apiFetch } from './client'

export interface Department {
  id: string
  name: string
  description: string | null
  companyId: string
  companyName: string
}

export interface UserSummary {
  id: string
  email: string
  firstName: string | null
  lastName: string | null
  roles: string[]
  departments: Department[]
  company: { id: string; name: string } | null
  deactivatedAt: string | null
}

export interface UsersPage {
  items: UserSummary[]
  page: number
  limit: number
  totalItems: number
  totalPages: number
}

export interface Role {
  id: string
  name: string
  description: string | null
}

export interface ListUsersFilters {
  email?: string
  departmentId?: string
}

export function listUsers(token: string, page = 1, limit = 20, filters: ListUsersFilters = {}): Promise<UsersPage> {
  const params = new URLSearchParams({ page: String(page), limit: String(limit) })

  if (filters.email) {
    params.set('email', filters.email)
  }
  if (filters.departmentId) {
    params.set('departmentId', filters.departmentId)
  }

  return apiFetch<UsersPage>(`/users?${params.toString()}`, { method: 'GET' }, token)
}

export function listRoles(token: string): Promise<Role[]> {
  return apiFetch<Role[]>('/roles', { method: 'GET' }, token)
}

export interface CreateUserResult {
  id: string
}

// La contraseña la genera el sistema (no la elige quien crea la cuenta): se
// le envía al usuario por email junto con el link de verificación, y queda
// obligado a cambiarla en su primer inicio de sesión.
export function createUser(token: string, email: string): Promise<CreateUserResult> {
  return apiFetch<CreateUserResult>(
    '/register',
    {
      method: 'POST',
      body: JSON.stringify({ email }),
    },
    token,
  )
}

export function assignRoleToUser(token: string, userId: string, roleName: string): Promise<null> {
  return apiFetch<null>(
    `/users/${userId}/roles`,
    {
      method: 'POST',
      body: JSON.stringify({ role: roleName }),
    },
    token,
  )
}

export function revokeRoleFromUser(token: string, userId: string, roleId: string): Promise<null> {
  return apiFetch<null>(`/users/${userId}/roles/${roleId}`, { method: 'DELETE' }, token)
}

export function listDepartments(token: string): Promise<Department[]> {
  return apiFetch<Department[]>('/departments', { method: 'GET' }, token)
}

export function createDepartment(token: string, name: string, companyId: string, description?: string): Promise<Department> {
  return apiFetch<Department>(
    '/departments',
    {
      method: 'POST',
      body: JSON.stringify({ name, companyId, description: description || undefined }),
    },
    token,
  )
}

export function updateDepartment(
  token: string,
  id: string,
  name: string,
  companyId: string,
  description?: string,
): Promise<Department> {
  return apiFetch<Department>(
    `/departments/${id}`,
    {
      method: 'PATCH',
      body: JSON.stringify({ name, companyId, description: description || undefined }),
    },
    token,
  )
}

export function deleteDepartment(token: string, id: string): Promise<null> {
  return apiFetch<null>(`/departments/${id}`, { method: 'DELETE' }, token)
}

export function assignDepartmentToUser(token: string, userId: string, departmentId: string): Promise<null> {
  return apiFetch<null>(
    `/users/${userId}/departments`,
    {
      method: 'POST',
      body: JSON.stringify({ departmentId }),
    },
    token,
  )
}

export function revokeDepartmentFromUser(token: string, userId: string, departmentId: string): Promise<null> {
  return apiFetch<null>(`/users/${userId}/departments/${departmentId}`, { method: 'DELETE' }, token)
}

export interface DeactivateUserResult {
  userId: string
  shiftsCancelled: number
  activitiesReassigned: number
  activitiesSkipped: number
}

// Baja lógica: el usuario no se borra, pero deja de poder iniciar sesión;
// sus turnos pendientes se cancelan y las actividades de sala que tenía
// agendadas pasan a quien ejecuta la baja (con un turno propio que las
// cubre, para que aparezcan en su calendario).
export function deactivateUser(token: string, userId: string): Promise<DeactivateUserResult> {
  return apiFetch<DeactivateUserResult>(`/users/${userId}/deactivate`, { method: 'POST' }, token)
}
