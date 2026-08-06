interface Toast {
  id: number
  type: 'success' | 'error'
  message: string
}

let nextId = 0

/** Toasts globais do admin — usa useState pra ficar isolado por request no SSR. */
export function useToast() {
  const toasts = useState<Toast[]>('admin-toasts', () => [])

  function push(type: Toast['type'], message: string) {
    const id = nextId++
    toasts.value.push({ id, type, message })
    setTimeout(() => dismiss(id), 6000)
  }

  function dismiss(id: number) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  return {
    toasts,
    dismiss,
    success: (message: string) => push('success', message),
    error: (message: string) => push('error', message),
  }
}

/** Traduz erros de $fetch (nginx, Laravel, rede) numa mensagem legível pro usuário. */
export function apiErrorMessage(e: any, fallback = 'Ocorreu um erro. Tente novamente.'): string {
  const status = e?.response?.status ?? e?.statusCode
  const data = e?.response?._data ?? e?.data

  if (!e?.response) return 'Falha de conexão com o servidor. Verifique sua internet e tente novamente.'
  if (status === 413) return 'Arquivo muito grande para envio.'
  if (status === 401 || status === 419) return 'Sessão expirada. Faça login novamente.'
  if (status === 403) return 'Você não tem permissão para fazer isso.'
  if (status === 404) return 'Registro não encontrado (pode já ter sido removido).'
  if (status === 422 && data?.errors) {
    const first = Object.values(data.errors)[0]
    return Array.isArray(first) ? String(first[0]) : String(first ?? fallback)
  }
  if (typeof data?.message === 'string' && data.message) return data.message
  if (status >= 500) return 'Erro no servidor. Tente novamente em instantes.'
  return fallback
}
