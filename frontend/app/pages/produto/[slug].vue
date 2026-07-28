<script setup lang="ts">
import { AlertCircle, CheckCircle2 } from 'lucide-vue-next'

interface Variant {
  id: number
  variant_name: string
  variant_value: string
  price_override: string | null
  stock_quantity: number
  active: boolean
}

interface ProductDetail {
  id: number
  name: string
  slug: string
  sku: string
  description: string | null
  short_description: string | null
  price: string
  promo_price: string | null
  category?: { id: number, name: string, slug: string }
  images: { id: number, path: string, alt_text: string | null, is_main: boolean }[]
  variants: Variant[]
}

const route = useRoute()
const { request, mutate } = useApi()
const { data: settings } = useSettings()
const { refreshCart } = useCart()

const slug = computed(() => route.params.slug as string)

const { data: product, pending } = await useAsyncData<ProductDetail | null>(
  () => `product-${slug.value}`,
  () => request<{ product: ProductDetail }>(`/api/products/${slug.value}`).then(r => r.product),
)

const hasRealVariants = computed(() => (product.value?.variants?.length ?? 0) > 1)
const selectedVariantId = ref<number | null>(null)

watchEffect(() => {
  if (product.value?.variants?.length && !selectedVariantId.value) {
    const firstAvailable = product.value.variants.find(v => v.stock_quantity > 0) ?? product.value.variants[0]
    selectedVariantId.value = firstAvailable.id
  }
})

const selectedVariant = computed(() => product.value?.variants.find(v => v.id === selectedVariantId.value))

const basePrice = computed(() => Number(product.value?.price ?? 0))
const promoPrice = computed(() => product.value?.promo_price ? Number(product.value.promo_price) : null)
const effectivePrice = computed(() => {
  const override = selectedVariant.value?.price_override
  if (override) return Number(override)
  return promoPrice.value ?? basePrice.value
})

const pixPrice = computed(() => effectivePrice.value * (1 - (settings.value?.pix_discount_percent ?? 0) / 100))

const installments = computed(() => {
  const max = settings.value?.max_installments ?? 1
  const min = settings.value?.min_installment_value ?? 0
  for (let n = max; n >= 1; n--) {
    const parcel = effectivePrice.value / n
    if (parcel >= min) return n
  }
  return 1
})

const totallyOutOfStock = computed(() => (product.value?.variants ?? []).every(v => v.stock_quantity <= 0))
const lowStock = computed(() => {
  const stock = selectedVariant.value?.stock_quantity ?? 0
  const threshold = settings.value?.low_stock_alert ?? 0
  return stock > 0 && stock <= threshold
})

const quantity = ref(1)
watch(selectedVariantId, () => { quantity.value = 1 })

function formatCurrency(value: number) {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

// Adicionar ao carrinho
const adding = ref(false)
const addedMessage = ref('')
const addError = ref(false)

async function addToCart() {
  if (!product.value || !selectedVariant.value || selectedVariant.value.stock_quantity <= 0) return

  adding.value = true
  addedMessage.value = ''

  try {
    await mutate('/api/cart/items', {
      method: 'POST',
      body: {
        product_id: product.value.id,
        variant_id: selectedVariant.value.id,
        quantity: quantity.value,
      },
    })
    await refreshCart()
    addedMessage.value = 'Adicionado ao carrinho!'
    addError.value = false
  } catch {
    addedMessage.value = 'Não foi possível adicionar. Tente novamente.'
    addError.value = true
  } finally {
    adding.value = false
  }
}

// Frete
const zipcode = ref('')
const shippingOptions = ref<{ id: number, name: string, price: number, delivery_days: number, free: boolean }[]>([])
const calculatingShipping = ref(false)

async function calculateShipping() {
  if (!zipcode.value || zipcode.value.replace(/\D/g, '').length < 8) return
  calculatingShipping.value = true
  try {
    const { options } = await request<{ options: typeof shippingOptions.value }>('/api/cart/shipping/quote', {
      method: 'POST',
      body: { zipcode: zipcode.value, subtotal: effectivePrice.value * quantity.value },
    })
    shippingOptions.value = options
  } finally {
    calculatingShipping.value = false
  }
}

// Relacionados
const { data: related } = await useAsyncData(() => `related-${slug.value}`, async () => {
  if (!product.value?.category) return []
  const { data } = await request<{ data: any[] }>('/api/products', { params: { category: product.value.category.slug, per_page: 8 } })
  return data.filter(p => p.slug !== slug.value)
}, { watch: [product] })

const activeTab = ref<'descricao' | 'medidas' | 'garantia'>('descricao')

useHead({ title: () => product.value ? `${product.value.name} — Radiance` : 'Radiance' })
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div v-if="pending" class="grid grid-cols-1 gap-10 lg:grid-cols-2">
      <div class="aspect-square animate-pulse rounded-2xl bg-brand-50" />
      <div class="space-y-4">
        <div class="h-8 w-2/3 animate-pulse rounded bg-brand-50" />
        <div class="h-6 w-1/3 animate-pulse rounded bg-brand-50" />
      </div>
    </div>

    <div v-else-if="!product" class="py-20 text-center text-neutral-400">
      Produto não encontrado.
    </div>

    <template v-else>
      <nav class="mb-4 text-xs text-neutral-400">
        <NuxtLink to="/" class="hover:text-brand-600">Início</NuxtLink>
        <span class="mx-1">/</span>
        <NuxtLink v-if="product.category" :to="`/categoria/${product.category.slug}`" class="hover:text-brand-600">
          {{ product.category.name }}
        </NuxtLink>
        <span class="mx-1">/</span>
        <span class="text-neutral-600">{{ product.name }}</span>
      </nav>

      <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
        <ProductGallery :images="product.images" />

        <div>
          <h1 class="font-display text-2xl text-brand-800 sm:text-3xl">{{ product.name }}</h1>
          <p v-if="product.short_description" class="mt-2 text-sm text-neutral-500">{{ product.short_description }}</p>

          <div class="mt-4">
            <p v-if="promoPrice" class="text-sm text-neutral-400 line-through">{{ formatCurrency(basePrice) }}</p>
            <p class="font-display text-3xl text-brand-700">{{ formatCurrency(effectivePrice) }}</p>
            <p class="mt-1 text-sm text-accent-700">{{ formatCurrency(pixPrice) }} no Pix ({{ settings?.pix_discount_percent }}% off)</p>
            <p v-if="installments > 1" class="text-sm text-neutral-400">
              ou {{ installments }}x de {{ formatCurrency(effectivePrice / installments) }} sem juros
            </p>
          </div>

          <!-- Estado: totalmente esgotado -->
          <div v-if="totallyOutOfStock" class="mt-6 rounded-lg bg-neutral-100 p-4 text-sm text-neutral-600">
            Produto esgotado no momento.
            <button type="button" class="mt-2 block font-medium text-brand-600 hover:underline">
              Avise-me quando chegar
            </button>
          </div>

          <template v-else>
            <!-- Seletor de variacao (so mostra se tiver variacao real) -->
            <div v-if="hasRealVariants" class="mt-6">
              <p class="mb-2 text-sm font-medium text-neutral-700">{{ product.variants[0]?.variant_name }}</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="variant in product.variants"
                  :key="variant.id"
                  type="button"
                  :disabled="variant.stock_quantity <= 0"
                  class="rounded-full border px-4 py-2 text-sm transition-colors"
                  :class="[
                    selectedVariantId === variant.id ? 'border-brand-600 bg-brand-600 text-white' : 'border-brand-200 text-neutral-700 hover:border-brand-400',
                    variant.stock_quantity <= 0 ? 'cursor-not-allowed line-through opacity-40' : '',
                  ]"
                  @click="selectedVariantId = variant.id"
                >
                  {{ variant.variant_value }}
                </button>
              </div>
            </div>

            <p v-if="lowStock" class="mt-3 text-sm font-medium text-brand-600">
              Últimas {{ selectedVariant?.stock_quantity }} unidades!
            </p>

            <!-- Quantidade -->
            <div class="mt-6 flex items-center gap-4">
              <div class="flex items-center rounded-full border border-brand-200">
                <button type="button" class="px-3 py-2 text-brand-600" :disabled="quantity <= 1" @click="quantity--">−</button>
                <span class="w-8 text-center text-sm">{{ quantity }}</span>
                <button
                  type="button"
                  class="px-3 py-2 text-brand-600"
                  :disabled="quantity >= (selectedVariant?.stock_quantity ?? 1)"
                  @click="quantity++"
                >
                  +
                </button>
              </div>
              <span class="text-xs text-neutral-400">{{ selectedVariant?.stock_quantity }} em estoque</span>
            </div>

            <button
              type="button"
              :disabled="adding"
              class="mt-6 w-full rounded-full bg-brand-600 py-3 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
              @click="addToCart"
            >
              {{ adding ? 'Adicionando...' : 'Adicionar ao carrinho' }}
            </button>
            <p
              v-if="addedMessage"
              class="mt-2 flex items-center justify-center gap-1 text-center text-sm"
              :class="addError ? 'text-red-600' : 'text-accent-700'"
            >
              <component :is="addError ? AlertCircle : CheckCircle2" :size="16" /> {{ addedMessage }}
            </p>
          </template>

          <!-- Frete -->
          <div class="mt-8 border-t border-brand-50 pt-6">
            <p class="mb-2 text-sm font-medium text-neutral-700">Calcular frete</p>
            <form class="flex gap-2" @submit.prevent="calculateShipping">
              <input
                v-model="zipcode"
                type="text"
                placeholder="00000-000"
                class="w-40 rounded-lg border border-brand-100 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none"
              >
              <button
                type="submit"
                :disabled="calculatingShipping"
                class="rounded-lg border border-brand-300 px-4 py-2 text-sm text-brand-600 hover:bg-brand-50"
              >
                {{ calculatingShipping ? 'Calculando...' : 'Calcular' }}
              </button>
            </form>
            <ul v-if="shippingOptions.length" class="mt-3 space-y-1 text-sm text-neutral-600">
              <li v-for="opt in shippingOptions" :key="opt.id" class="flex justify-between">
                <span>{{ opt.name }} — até {{ opt.delivery_days }} dias</span>
                <span class="font-medium">{{ opt.free ? 'Grátis' : formatCurrency(opt.price) }}</span>
              </li>
            </ul>
          </div>

          <!-- Abas -->
          <div class="mt-8 border-t border-brand-50 pt-6">
            <div class="flex gap-6 border-b border-brand-50 text-sm">
              <button
                v-for="tab in ['descricao', 'medidas', 'garantia'] as const"
                :key="tab"
                class="border-b-2 pb-2 capitalize"
                :class="activeTab === tab ? 'border-brand-600 text-brand-700' : 'border-transparent text-neutral-400'"
                @click="activeTab = tab"
              >
                {{ tab }}
              </button>
            </div>
            <div class="pt-4 text-sm text-neutral-600">
              <p v-if="activeTab === 'descricao'">{{ product.description ?? product.short_description ?? 'Sem descrição.' }}</p>
              <p v-else-if="activeTab === 'medidas'">Consulte o guia de medidas ou entre em contato pelo WhatsApp.</p>
              <p v-else>Peça em prata 925 com garantia contra defeitos de fabricação.</p>
            </div>
          </div>
        </div>
      </div>

      <ProductShowcase title="Você também pode gostar" :products="related ?? []" />
    </template>
  </div>
</template>
