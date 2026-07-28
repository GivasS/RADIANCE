<script setup lang="ts">
import { CheckCircle2 } from 'lucide-vue-next'

const email = ref('')
const sent = ref(false)

// TODO: nao existe endpoint de newsletter no backend ainda - so a UI por enquanto.
function subscribe() {
  if (!email.value.trim()) return
  sent.value = true
  email.value = ''
}
</script>

<template>
  <section class="bg-brand-700 py-14 text-center text-white">
    <div class="mx-auto max-w-xl px-4">
      <h2 class="font-display text-2xl">Receba novidades em primeira mão</h2>
      <p class="mt-2 text-sm text-brand-100">Cupons exclusivos e lançamentos direto no seu e-mail.</p>

      <form v-if="!sent" class="mt-6 flex flex-col gap-3 sm:flex-row" @submit.prevent="subscribe">
        <input
          v-model="email"
          type="email"
          required
          placeholder="Seu melhor e-mail"
          class="flex-1 rounded-full px-5 py-3 text-sm text-neutral-800 focus:outline-none"
        >
        <button type="submit" class="rounded-full bg-accent-600 px-6 py-3 text-sm font-medium hover:bg-accent-700">
          Quero receber
        </button>
      </form>
      <p v-else class="mt-6 flex items-center justify-center gap-2 text-sm">
        <CheckCircle2 :size="18" />
        Obrigada! Você vai receber nossas novidades.
      </p>
    </div>
  </section>
</template>
