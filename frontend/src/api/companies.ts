import { apiFetch } from './client'

export interface Company {
  id: string
  name: string
  openingTime: string
  closingTime: string
}

export function listCompanies(token: string): Promise<Company[]> {
  return apiFetch<Company[]>('/companies', { method: 'GET' }, token)
}

export function createCompany(token: string, name: string, openingTime: string, closingTime: string): Promise<Company> {
  return apiFetch<Company>(
    '/companies',
    {
      method: 'POST',
      body: JSON.stringify({ name, openingTime, closingTime }),
    },
    token,
  )
}

export function updateCompany(token: string, id: string, name: string, openingTime: string, closingTime: string): Promise<Company> {
  return apiFetch<Company>(
    `/companies/${id}`,
    {
      method: 'PATCH',
      body: JSON.stringify({ name, openingTime, closingTime }),
    },
    token,
  )
}

export function deleteCompany(token: string, id: string): Promise<null> {
  return apiFetch<null>(`/companies/${id}`, { method: 'DELETE' }, token)
}
