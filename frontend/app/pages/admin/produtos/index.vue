<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

interface ProductRow {
  id: number
  name: string
  sku: string
  price: string
  category_id: number
  active: boolean
  category?: { name: string }
  images: { path: string, is_main: boolean }[]
  stock_total: number | null
}

const { request, mutate } = useApi()
const { imageUrl } = useImageUrl()

const search = ref('')
const page = ref(1)

const { data: products, refresh, pending } = await useAsyncData(
  () => `admin-products-${search.value}-${page.value}`,
  () => request<{ data: ProductRow[], current_page: number, last_page: number }>('/api/admin/products', {
    params: { search: search.value || undefined, page: page.value },
  }),
  { watch: [search, page] },
)

const { success, error: toastError } = useToast()

async function toggleActive(product: ProductRow) {
  try {
    await mutate(`/api/admin/products/${product.id}`, {
      method: 'PUT',
      body: { active: !product.active, name: product.name, category_id: product.category_id, sku: product.sku, price: product.price },
    })
    await refresh()
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível alterar o status do produto.'))
  }
}

async function destroyProduct(product: ProductRow) {
  if (!confirm(`Mover "${product.name}" para a lixeira?`)) return
  try {
    await mutate(`/api/admin/products/${product.id}`, { method: 'DELETE' })
    await refresh()
    success('Produto movido para a lixeira.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível excluir o produto.'))
  }
}

function formatCurrency(v: string) {
  return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function mainImage(p: ProductRow) {
  const img = p.images?.find(i => i.is_main) ?? p.images?.[0]
  return imageUrl(img?.path)
}

useHead({ title: 'Produtos — Admin Radiance' })
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold text-neutral-800">Produtos</h1>
      <div class="flex gap-2">
        <NuxtLink to="/admin/produtos/lixeira" class="rounded-lg border border-neutral-200 px-4 py-2 text-sm text-neutral-500 hover:bg-neutral-50">
          Lixeira
        </NuxtLink>
        <NuxtLink to="/admin/produtos/novo" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white hover:bg-brand-700">
          + Novo produto
        </NuxtLink>
      </div>
    </div>

    <input
      v-model="search"
      type="search"
      placeholder="Buscar por nome ou SKU..."
      class="mb-4 w-full max-w-sm rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none"
    >

    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase text-neutral-400">
            <th class="p-3">Produto</th>
            <th class="p-3">SKU</th>
            <th class="p-3">Categoria</th>
            <th class="p-3">Preço</th>
            <th class="p-3">Estoque</th>
            <th class="p-3">Status</th>
            <th class="p-3" />
          </tr>
        </thead>
        <tbody>
          <tr v-if="pending"><td colspan="7" class="p-6 text-center text-neutral-400">Carregando...</td></tr>
          <tr v-else-if="!products?.data?.length"><td colspan="7" class="p-6 text-center text-neutral-400">Nenhum produto encontrado.</td></tr>
          <tr v-for="product in products?.data" :key="product.id" class="border-b border-neutral-50">
            <td class="flex items-center gap-3 p-3">
              <div class="h-10 w-10 flex-none overflow-hidden rounded bg-neutral-100">
                <img v-if="mainImage(product)" :src="mainImage(product)" class="h-full w-full object-cover">
              </div>
              <NuxtLink :to="`/admin/produtos/${product.id}`" class="font-medium text-neutral-800 hover:text-brand-600">{{ product.name }}</NuxtLink>
            </td>
            <td class="p-3 text-neutral-500">{{ product.sku }}</td>
            <td class="p-3 text-neutral-500">{{ product.category?.name }}</td>
            <td class="p-3 text-neutral-600">{{ formatCurrency(product.price) }}</td>
            <td class="p-3" :class="(product.stock_total ?? 0) === 0 ? 'text-red-600' : 'text-neutral-600'">{{ product.stock_total ?? 0 }}</td>
            <td class="p-3">
              <button
                type="button"
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="product.active ? 'bg-accent-100 text-accent-700' : 'bg-neutral-100 text-neutral-500'"
                @click="toggleActive(product)"
              >
                {{ product.active ? 'Ativo' : 'Inativo' }}
              </button>
            </td>
            <td class="p-3 text-right">
              <NuxtLink :to="`/admin/produtos/${product.id}`" class="mr-3 text-brand-600 hover:underline">Editar</NuxtLink>
              <button type="button" class="text-neutral-400 hover:text-red-600" @click="destroyProduct(product)">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="products && products.last_page > 1" class="mt-4 flex justify-center gap-2">
      <button :disabled="page <= 1" class="rounded border border-neutral-200 px-3 py-1 text-sm disabled:opacity-30" @click="page--">Anterior</button>
      <span class="text-sm text-neutral-500">{{ products.current_page }} / {{ products.last_page }}</span>
      <button :disabled="page >= products.last_page" class="rounded border border-neutral-200 px-3 py-1 text-sm disabled:opacity-30" @click="page++">Próxima</button>
    </div>
  </div>
</template>
