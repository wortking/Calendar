import { apiFetch } from './client'

export interface Room {
  id: string
  companyId: string
  name: string
}

export function listRooms(token: string, companyId?: string): Promise<Room[]> {
  const query = companyId ? `?companyId=${companyId}` : ''
  return apiFetch<Room[]>(`/rooms${query}`, { method: 'GET' }, token)
}

export function createRoom(token: string, companyId: string, name: string): Promise<Room> {
  return apiFetch<Room>(
    '/rooms',
    {
      method: 'POST',
      body: JSON.stringify({ companyId, name }),
    },
    token,
  )
}

export function updateRoom(token: string, id: string, name: string): Promise<Room> {
  return apiFetch<Room>(
    `/rooms/${id}`,
    {
      method: 'PATCH',
      body: JSON.stringify({ name }),
    },
    token,
  )
}

export function deleteRoom(token: string, id: string): Promise<null> {
  return apiFetch<null>(`/rooms/${id}`, { method: 'DELETE' }, token)
}
