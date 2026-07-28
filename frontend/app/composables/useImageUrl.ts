/** Resolve o path relativo salvo no banco (ex: "products/1/hash.jpg") pra URL publica. */
export function useImageUrl() {
  const config = useRuntimeConfig()

  function imageUrl(path?: string | null): string | undefined {
    if (!path) return undefined
    if (path.startsWith('http')) return path

    return `${config.public.apiBase}/storage/${path}`
  }

  return { imageUrl }
}
