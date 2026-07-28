<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

const { request } = useApi()
const config = useRuntimeConfig()

const search = ref('')
const statusFilter = ref('')
const page = ref(1)

const statuses = ['pendente', 'aguardando_pagamento', 'pago', 'separando', 'enviado', 'entregue', 'cancelado', 'expirado', 'estornado']

const { data: orders, pending } = await useAsyncData(
  () => `admin-orders-${search.value}-${statusFilter.value}-${page.value}`,
  () => request<{ data: any[], current_page: number, last_page: number }>('/api/admin/orders', {
    params: { search: search.value || undefined, status: statusFilter.value || undefined, page: page.value },
  }),
  { watch: [search, statusFilter, page] },
)

function formatCurrency(v: string) {
  return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function exportUrl() {
  const params = new URLSearchParams()
  if (search.value) params.set('search', search.value)
  if (statusFilter.value) params.set('status', statusFilter.value)
  return `${config.public.apiBase}/api/admin/orders/export?${params.toString()}`
}

useHead({ title: 'Pedidos — Admin Radiance' })
</script>

<template>
  <div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold text-neutral-800">Pedidos</h1>
      <a :href="exportUrl()" target="_blank" class="rounded-lg border border-neutral-200 px-4 py-2 text-sm text-neutral-500 hover:bg-neutral-50">
        Exportar CSV
      </a>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input v-model="search" placeholder="Buscar por número ou cliente..." class="rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
      <select v-model="statusFilter" class="rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        <option value="">Todos os status</option>
        <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
      </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase text-neutral-400">
            <th class="p-3">Número</th>
            <th class="p-3">Cliente</th>
            <th class="p-3">Status</th>
            <th class="p-3">Data</th>
            <th class="p-3 text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="pending"><td colspan="5" class="p-6 text-center text-neutral-400">Carregando...</td></tr>
          <tr v-else-if="!orders?.data?.length"><td colspan="5" class="p-6 text-center text-neutral-400">Nenhum pedido encontrado.</td></tr>
          <tr v-for="order in orders?.data" :key="order.id" class="border-b border-neutral-50">
            <td class="p-3"><NuxtLink :to="`/admin/pedidos/${order.id}`" class="text-brand-600 hover:underline">{{ order.order_number }}</NuxtLink></td>
            <td class="p-3 text-neutral-600">{{ order.user?.name }}</td>
            <td class="p-3 text-neutral-500">{{ order.status }}</td>
            <td class="p-3 text-neutral-400">{{ new Date(order.created_at).toLocaleDateString('pt-BR') }}</td>
            <td class="p-3 text-right font-medium">{{ formatCurrency(order.total) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="orders && orders.last_page > 1" class="mt-4 flex justify-center gap-2">
      <button :disabled="page <= 1" class="rounded border border-neutral-200 px-3 py-1 text-sm disabled:opacity-30" @click="page--">Anterior</button>
      <span class="text-sm text-neutral-500">{{ orders.current_page }} / {{ orders.last_page }}</span>
      <button :disabled="page >= orders.last_page" class="rounded border border-neutral-200 px-3 py-1 text-sm disabled:opacity-30" @click="page++">Próxima</button>
    </div>
  </div>
</template>
