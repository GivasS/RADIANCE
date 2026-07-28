<script setup lang="ts">
import { Gem } from 'lucide-vue-next'
import type { ProductSummary } from '~/composables/useProducts'

const props = defineProps<{ product: ProductSummary }>()

const { data: settings } = useSettings()
const { imageUrl } = useImageUrl()

const mainImage = computed(() => {
  const img = props.product.images?.find(i => i.is_main) ?? props.product.images?.[0]
  return imageUrl(img?.path)
})

const price = computed(() => Number(props.product.price))
const promoPrice = computed(() => props.product.promo_price ? Number(props.product.promo_price) : null)
const effectivePrice = computed(() => promoPrice.value ?? price.value)

const pixPrice = computed(() => {
  const discount = settings.value?.pix_discount_percent ?? 0
  return effectivePrice.value * (1 - discount / 100)
})

const installmentText = computed(() => {
  const max = settings.value?.max_installments ?? 1
  const min = settings.value?.min_installment_value ?? 0
  for (let n = max; n >= 1; n--) {
    const parcel = effectivePrice.value / n
    if (parcel >= min) return n > 1 ? `ou ${n}x de ${formatCurrency(parcel)} sem juros` : null
  }
  return null
})

const freeShipping = computed(() => effectivePrice.value >= (settings.value?.free_shipping_threshold ?? Infinity))
const lowStock = computed(() => {
  const stock = props.product.stock_total
  const threshold = settings.value?.low_stock_alert ?? 0
  return stock !== null && stock > 0 && stock <= threshold
})

function formatCurrency(value: number) {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
</script>

<template>
  <NuxtLink :to="`/produto/${product.slug}`" class="group block">
    <div class="relative aspect-square overflow-hidden rounded-xl bg-brand-50/50">
      <img
        v-if="mainImage"
        :src="mainImage"
        :alt="product.name"
        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
        loading="lazy"
      >
      <div v-else class="flex h-full w-full items-center justify-center text-brand-200">
        <Gem :size="36" />
      </div>

      <div class="absolute left-2 top-2 flex flex-col gap-1">
        <span v-if="promoPrice" class="rounded-full bg-accent-600 px-2 py-0.5 text-[11px] font-semibold text-white">
          OFF
        </span>
        <span v-if="lowStock" class="rounded-full bg-brand-700 px-2 py-0.5 text-[11px] font-semibold text-white">
          Últimas {{ product.stock_total }} unidades
        </span>
      </div>

      <span
        v-if="freeShipping"
        class="absolute bottom-2 left-2 rounded-full bg-white/90 px-2 py-0.5 text-[11px] font-medium text-accent-700"
      >
        Frete grátis
      </span>
    </div>

    <div class="mt-3 space-y-1">
      <p v-if="product.category" class="text-[11px] uppercase tracking-wide text-neutral-400">
        {{ product.category.name }}
      </p>
      <h3 class="font-medium text-neutral-800 line-clamp-2">{{ product.name }}</h3>

      <div class="pt-1">
        <p v-if="promoPrice" class="text-xs text-neutral-400 line-through">{{ formatCurrency(price) }}</p>
        <p class="font-display text-lg text-brand-700">{{ formatCurrency(effectivePrice) }}</p>
        <p class="text-xs text-accent-700">{{ formatCurrency(pixPrice) }} no Pix</p>
        <p v-if="installmentText" class="text-xs text-neutral-400">{{ installmentText }}</p>
      </div>
    </div>
  </NuxtLink>
</template>
