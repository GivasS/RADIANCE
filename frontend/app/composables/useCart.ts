interface CartItem {
  id: number
  quantity: number
}

interface CartPayload {
  id: number
  items: CartItem[]
  subtotal: number
  has_unavailable: boolean
}

/**
 * Carrinho atual (funciona logado ou anonimo - o backend resolve via cookie).
 * Usado pro badge de contagem no header.
 */
export function useCart() {
  const { request } = useApi()

  const { data, refresh, pending } = useAsyncData<CartPayload>('current-cart', () =>
    request<CartPayload>('/api/cart'), {
    default: () => ({ id: 0, items: [], subtotal: 0, has_unavailable: false }),
  })

  const itemCount = computed(() => data.value?.items?.reduce((sum, i) => sum + i.quantity, 0) ?? 0)

  return { cart: data, itemCount, refreshCart: refresh, pending }
}
