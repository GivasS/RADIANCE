import type { ProductSummary } from '~/composables/useProducts'

interface PaginatedProducts {
  data: ProductSummary[]
  current_page: number
  last_page: number
  total: number
}

export function useProductList(categorySlug: ComputedRef<string | undefined>) {
  const route = useRoute()
  const router = useRouter()
  const { request } = useApi()

  const minPrice = ref(route.query.min_price ? String(route.query.min_price) : '')
  const maxPrice = ref(route.query.max_price ? String(route.query.max_price) : '')
  const sort = ref((route.query.sort as string) || 'relevancia')
  const page = ref(route.query.page ? Number(route.query.page) : 1)

  function syncQuery() {
    router.replace({
      query: {
        ...(minPrice.value ? { min_price: minPrice.value } : {}),
        ...(maxPrice.value ? { max_price: maxPrice.value } : {}),
        ...(sort.value !== 'relevancia' ? { sort: sort.value } : {}),
        ...(page.value > 1 ? { page: String(page.value) } : {}),
      },
    })
  }

  const { data, pending, refresh } = useAsyncData<PaginatedProducts>(
    () => `plp-${categorySlug.value}-${minPrice.value}-${maxPrice.value}-${sort.value}-${page.value}`,
    () => request<PaginatedProducts>('/api/products', {
      params: {
        category: categorySlug.value,
        min_price: minPrice.value || undefined,
        max_price: maxPrice.value || undefined,
        sort: sort.value === 'relevancia' ? undefined : sort.value,
        page: page.value,
      },
    }),
    {
      watch: [categorySlug, minPrice, maxPrice, sort, page],
      default: () => ({ data: [], current_page: 1, last_page: 1, total: 0 }),
    },
  )

  watch([minPrice, maxPrice, sort, page], syncQuery)
  watch([minPrice, maxPrice, sort], () => { page.value = 1 })

  return { products: data, pending, refresh, minPrice, maxPrice, sort, page }
}
