<script setup lang="ts">
import { CreditCard, Gift, Sparkles, Truck } from 'lucide-vue-next'

const { data: settings } = useSettings()

const benefits = computed(() => [
  { icon: Truck, title: 'Frete grátis', text: `Acima de R$ ${settings.value?.free_shipping_threshold ?? 219}` },
  { icon: CreditCard, title: `${settings.value?.max_installments ?? 3}x sem juros`, text: 'No cartão de crédito' },
  { icon: Gift, title: 'Cupom de boas-vindas', text: '10% na primeira compra' },
  { icon: Sparkles, title: 'Prata 925', text: 'Garantia e qualidade' },
])
</script>

<template>
  <section class="border-y border-brand-50 bg-brand-50/30">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-8 sm:grid-cols-4 sm:px-6 lg:px-8">
      <div v-for="benefit in benefits" :key="benefit.title" class="flex flex-col items-center gap-2 text-center">
        <component :is="benefit.icon" :size="26" class="text-brand-600" />
        <p class="text-sm font-semibold text-neutral-800">{{ benefit.title }}</p>
        <p class="text-xs text-neutral-500">{{ benefit.text }}</p>
      </div>
    </div>
  </section>
</template>
