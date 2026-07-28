export interface Category {
  id: number
  parent_id: number | null
  name: string
  slug: string
  children?: Category[]
}

/**
 * Categorias pro menu principal (COLARES, BRINCOS, ANEIS...), com
 * subcategorias aninhadas. Compartilhado entre paginas (mesma key).
 */
export function useCategories() {
  const { request } = useApi()

  return useAsyncData<Category[]>('nav-categories', async () => {
    const { categories } = await request<{ categories: Category[] }>('/api/categories')
    return categories
  }, {
    default: () => [],
  })
}
