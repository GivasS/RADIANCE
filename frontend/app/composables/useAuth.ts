export interface AuthUser {
  id: number
  name: string
  email: string
  cpf: string | null
  phone: string | null
  role: 'customer' | 'admin'
}

/**
 * Usuario logado (ou null). Usado pro icone de conta no header decidir
 * entre "Entrar" e o nome/avatar do cliente.
 */
export function useAuth() {
  const { request } = useApi()

  const { data: user, refresh, status } = useAsyncData<AuthUser | null>('current-user', async () => {
    try {
      const { user } = await request<{ user: AuthUser }>('/api/auth/me')
      return user
    } catch {
      return null
    }
  }, {
    default: () => null,
  })

  const isLoggedIn = computed(() => !!user.value)

  return { user, isLoggedIn, refreshUser: refresh, status }
}
