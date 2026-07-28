<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

useHead({ title: 'Meus Endereços — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="font-display text-2xl text-brand-700">Minha Conta</h1>

    <div class="mt-6">
      <AccountTabs />
    </div>

    <p class="mt-8 text-neutral-400">Gerenciamento de endereços em construção.</p>
  </div>
</template>
