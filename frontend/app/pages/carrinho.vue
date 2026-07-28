<script setup lang="ts">
import { PartyPopper, ShoppingBag, X } from 'lucide-vue-next'

interface CartItemFull {
  id: number
  product_id: number
  variant_id: number
  product: { id: number, name: string, slug: string, images: { path: string, is_main: boolean }[] }
  variant: { id: number, variant_value: string }
  quantity: number
  unit_price: number
  effective_unit_price: number
  line_total: number
  situation: 'ok' | 'estoque_parcial' | 'indisponivel'
  available_stock: number
}

interface CartFull {
  id: number
  items: CartItemFull[]
  subtotal: number
  has_unavailable: boolean
}

const { request, mutate } = useApi()
const { data: settings } = useSettings()
const { refreshCart } = useCart()
const { imageUrl } = useImageUrl()

const { data: cart, pending, refresh } = useAsyncData<CartFull>('cart-page', () =>
  request<CartFull>('/api/cart'), {
  default: () => ({ id: 0, items: [], subtotal: 0, has_unavailable: false }),
})

const busyItemId = ref<number | null>(null)

async function updateQuantity(item: CartItemFull, quantity: number) {
  if (quantity < 1) return
  busyItemId.value = item.id
  try {
    cart.value = await mutate<CartFull>(`/api/cart/items/${item.id}`, { method: 'PUT', body: { quantity } })
    await refreshCart()
  } finally {
    busyItemId.value = null
  }
}

async function removeItem(item: CartItemFull) {
  busyItemId.value = item.id
  try {
    cart.value = await mutate<CartFull>(`/api/cart/items/${item.id}`, { method: 'DELETE' })
    await refreshCart()
  } finally {
    busyItemId.value = null
  }
}

async function removeUnavailable() {
  cart.value = await mutate<CartFull>('/api/cart/items/unavailable', { method: 'DELETE' })
  await refreshCart()
}

const freeShippingThreshold = computed(() => settings.value?.free_shipping_threshold ?? 219)
const freeShippingProgress = computed(() => Math.min(100, ((cart.value?.subtotal ?? 0) / freeShippingThreshold.value) * 100))
const missingForFreeShipping = computed(() => Math.max(0, freeShippingThreshold.value - (cart.value?.subtotal ?? 0)))

function formatCurrency(value: number) {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function mainImage(item: CartItemFull) {
  const img = item.product.images?.find(i => i.is_main) ?? item.product.images?.[0]
  return imageUrl(img?.path)
}

useHead({ title: 'Meu Carrinho — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="font-display text-2xl text-brand-700 sm:text-3xl">Meu Carrinho</h1>

    <div v-if="pending" class="mt-10 space-y-4">
      <div v-for="n in 3" :key="n" class="h-24 animate-pulse rounded-xl bg-brand-50" />
    </div>

    <div v-else-if="!cart?.items?.length" class="mt-10 rounded-2xl border border-brand-50 bg-brand-50/30 py-20 text-center">
      <ShoppingBag :size="48" class="mx-auto text-brand-200" />
      <p class="mt-4 text-neutral-500">Seu carrinho está vazio.</p>
      <NuxtLink to="/" class="mt-4 inline-block rounded-full bg-brand-600 px-6 py-2 text-sm text-white hover:bg-brand-700">
        Ver produtos
      </NuxtLink>
    </div>

    <div v-else class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_320px]">
      <!-- Itens -->
      <div>
        <!-- Barra de frete gratis -->
        <div class="mb-6 rounded-xl border border-brand-50 bg-brand-50/40 p-4">
          <p class="text-sm text-neutral-600">
            <template v-if="missingForFreeShipping > 0">
              Faltam <strong class="text-brand-700">{{ formatCurrency(missingForFreeShipping) }}</strong> para o frete grátis!
            </template>
            <template v-else>
              <span class="inline-flex items-center gap-1"><PartyPopper :size="16" /> Você ganhou frete grátis!</span>
            </template>
          </p>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
            <div class="h-full rounded-full bg-accent-600 transition-all" :style="{ width: freeShippingProgress + '%' }" />
          </div>
        </div>

        <div v-if="cart.has_unavailable" class="mb-4 flex items-center justify-between rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
          <span>Alguns itens estão indisponíveis.</span>
          <button type="button" class="font-medium underline" @click="removeUnavailable">Remover indisponíveis</button>
        </div>

        <ul class="space-y-4">
          <li
            v-for="item in cart.items"
            :key="item.id"
            class="flex gap-4 rounded-xl border border-brand-50 p-4"
            :class="item.situation === 'indisponivel' ? 'opacity-50' : ''"
          >
            <NuxtLink :to="`/produto/${item.product.slug}`" class="h-20 w-20 flex-none overflow-hidden rounded-lg bg-brand-50">
              <img v-if="mainImage(item)" :src="mainImage(item)" :alt="item.product.name" class="h-full w-full object-cover">
            </NuxtLink>

            <div class="flex flex-1 flex-col justify-between">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <NuxtLink :to="`/produto/${item.product.slug}`" class="font-medium text-neutral-800 hover:text-brand-600">
                    {{ item.product.name }}
                  </NuxtLink>
                  <p class="text-xs text-neutral-400">{{ item.variant.variant_value }}</p>

                  <span
                    v-if="item.situation === 'indisponivel'"
                    class="mt-1 inline-block rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-700"
                  >
                    Indisponível
                  </span>
                  <span
                    v-else-if="item.situation === 'estoque_parcial'"
                    class="mt-1 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700"
                  >
                    Restam apenas {{ item.available_stock }} unidades
                  </span>
                </div>
                <button type="button" class="text-neutral-400 hover:text-red-600" @click="removeItem(item)">
                  <X :size="16" />
                </button>
              </div>

              <div class="flex items-end justify-between">
                <div class="flex items-center rounded-full border border-brand-200" :class="item.situation === 'indisponivel' ? 'pointer-events-none opacity-40' : ''">
                  <button
                    type="button"
                    class="px-2.5 py-1 text-brand-600"
                    :disabled="busyItemId === item.id || item.quantity <= 1"
                    @click="updateQuantity(item, item.quantity - 1)"
                  >
                    −
                  </button>
                  <span class="w-6 text-center text-sm">{{ item.quantity }}</span>
                  <button
                    type="button"
                    class="px-2.5 py-1 text-brand-600"
                    :disabled="busyItemId === item.id || item.quantity >= item.available_stock"
                    @click="updateQuantity(item, item.quantity + 1)"
                  >
                    +
                  </button>
                </div>
                <p class="font-display text-brand-700">{{ formatCurrency(item.line_total) }}</p>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Resumo -->
      <aside class="h-fit rounded-2xl border border-brand-50 bg-white p-6 shadow-sm">
        <h2 class="font-display text-lg text-brand-700">Resumo</h2>
        <div class="mt-4 flex justify-between text-sm text-neutral-600">
          <span>Subtotal</span>
          <span>{{ formatCurrency(cart.subtotal) }}</span>
        </div>
        <p class="mt-1 text-xs text-neutral-400">Frete calculado no próximo passo</p>

        <NuxtLink
          v-if="!cart.has_unavailable"
          to="/checkout"
          class="mt-6 block rounded-full bg-brand-600 py-3 text-center text-sm font-medium text-white hover:bg-brand-700"
        >
          Finalizar compra
        </NuxtLink>
        <button
          v-else
          disabled
          class="mt-6 w-full cursor-not-allowed rounded-full bg-neutral-200 py-3 text-sm font-medium text-neutral-400"
        >
          Remova os itens indisponíveis
        </button>
      </aside>
    </div>
  </div>
</template>
