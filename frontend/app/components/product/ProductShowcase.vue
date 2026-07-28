<script setup lang="ts">
import type { ProductSummary } from '~/composables/useProducts'

defineProps<{ title: string, products: ProductSummary[] }>()

const scrollerRef = ref<HTMLElement>()

function scroll(direction: 'left' | 'right') {
  const el = scrollerRef.value
  if (!el) return
  el.scrollBy({ left: direction === 'left' ? -320 : 320, behavior: 'smooth' })
}
</script>

<template>
  <section v-if="products.length" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
      <h2 class="font-display text-2xl text-brand-700">{{ title }}</h2>
      <div class="hidden gap-2 sm:flex">
        <button
          class="flex h-9 w-9 items-center justify-center rounded-full border border-brand-100 text-brand-600 hover:bg-brand-50"
          aria-label="Anterior"
          @click="scroll('left')"
        >
          ‹
        </button>
        <button
          class="flex h-9 w-9 items-center justify-center rounded-full border border-brand-100 text-brand-600 hover:bg-brand-50"
          aria-label="Próximo"
          @click="scroll('right')"
        >
          ›
        </button>
      </div>
    </div>

    <div ref="scrollerRef" class="flex snap-x gap-5 overflow-x-auto pb-2 scroll-smooth [scrollbar-width:none]">
      <div v-for="product in products" :key="product.id" class="w-44 flex-none snap-start sm:w-56">
        <ProductCard :product="product" />
      </div>
    </div>
  </section>
</template>
