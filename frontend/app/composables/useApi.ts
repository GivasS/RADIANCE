/**
 * Wrapper de fetch pra API do backend. Repassa cookies no SSR (a sessao do
 * Sanctum e HttpOnly - sem isso, requisicoes no servidor iriam sem login).
 */
export function useApi() {
  const config = useRuntimeConfig()

  function request<T>(path: string, options: Record<string, any> = {}): Promise<T> {
    // No SSR precisa repassar o cookie E simular o Origin - sem Origin o
    // Sanctum nao reconhece a chamada como "stateful" e a sessao nao vale.
    const forwardedHeaders = import.meta.server
      ? { ...useRequestHeaders(['cookie']), origin: config.public.siteUrl }
      : {}

    return $fetch<T>(path, {
      baseURL: config.public.apiBase,
      credentials: 'include',
      ...options,
      headers: {
        ...forwardedHeaders,
        ...(options.headers || {}),
      },
    })
  }

  /**
   * Pra POST/PUT/DELETE: garante o cookie CSRF do Sanctum e manda o
   * X-XSRF-TOKEN. So roda no cliente (mutacao nao acontece durante SSR).
   */
  async function mutate<T>(path: string, options: Record<string, any> = {}): Promise<T> {
    const xsrf = useCookie('XSRF-TOKEN')

    if (!xsrf.value) {
      await $fetch('/sanctum/csrf-cookie', { baseURL: config.public.apiBase, credentials: 'include' })
    }

    return request<T>(path, {
      ...options,
      headers: {
        'X-XSRF-TOKEN': xsrf.value ? decodeURIComponent(xsrf.value) : '',
        ...(options.headers || {}),
      },
    })
  }

  return { request, mutate }
}
