<script setup lang="ts">
import type { Category } from '~/composables/useCategories'

const route = useRoute()
const { request } = useApi()

const slug = computed(() => route.params.slug as string)

const { data: category } = await useAsyncData<Category | null>(
  () => `category-${slug.value}`,
  () => request<{ category: Category }>(`/api/categories/${slug.value}`).then(r => r.category).catch(() => null),
  { watch: [slug] },
)

const { products, pending, minPrice, maxPrice, sort, page } = useProductList(slug)

const sortOptions = [
  { value: 'relevancia', label: 'Relevância' },
  { value: 'menor_preco', label: 'Menor preço' },
  { value: 'maior_preco', label: 'Maior preço' },
  { value: 'lancamentos', label: 'Lançamentos' },
]

useHead({ title: () => `${category.value?.name ?? 'Categoria'} — Radiance` })
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <!-- Breadcrumb -->
    <nav class="mb-4 text-xs text-neutral-400">
      <NuxtLink to="/" class="hover:text-brand-600">Início</NuxtLink>
      <span class="mx-1">/</span>
      <span class="text-neutral-600">{{ category?.name ?? '...' }}</span>
    </nav>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="font-display text-2xl text-brand-700 sm:text-3xl">{{ category?.name }}</h1>
        <p class="mt-1 text-sm text-neutral-400">{{ products?.total ?? 0 }} produto(s)</p>
      </div>

      <select
        v-model="sort"
        class="rounded-lg border border-brand-100 px-3 py-2 text-sm text-neutral-600 focus:border-brand-400 focus:outline-none"
      >
        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
          Ordenar: {{ opt.label }}
        </option>
      </select>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
      <!-- Sidebar de filtros -->
      <aside class="space-y-6">
        <div v-if="category?.children?.length">
          <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-neutral-700">Subcategorias</h3>
          <ul class="space-y-1">
            <li v-for="child in category.children" :key="child.id">
              <NuxtLink :to="`/categoria/${child.slug}`" class="text-sm text-neutral-500 hover:text-brand-600">
                {{ child.name }}
              </NuxtLink>
            </li>
          </ul>
        </div>

        <div>
          <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-neutral-700">Preço</h3>
          <div class="flex items-center gap-2">
            <input
              v-model="minPrice"
              type="number"
              placeholder="Mín"
              class="w-full rounded-lg border border-brand-100 px-2 py-1.5 text-sm focus:border-brand-400 focus:outline-none"
            >
            <span class="text-neutral-300">—</span>
            <input
              v-model="maxPrice"
              type="number"
              placeholder="Máx"
              class="w-full rounded-lg border border-brand-100 px-2 py-1.5 text-sm focus:border-brand-400 focus:outline-none"
            >
          </div>
        </div>
      </aside>

      <!-- Grid de produtos -->
      <div>
        <div v-if="pending" class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
          <ProductCardSkeleton v-for="n in 8" :key="n" />
        </div>

        <div v-else-if="!products?.data?.length" class="rounded-xl border border-brand-50 bg-brand-50/30 py-16 text-center">
          <p class="text-neutral-500">Nenhum produto encontrado nessa categoria.</p>
        </div>

        <div v-else class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
          <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
        </div>

        <!-- Paginacao -->
        <div v-if="products && products.last_page > 1" class="mt-10 flex items-center justify-center gap-2">
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
      </div>
    </div>
  </div>
</template>
