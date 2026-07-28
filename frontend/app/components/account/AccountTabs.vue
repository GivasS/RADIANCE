<script setup lang="ts">
const tabs = [
  { to: '/minha-conta', label: 'Meus Dados' },
  { to: '/minha-conta/enderecos', label: 'Endereços' },
  { to: '/minha-conta/pedidos', label: 'Meus Pedidos' },
]
</script>

<template>
  <nav class="flex gap-6 border-b border-brand-100">
    <NuxtLink
      v-for="tab in tabs"
      :key="tab.to"
      :to="tab.to"
      class="border-b-2 border-transparent pb-3 text-sm font-medium text-neutral-500 hover:text-brand-600"
      active-class="!border-brand-600 !text-brand-700"
      exact-active-class="!border-brand-600 !text-brand-700"
    >
      {{ tab.label }}
    </NuxtLink>
  </nav>
</template>
