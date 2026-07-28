export interface ProductImage {
  id: number
  path: string
  is_main: boolean
}

export interface ProductSummary {
  id: number
  name: string
  slug: string
  sku: string
  price: string
  promo_price: string | null
  featured: boolean
  category?: { id: number, name: string, slug: string }
  images: ProductImage[]
  stock_total: number | null
}

export function useFeaturedProducts() {
  const { request } = useApi()

  return useAsyncData<ProductSummary[]>('home-featured', async () => {
    const { products } = await request<{ products: ProductSummary[] }>('/api/products/featured')
    return products
  }, { default: () => [] })
}

export function useLatestProducts() {
  const { request } = useApi()

  return useAsyncData<ProductSummary[]>('home-latest', async () => {
    const { data } = await request<{ data: ProductSummary[] }>('/api/products', {
      params: { sort: 'lancamentos', per_page: 8 },
    })
    return data
  }, { default: () => [] })
}
