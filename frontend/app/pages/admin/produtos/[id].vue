<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

interface ProductImage { id: number, path: string, is_main: boolean, alt_text: string | null }
interface Variant { id: number, variant_name: string, variant_value: string, sku_suffix: string | null, price_override: string | null, stock_quantity: number, active: boolean }
interface ProductFull {
  id: number
  category_id: number
  name: string
  slug: string
  sku: string
  price: string
  promo_price: string | null
  weight_grams: number
  short_description: string | null
  description: string | null
  active: boolean
  featured: boolean
  images: ProductImage[]
  variants: Variant[]
}

const route = useRoute()
const { request, mutate } = useApi()
const { imageUrl } = useImageUrl()

const productId = computed(() => route.params.id as string)

const { data: categories } = await useAsyncData('admin-edit-product-categories', async () => {
  const { categories } = await request<{ categories: any[] }>('/api/categories')
  return categories.flatMap((c: any) => [c, ...(c.children ?? [])])
})

const { data: product, refresh } = await useAsyncData<ProductFull>(
  () => `admin-product-${productId.value}`,
  () => request<{ product: ProductFull }>(`/api/admin/products/${productId.value}`).then(r => r.product),
)

const activeTab = ref<'dados' | 'imagens' | 'variacoes'>('dados')
const { success, error: toastError } = useToast()

// --- Aba Dados ---
const form = reactive({
  category_id: 0, name: '', sku: '', price: '', promo_price: '', weight_grams: 0,
  short_description: '', description: '', active: true, featured: false,
})
watchEffect(() => {
  if (product.value) {
    Object.assign(form, {
      category_id: product.value.category_id,
      name: product.value.name,
      sku: product.value.sku,
      price: product.value.price,
      promo_price: product.value.promo_price ?? '',
      weight_grams: product.value.weight_grams,
      short_description: product.value.short_description ?? '',
      description: product.value.description ?? '',
      active: product.value.active,
      featured: product.value.featured,
    })
  }
})

const savingData = ref(false)
const dataErrors = ref<Record<string, string[]>>({})
async function saveData() {
  savingData.value = true
  dataErrors.value = {}
  try {
    await mutate(`/api/admin/products/${productId.value}`, { method: 'PUT', body: form })
    await refresh()
    success('Alterações salvas.')
  } catch (e: any) {
    dataErrors.value = e?.response?._data?.errors ?? {}
    if (!Object.keys(dataErrors.value).length) toastError(apiErrorMessage(e, 'Não foi possível salvar as alterações.'))
  } finally {
    savingData.value = false
  }
}

// --- Aba Imagens ---
const uploadingImages = ref(false)
const fileInput = ref<HTMLInputElement>()

async function uploadImages(event: Event) {
  const files = (event.target as HTMLInputElement).files
  if (!files?.length) return

  uploadingImages.value = true
  const formData = new FormData()
  Array.from(files).forEach(f => formData.append('images[]', f))

  try {
    await mutate(`/api/admin/products/${productId.value}/images`, { method: 'POST', body: formData })
    await refresh()
    success(files.length > 1 ? `${files.length} imagens enviadas.` : 'Imagem enviada.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível enviar a imagem.'))
  } finally {
    uploadingImages.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

async function setMainImage(image: ProductImage) {
  try {
    await mutate(`/api/admin/products/${productId.value}/images/${image.id}`, { method: 'PUT', body: { is_main: true } })
    await refresh()
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível definir a imagem principal.'))
  }
}

async function deleteImage(image: ProductImage) {
  if (!confirm('Remover essa imagem?')) return
  try {
    await mutate(`/api/admin/products/${productId.value}/images/${image.id}`, { method: 'DELETE' })
    await refresh()
    success('Imagem removida.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível remover a imagem.'))
  }
}

// --- Aba Variacoes ---
const newVariant = reactive({ variant_name: 'Tamanho', variant_value: '', stock_quantity: 0, price_override: '' })
const addingVariant = ref(false)

async function addVariant() {
  addingVariant.value = true
  try {
    await mutate(`/api/admin/products/${productId.value}/variants`, { method: 'POST', body: newVariant })
    await refresh()
    newVariant.variant_value = ''
    newVariant.stock_quantity = 0
    newVariant.price_override = ''
    success('Variação adicionada.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível adicionar a variação.'))
  } finally {
    addingVariant.value = false
  }
}

async function updateVariantStock(variant: Variant, stock_quantity: number) {
  try {
    await mutate(`/api/admin/products/${productId.value}/variants/${variant.id}`, {
      method: 'PUT',
      body: { variant_name: variant.variant_name, variant_value: variant.variant_value, stock_quantity },
    })
    await refresh()
    success('Estoque atualizado.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível atualizar o estoque.'))
    await refresh()
  }
}

async function deleteVariant(variant: Variant) {
  if (!confirm(`Remover a variação "${variant.variant_value}"?`)) return
  try {
    await mutate(`/api/admin/products/${productId.value}/variants/${variant.id}`, { method: 'DELETE' })
    await refresh()
    success('Variação removida.')
  } catch (e: any) {
    toastError(e?.response?._data?.errors?.variant?.[0] ?? apiErrorMessage(e, 'Não foi possível remover a variação.'))
  }
}

const adjustingStock = ref<number | null>(null)
const stockDelta = ref('')
const stockNotes = ref('')
async function adjustStock(variant: Variant) {
  if (!stockDelta.value || !stockNotes.value) return
  try {
    await mutate(`/api/admin/products/${productId.value}/variants/${variant.id}/adjust-stock`, {
      method: 'POST',
      body: { delta: Number(stockDelta.value), notes: stockNotes.value },
    })
    await refresh()
    success('Estoque ajustado.')
    adjustingStock.value = null
    stockDelta.value = ''
    stockNotes.value = ''
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível ajustar o estoque.'))
  }
}

useHead({ title: () => `${product.value?.name ?? 'Produto'} — Admin Radiance` })
</script>

<template>
  <div class="max-w-3xl">
    <h1 class="mb-6 text-xl font-semibold text-neutral-800">{{ product?.name }}</h1>

    <div class="mb-6 flex gap-6 border-b border-neutral-200 text-sm">
      <button v-for="tab in ['dados', 'imagens', 'variacoes'] as const" :key="tab" class="border-b-2 pb-3 capitalize" :class="activeTab === tab ? 'border-brand-600 font-medium text-brand-700' : 'border-transparent text-neutral-400'" @click="activeTab = tab">
        {{ tab }}
      </button>
    </div>

    <!-- ABA DADOS -->
    <form v-if="activeTab === 'dados'" class="space-y-4 rounded-xl border border-neutral-200 bg-white p-6" @submit.prevent="saveData">
      <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Nome</label>
          <input v-model="form.name" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">SKU</label>
          <input v-model="form.sku" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Categoria</label>
          <select v-model="form.category_id" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Preço</label>
          <input v-model="form.price" type="number" step="0.01" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Preço promocional</label>
          <input v-model="form.promo_price" type="number" step="0.01" class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Peso (g)</label>
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
      <button type="submit" :disabled="savingData" class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
        {{ savingData ? 'Salvando...' : 'Salvar alterações' }}
      </button>
    </form>

    <!-- ABA IMAGENS -->
    <div v-else-if="activeTab === 'imagens'" class="rounded-xl border border-neutral-200 bg-white p-6">
      <input ref="fileInput" type="file" multiple accept="image/*" class="mb-4 text-sm" @change="uploadImages">
      <p v-if="uploadingImages" class="text-sm text-neutral-400">Enviando...</p>

      <div class="grid grid-cols-3 gap-4 sm:grid-cols-4">
        <div v-for="image in product?.images" :key="image.id" class="relative">
          <img :src="imageUrl(image.path)" class="aspect-square w-full rounded-lg object-cover" :class="image.is_main ? 'ring-2 ring-brand-500' : ''">
          <span v-if="image.is_main" class="absolute left-1 top-1 rounded bg-brand-600 px-1.5 py-0.5 text-[10px] text-white">Principal</span>
          <div class="mt-1 flex justify-between text-xs">
            <button v-if="!image.is_main" type="button" class="text-brand-600 hover:underline" @click="setMainImage(image)">Definir principal</button>
            <button type="button" class="text-red-500 hover:underline" @click="deleteImage(image)">Remover</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ABA VARIACOES -->
    <div v-else class="space-y-4">
      <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase text-neutral-400">
              <th class="p-3">Nome</th>
              <th class="p-3">Valor</th>
              <th class="p-3">Estoque</th>
              <th class="p-3">Ajuste</th>
              <th class="p-3" />
            </tr>
          </thead>
          <tbody>
            <tr v-for="variant in product?.variants" :key="variant.id" class="border-b border-neutral-50">
              <td class="p-3">{{ variant.variant_name }}</td>
              <td class="p-3">{{ variant.variant_value }}</td>
              <td class="p-3">
                <input
                  :value="variant.stock_quantity"
                  type="number"
                  class="w-20 rounded border border-neutral-200 px-2 py-1"
                  @change="updateVariantStock(variant, Number(($event.target as HTMLInputElement).value))"
                >
              </td>
              <td class="p-3">
                <button v-if="adjustingStock !== variant.id" type="button" class="text-xs text-brand-600 hover:underline" @click="adjustingStock = variant.id">
                  Ajustar com histórico
                </button>
                <div v-else class="flex items-center gap-1">
                  <input v-model="stockDelta" type="number" placeholder="+/-" class="w-16 rounded border border-neutral-200 px-1 py-1 text-xs">
                  <input v-model="stockNotes" placeholder="Motivo" class="w-24 rounded border border-neutral-200 px-1 py-1 text-xs">
                  <button type="button" class="text-xs text-accent-700" @click="adjustStock(variant)">OK</button>
                </div>
              </td>
              <td class="p-3 text-right">
                <button type="button" class="text-red-500 hover:underline" @click="deleteVariant(variant)">Remover</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <form class="flex flex-wrap items-end gap-3 rounded-xl border border-neutral-200 bg-white p-4" @submit.prevent="addVariant">
        <div>
          <label class="mb-1 block text-xs text-neutral-500">Nome do atributo</label>
          <input v-model="newVariant.variant_name" placeholder="Tamanho" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
        </div>
        <div>
          <label class="mb-1 block text-xs text-neutral-500">Valor</label>
          <input v-model="newVariant.variant_value" required placeholder="16" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
        </div>
        <div>
          <label class="mb-1 block text-xs text-neutral-500">Estoque</label>
          <input v-model="newVariant.stock_quantity" type="number" class="w-20 rounded border border-neutral-200 px-2 py-1.5 text-sm">
        </div>
        <div>
          <label class="mb-1 block text-xs text-neutral-500">Preço próprio (opcional)</label>
          <input v-model="newVariant.price_override" type="number" step="0.01" class="w-28 rounded border border-neutral-200 px-2 py-1.5 text-sm">
        </div>
        <button type="submit" :disabled="addingVariant" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white hover:bg-brand-700">
          + Adicionar variação
        </button>
      </form>
    </div>
  </div>
</template>
