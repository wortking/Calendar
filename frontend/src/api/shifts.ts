import { apiFetch } from './client'

const API_URL = import.meta.env.VITE_API_URL

export interface RoomActivityOfShift {
  id: string
  roomId: string
  roomName: string | null
  activityTypeId: string
  activityTypeName: string | null
  activityTypeColor: string | null
  startAt: string
  endAt: string
}

export interface Shift {
  id: string
  startAt: string
  endAt: string
  roomActivities: RoomActivityOfShift[]
}

export interface ShiftWithUser extends Shift {
  userId: string
  userEmail: string
  userName: string | null
  avatarUrl: string | null
}

export function listMyShifts(token: string, startIso: string, endIso: string): Promise<Shift[]> {
  return apiFetch<Shift[]>(`/me/shifts?start=${encodeURIComponent(startIso)}&end=${encodeURIComponent(endIso)}`, { method: 'GET' }, token)
}

export function listShiftsForUser(token: string, userId: string, startIso: string, endIso: string): Promise<Shift[]> {
  return apiFetch<Shift[]>(
    `/users/${userId}/shifts?start=${encodeURIComponent(startIso)}&end=${encodeURIComponent(endIso)}`,
    { method: 'GET' },
    token,
  )
}

export function listShiftsForDate(token: string, dateIso: string): Promise<ShiftWithUser[]> {
  return apiFetch<ShiftWithUser[]>(`/shifts?date=${encodeURIComponent(dateIso)}`, { method: 'GET' }, token)
}

export function listDepartmentShifts(token: string, startIso: string, endIso: string): Promise<ShiftWithUser[]> {
  return apiFetch<ShiftWithUser[]>(
    `/me/department-shifts?start=${encodeURIComponent(startIso)}&end=${encodeURIComponent(endIso)}`,
    { method: 'GET' },
    token,
  )
}

export function createShift(token: string, userId: string, startAtIso: string, endAtIso: string): Promise<Shift> {
  return apiFetch<Shift>(
    `/users/${userId}/shifts`,
    {
      method: 'POST',
      body: JSON.stringify({ startAt: startAtIso, endAt: endAtIso }),
    },
    token,
  )
}

export function deleteShift(token: string, userId: string, shiftId: string): Promise<null> {
  return apiFetch<null>(`/users/${userId}/shifts/${shiftId}`, { method: 'DELETE' }, token)
}

export function updateShift(token: string, shiftId: string, userId: string, startAtIso: string, endAtIso: string): Promise<Shift> {
  return apiFetch<Shift>(
    `/shifts/${shiftId}`,
    {
      method: 'PATCH',
      body: JSON.stringify({ userId, startAt: startAtIso, endAt: endAtIso }),
    },
    token,
  )
}

export interface CoverShiftResult {
  originalShiftId: string
  originalShiftEndAt: string
  newShiftId: string
  newShiftUserId: string
  newShiftStartAt: string
  newShiftEndAt: string
  activitiesReassigned: number
  activitiesSkipped: number
}

// Corta el turno a "cutoffAtIso" y crea uno nuevo para "replacementUserId"
// con lo que queda, reasignándole las actividades de sala que hubieran
// quedado sin turno (las que ya no puede tomar por conflicto de horario se
// omiten, sin fallar la operación completa).
export function coverShift(token: string, shiftId: string, cutoffAtIso: string, replacementUserId: string): Promise<CoverShiftResult> {
  return apiFetch<CoverShiftResult>(
    `/shifts/${shiftId}/cover`,
    {
      method: 'POST',
      body: JSON.stringify({ cutoffAt: cutoffAtIso, replacementUserId }),
    },
    token,
  )
}

export interface CopyWeekResult {
  copied: number
  skipped: number
  activitiesCopied: number
  activitiesSkipped: number
}

// weekStartIso: primer día (lunes) de la semana destino; se copia la
// semana de los 7 días previos. includeRoomActivities copia también las
// actividades de sala agendadas esos días (se omiten si la sala/el
// empleado ya están ocupados en el destino, o si el turno correspondiente
// no se pudo copiar).
export function copyPreviousWeekShifts(token: string, weekStartIso: string, includeRoomActivities = false): Promise<CopyWeekResult> {
  return apiFetch<CopyWeekResult>(
    '/shifts/copy-previous-week',
    {
      method: 'POST',
      body: JSON.stringify({ weekStart: weekStartIso, includeRoomActivities }),
    },
    token,
  )
}

export interface ImportShiftsRowError {
  row: number
  message: string
}

export interface ImportShiftsResult {
  created: number
  errors: ImportShiftsRowError[]
}

export function importShifts(token: string, file: File): Promise<ImportShiftsResult> {
  const formData = new FormData()
  formData.append('file', file)

  return apiFetch<ImportShiftsResult>(
    '/shifts/import',
    {
      method: 'POST',
      body: formData,
    },
    token,
  )
}

// No usa apiFetch: la respuesta es el binario del .xlsx, no el sobre JSON
// {success,data,message} que apiFetch espera parsear.
export async function downloadShiftsImportTemplate(token: string): Promise<Blob> {
  const response = await fetch(`${API_URL}/shifts/import-template`, {
    headers: { Authorization: `Bearer ${token}` },
  })

  if (!response.ok) {
    throw new Error('No se pudo descargar la plantilla.')
  }

  return response.blob()
}
