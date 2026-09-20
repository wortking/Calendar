const API_URL = import.meta.env.VITE_API_URL

export interface ApiEnvelope<T> {
  success: boolean
  data: T | null
  message: string
}

export class ApiError extends Error {
  readonly status: number
  readonly retryAfterSeconds?: number

  constructor(message: string, status: number, retryAfterSeconds?: number) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.retryAfterSeconds = retryAfterSeconds
  }
}

interface AuthTokens {
  token: string
  refreshToken: string
}

interface AuthHandlers {
  getRefreshToken: () => string | null
  onTokensRefreshed: (tokens: AuthTokens) => void
  onAuthExpired: () => void
}

// La AuthContext se registra acá al montar la app. client.ts no importa
// auth.ts/AuthContext.tsx directamente para evitar una dependencia circular
// (auth.ts ya importa apiFetch de este archivo).
let authHandlers: AuthHandlers | null = null

export function registerAuthHandlers(handlers: AuthHandlers): void {
  authHandlers = handlers
}

async function rawRequest<T>(path: string, options: RequestInit, token?: string): Promise<T> {
  const headers: Record<string, string> = {
    ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
    ...(options.headers as Record<string, string> | undefined),
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  let response: Response
  try {
    response = await fetch(`${API_URL}${path}`, { ...options, headers })
  } catch {
    throw new ApiError('No se pudo conectar con el servidor.', 0)
  }

  const envelope = (await response.json()) as ApiEnvelope<T>

  if (!response.ok || !envelope.success) {
    const retryAfterHeader = response.headers.get('Retry-After')
    throw new ApiError(
      envelope.message || 'Ocurrió un error inesperado.',
      response.status,
      retryAfterHeader ? Number(retryAfterHeader) : undefined,
    )
  }

  return envelope.data as T
}

// Deduplica refreshes concurrentes: si varias llamadas reciben 401 al mismo
// tiempo, todas esperan el mismo refresh en curso en vez de disparar N.
let refreshInFlight: Promise<string | null> | null = null

async function refreshAccessTokenOnce(): Promise<string | null> {
  if (!authHandlers) {
    return null
  }

  const storedRefreshToken = authHandlers.getRefreshToken()
  if (!storedRefreshToken) {
    return null
  }

  if (!refreshInFlight) {
    refreshInFlight = rawRequest<AuthTokens>('/token/refresh', {
      method: 'POST',
      body: JSON.stringify({ refreshToken: storedRefreshToken }),
    })
      .then((tokens) => {
        authHandlers?.onTokensRefreshed(tokens)
        return tokens.token
      })
      .catch(() => {
        authHandlers?.onAuthExpired()
        return null
      })
      .finally(() => {
        refreshInFlight = null
      })
  }

  return refreshInFlight
}

const AUTH_ENDPOINTS = new Set(['/login_check', '/token/refresh'])

export async function apiFetch<T>(
  path: string,
  options: RequestInit = {},
  token?: string,
  skipAuthRetry = false,
): Promise<T> {
  try {
    return await rawRequest<T>(path, options, token)
  } catch (err) {
    const canRetryWithRefresh = err instanceof ApiError && err.status === 401 && token && !skipAuthRetry && !AUTH_ENDPOINTS.has(path)

    if (canRetryWithRefresh) {
      const newToken = await refreshAccessTokenOnce()
      if (newToken) {
        return rawRequest<T>(path, options, newToken)
      }
    }

    throw err
  }
}
