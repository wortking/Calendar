import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { fetchMe, login as loginRequest, logout as logoutRequest, refreshAccessToken, type Me } from '../api/auth'
import { ApiError, registerAuthHandlers } from '../api/client'

const REFRESH_TOKEN_KEY = 'refreshToken'
const CACHED_USER_KEY = 'cachedUser'

function readCachedUser(): Me | null {
  try {
    const raw = localStorage.getItem(CACHED_USER_KEY)
    return raw ? (JSON.parse(raw) as Me) : null
  } catch {
    return null
  }
}

function writeCachedUser(user: Me): void {
  localStorage.setItem(CACHED_USER_KEY, JSON.stringify(user))
}

interface AuthContextValue {
  token: string | null
  user: Me | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string) => Promise<Me>
  logout: () => Promise<void>
  refreshUser: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [token, setToken] = useState<string | null>(null)
  // Se hidrata con lo último que sabíamos del usuario (localStorage) para
  // poder mostrar la UI ya logueada en el primer render, en vez de esperar
  // a que vuelvan el refresh del token y el /me. Si ese refresh falla, se
  // limpia más abajo.
  const [user, setUser] = useState<Me | null>(() => readCachedUser())
  const [isLoading, setIsLoading] = useState(true)

  // Registra en client.ts cómo obtener/actualizar el refresh token y qué
  // hacer si expira del todo, para que el interceptor 401→refresh→retry de
  // apiFetch funcione en cualquier llamada, no solo al montar la app.
  useEffect(() => {
    registerAuthHandlers({
      getRefreshToken: () => localStorage.getItem(REFRESH_TOKEN_KEY),
      onTokensRefreshed: (tokens) => {
        localStorage.setItem(REFRESH_TOKEN_KEY, tokens.refreshToken)
        setToken(tokens.token)
      },
      onAuthExpired: () => {
        localStorage.removeItem(REFRESH_TOKEN_KEY)
        localStorage.removeItem(CACHED_USER_KEY)
        setToken(null)
        setUser(null)
      },
    })
  }, [])

  // Al cargar la app, si hay un refresh token guardado se intenta canjear
  // por un access token nuevo, para no forzar un login en cada recarga.
  useEffect(() => {
    const storedRefreshToken = localStorage.getItem(REFRESH_TOKEN_KEY)

    if (!storedRefreshToken) {
      setUser(null)
      localStorage.removeItem(CACHED_USER_KEY)
      setIsLoading(false)
      return
    }

    refreshAccessToken(storedRefreshToken)
      .then(async (tokens) => {
        localStorage.setItem(REFRESH_TOKEN_KEY, tokens.refreshToken)
        setToken(tokens.token)
        const me = await fetchMe(tokens.token)
        setUser(me)
        writeCachedUser(me)
      })
      .catch((err: unknown) => {
        // Solo tiramos la sesión guardada si el servidor la rechazó de
        // verdad (status !== 0). Un error de red no debe borrar
        // credenciales todavía válidas.
        if (err instanceof ApiError && err.status !== 0) {
          localStorage.removeItem(REFRESH_TOKEN_KEY)
          localStorage.removeItem(CACHED_USER_KEY)
          setUser(null)
        }
      })
      .finally(() => setIsLoading(false))
  }, [])

  async function login(email: string, password: string): Promise<Me> {
    const tokens = await loginRequest(email, password)
    localStorage.setItem(REFRESH_TOKEN_KEY, tokens.refreshToken)
    setToken(tokens.token)
    const me = await fetchMe(tokens.token)
    setUser(me)
    writeCachedUser(me)
    return me
  }

  async function logout() {
    const storedRefreshToken = localStorage.getItem(REFRESH_TOKEN_KEY)

    if (token) {
      try {
        await logoutRequest(token, storedRefreshToken)
      } catch {
        // Best-effort: si el logout en el servidor falla igual limpiamos la sesión local.
      }
    }

    localStorage.removeItem(REFRESH_TOKEN_KEY)
    localStorage.removeItem(CACHED_USER_KEY)
    setToken(null)
    setUser(null)
  }

  async function refreshUser() {
    if (!token) {
      return
    }

    const me = await fetchMe(token)
    setUser(me)
    writeCachedUser(me)
  }

  return (
    <AuthContext.Provider value={{ token, user, isAuthenticated: user !== null, isLoading, login, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth debe usarse dentro de <AuthProvider>')
  }

  return context
}
