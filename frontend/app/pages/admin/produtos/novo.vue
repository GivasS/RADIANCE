<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

const { request, mutate } = useApi()
const router = useRouter()

const { data: categories } = await useAsyncData('admin-new-product-categories', async () => {
  const { categories } = await request<{ categories: any[] }>('/api/categories')
  return categories.flatMap(c => [c, ...(c.children ?? [])])
})

const form = reactive({
  category_id: '',
  name: '',
  sku: '',
  price: '',
  promo_price: '',
  weight_grams: '',
  short_description: '',
  description: '',
  active: true,
  featured: false,
})

const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})

const { success, error: toastError } = useToast()

async function submit() {
  submitting.value = true
  errors.value = {}
  try {
    const { product } = await mutate<{ product: { id: number } }>('/api/admin/products', { method: 'POST', body: form })
    success('Produto criado! Agora adicione imagens e variações.')
    router.push(`/admin/produtos/${product.id}`)
  } catch (e: any) {
    errors.value = e?.response?._data?.errors ?? {}
    toastError(apiErrorMessage(e, 'Não foi possível criar o produto.'))
  } finally {
    submitting.value = false
  }
}

useHead({ title: 'Novo Produto — Admin Radiance' })
</script>

<template>
  <div class="max-w-2xl">
    <h1 class="mb-6 text-xl font-semibold text-neutral-800">Novo Produto</h1>

    <form class="space-y-4 rounded-xl border border-neutral-200 bg-white p-6" @submit.prevent="submit">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Nome</label>
          <input v-model="form.name" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
          <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name[0] }}</p>
          <p v-if="errors.slug" class="mt-1 text-xs text-red-600">Já existe um produto com esse nome: {{ errors.slug[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">SKU</label>
          <input v-model="form.sku" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
          <p v-if="errors.sku" class="mt-1 text-xs text-red-600">{{ errors.sku[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Categoria</label>
          <select v-model="form.category_id" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
            <option value="" disabled>Selecione</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <p v-if="errors.category_id" class="mt-1 text-xs text-red-600">{{ errors.category_id[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Preço</label>
          <input v-model="form.price" type="number" step="0.01" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
          <p v-if="errors.price" class="mt-1 text-xs text-red-600">{{ errors.price[0] }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Preço promocional</label>
          <input v-model="form.promo_price" type="number" step="0.01" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Peso (gramas)</label>
          <input v-model="form.weight_grams" type="number" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>

        <div class="col-span-2">
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Descrição curta</label>
          <input v-model="form.short_description" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>

        <div class="col-span-2">
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Descrição completa</label>
          <textarea v-model="form.description" rows="4" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none" />
        </div>

        <label class="flex items-center gap-2 text-sm text-neutral-600"><input v-model="form.active" type="checkbox" class="accent-brand-600"> Ativo</label>
        <label class="flex items-center gap-2 text-sm text-neutral-600"><input v-model="form.featured" type="checkbox" class="accent-brand-600"> Destaque</label>
      </div>

      <button type="submit" :disabled="submitting" class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
        {{ submitting ? 'Salvando...' : 'Criar e continuar (imagens e variações)' }}
      </button>
      <p class="text-xs text-neutral-400">As imagens e variações são adicionadas na próxima tela, depois que o produto for criado.</p>
    </form>
  </div>
</template>
