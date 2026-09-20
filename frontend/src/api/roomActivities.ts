import { apiFetch } from './client'

export interface RoomActivity {
  id: string
  roomId: string
  activityTypeId: string
  activityTypeName: string | null
  activityTypeColor: string | null
  userId: string
  userEmail: string
  userName: string | null
  startAt: string
  endAt: string
}

export interface EligibleUser {
  id: string
  email: string
  name: string | null
}

export function listRoomActivities(token: string, roomId: string, fromIso: string, toIso: string): Promise<RoomActivity[]> {
  return apiFetch<RoomActivity[]>(
    `/rooms/${roomId}/activities?from=${encodeURIComponent(fromIso)}&to=${encodeURIComponent(toIso)}`,
    { method: 'GET' },
    token,
  )
}

export function listEligibleUsers(
  token: string,
  roomId: string,
  startAtIso: string,
  endAtIso: string,
  excludeRoomActivityId?: string,
): Promise<EligibleUser[]> {
  const params = new URLSearchParams({ startAt: startAtIso, endAt: endAtIso })
  if (excludeRoomActivityId) {
    params.set('excludeRoomActivityId', excludeRoomActivityId)
  }

  return apiFetch<EligibleUser[]>(`/rooms/${roomId}/eligible-users?${params.toString()}`, { method: 'GET' }, token)
}

export function createRoomActivity(
  token: string,
  roomId: string,
  activityTypeId: string,
  userId: string,
  startAtIso: string,
  endAtIso: string,
): Promise<RoomActivity> {
  return apiFetch<RoomActivity>(
    `/rooms/${roomId}/activities`,
    {
      method: 'POST',
      body: JSON.stringify({ activityTypeId, userId, startAt: startAtIso, endAt: endAtIso }),
    },
    token,
  )
}

export function updateRoomActivity(
  token: string,
  id: string,
  roomId: string,
  activityTypeId: string,
  userId: string,
  startAtIso: string,
  endAtIso: string,
): Promise<RoomActivity> {
  return apiFetch<RoomActivity>(
    `/room-activities/${id}`,
    {
      method: 'PATCH',
      body: JSON.stringify({ roomId, activityTypeId, userId, startAt: startAtIso, endAt: endAtIso }),
    },
    token,
  )
}

export function deleteRoomActivity(token: string, id: string): Promise<null> {
  return apiFetch<null>(`/room-activities/${id}`, { method: 'DELETE' }, token)
}
