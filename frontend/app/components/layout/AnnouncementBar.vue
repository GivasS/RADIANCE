<script setup lang="ts">
import { X } from 'lucide-vue-next'

const visible = ref(true)
</script>

<template>
  <div
    v-if="visible"
    class="relative bg-brand-600 text-white text-xs sm:text-sm py-2 px-4 text-center tracking-wide"
  >
    <span>Frete grátis acima de R$ 219 · Use o cupom <strong class="font-semibold">BEMVINDO10</strong> na primeira compra</span>
    <button
      type="button"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-white/80 hover:text-white"
      aria-label="Fechar aviso"
      @click="visible = false"
    >
      <X :size="16" />
    </button>
  </div>
</template>
