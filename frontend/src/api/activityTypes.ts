import { apiFetch } from './client'

export interface ActivityType {
  id: string
  departmentId: string
  name: string
  color: string | null
}

export function listActivityTypes(token: string, departmentIds?: string[]): Promise<ActivityType[]> {
  const query = departmentIds && departmentIds.length > 0
    ? `?departmentId=${departmentIds.join(',')}`
    : ''

  return apiFetch<ActivityType[]>(`/activity-types${query}`, { method: 'GET' }, token)
}

export function createActivityType(token: string, departmentId: string, name: string, color?: string | null): Promise<ActivityType> {
  return apiFetch<ActivityType>(
    '/activity-types',
    {
      method: 'POST',
      body: JSON.stringify({ departmentId, name, color: color || null }),
    },
    token,
  )
}

export function updateActivityType(token: string, id: string, name: string, color?: string | null): Promise<ActivityType> {
  return apiFetch<ActivityType>(
    `/activity-types/${id}`,
    {
      method: 'PATCH',
      body: JSON.stringify({ name, color: color || null }),
    },
    token,
  )
}
