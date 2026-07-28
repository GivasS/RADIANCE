<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

interface OrderRow {
  id: number
  order_number: string
  status: string
  total: string
  created_at: string
}

const { request } = useApi()

const { data: orders, pending } = await useAsyncData('my-orders', () =>
  request<{ data: OrderRow[] }>('/api/orders').then(r => r.data), { default: () => [] })

const statusLabels: Record<string, string> = {
  pendente: 'Pendente',
  aguardando_pagamento: 'Aguardando pagamento',
  pago: 'Pago',
  separando: 'Separando',
  enviado: 'Enviado',
  entregue: 'Entregue',
  cancelado: 'Cancelado',
  expirado: 'Expirado',
  estornado: 'Estornado',
}

function formatCurrency(value: string) {
  return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

useHead({ title: 'Meus Pedidos — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="font-display text-2xl text-brand-700">Minha Conta</h1>

    <div class="mt-6">
      <AccountTabs />
    </div>

    <div v-if="pending" class="mt-8 space-y-3">
      <div v-for="n in 3" :key="n" class="h-16 animate-pulse rounded-xl bg-brand-50" />
    </div>

    <div v-else-if="!orders.length" class="mt-8 rounded-xl border border-brand-50 bg-brand-50/30 py-16 text-center text-neutral-500">
      Você ainda não fez pedidos.
    </div>

    <ul v-else class="mt-8 space-y-3">
      <li v-for="order in orders" :key="order.id">
        <NuxtLink
          :to="`/pedido/${order.order_number}`"
          class="flex items-center justify-between rounded-xl border border-brand-50 p-4 text-sm hover:border-brand-200"
        >
          <div>
            <p class="font-medium text-neutral-800">{{ order.order_number }}</p>
            <p class="text-xs text-neutral-400">{{ new Date(order.created_at).toLocaleDateString('pt-BR') }}</p>
          </div>
          <div class="text-right">
            <p class="font-display text-brand-700">{{ formatCurrency(order.total) }}</p>
            <p class="text-xs text-neutral-400">{{ statusLabels[order.status] ?? order.status }}</p>
          </div>
        </NuxtLink>
      </li>
    </ul>
  </div>
</template>
