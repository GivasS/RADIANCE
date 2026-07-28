<script setup lang="ts">
import { Search } from 'lucide-vue-next'
import type { ProductSummary } from '~/composables/useProducts'

interface PaginatedProducts {
  data: ProductSummary[]
  current_page: number
  last_page: number
  total: number
}

const route = useRoute()
const router = useRouter()
const { request } = useApi()

const term = computed(() => (route.query.q as string) ?? '')
const searchInput = ref(term.value)
const page = ref(route.query.page ? Number(route.query.page) : 1)

watch(term, (value) => { searchInput.value = value; page.value = 1 })

function submitSearch() {
  router.push({ path: '/busca', query: { q: searchInput.value } })
}

const { data: products, pending } = useAsyncData<PaginatedProducts>(
  () => `busca-${term.value}-${page.value}`,
  () => term.value
    ? request<PaginatedProducts>('/api/products/search', { params: { q: term.value, page: page.value } })
    : Promise.resolve({ data: [], current_page: 1, last_page: 1, total: 0 }),
  {
    watch: [term, page],
    default: () => ({ data: [], current_page: 1, last_page: 1, total: 0 }),
  },
)

useHead({ title: () => term.value ? `Busca: ${term.value} — Radiance` : 'Busca — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <nav class="mb-4 text-xs text-neutral-400">
      <NuxtLink to="/" class="hover:text-brand-600">Início</NuxtLink>
      <span class="mx-1">/</span>
      <span class="text-neutral-600">Busca</span>
    </nav>

    <form class="relative mb-8 max-w-md" @submit.prevent="submitSearch">
      <input
        v-model="searchInput"
        type="search"
        placeholder="Buscar joias..."
        class="w-full rounded-full border border-brand-100 bg-brand-50/50 py-2.5 pl-4 pr-10 text-sm text-neutral-700 placeholder:text-neutral-400 focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
      >
      <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-brand-500" aria-label="Buscar">
        <Search :size="18" />
      </button>
    </form>

    <div v-if="term" class="mb-6">
      <h1 class="font-display text-2xl text-brand-700 sm:text-3xl">Resultados para "{{ term }}"</h1>
      <p class="mt-1 text-sm text-neutral-400">{{ products?.total ?? 0 }} produto(s) encontrado(s)</p>
    </div>

    <div v-if="!term" class="rounded-xl border border-brand-50 bg-brand-50/30 py-16 text-center">
      <p class="text-neutral-500">Digite algo acima pra buscar joias.</p>
    </div>

    <div v-else-if="pending" class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
      <ProductCardSkeleton v-for="n in 8" :key="n" />
    </div>

    <div v-else-if="!products?.data?.length" class="rounded-xl border border-brand-50 bg-brand-50/30 py-16 text-center">
      <p class="text-neutral-500">Nenhum produto encontrado para "{{ term }}".</p>
      <p class="mt-1 text-sm text-neutral-400">Tente buscar por outro termo.</p>
    </div>

    <template v-else>
      <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
        <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
      </div>

      <div v-if="products.last_page > 1" class="mt-10 flex items-center justify-center gap-2">
        <button
          :disabled="page <= 1"
          class="rounded-full border border-brand-100 px-4 py-1.5 text-sm text-brand-600 disabled:opacity-30"
          @click="page--"
        >
          Anterior
        </button>
        <span class="text-sm text-neutral-500">{{ products.current_page }} / {{ products.last_page }}</span>
        <button
          :disabled="page >= products.last_page"
          class="rounded-full border border-brand-100 px-4 py-1.5 text-sm text-brand-600 disabled:opacity-30"
          @click="page++"
        >
          Próxima
        </button>
      </div>
    </template>
  </div>
</template>
