<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

interface DashboardData {
  sales_today: number
  sales_month: number
  pending_orders: number
  average_ticket: number
  low_stock: { id: number, variant_value: string, stock_quantity: number, product: { name: string } }[]
  last_orders: { id: number, order_number: string, status: string, total: string, user: { name: string } }[]
  sales_chart: { date: string, total: string }[]
}

const { request } = useApi()

const { data } = await useAsyncData<DashboardData>('admin-dashboard', () =>
  request<DashboardData>('/api/admin/dashboard'))

function formatCurrency(value: number) {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

const maxChartValue = computed(() => Math.max(1, ...(data.value?.sales_chart.map(d => Number(d.total)) ?? [1])))

useHead({ title: 'Dashboard — Admin Radiance' })
</script>

<template>
  <div>
    <h1 class="mb-6 text-xl font-semibold text-neutral-800">Dashboard</h1>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <div class="rounded-xl border border-neutral-200 bg-white p-4">
        <p class="text-xs text-neutral-400">Vendas hoje</p>
        <p class="mt-1 text-2xl font-semibold text-brand-700">{{ formatCurrency(data?.sales_today ?? 0) }}</p>
      </div>
      <div class="rounded-xl border border-neutral-200 bg-white p-4">
        <p class="text-xs text-neutral-400">Vendas no mês</p>
        <p class="mt-1 text-2xl font-semibold text-brand-700">{{ formatCurrency(data?.sales_month ?? 0) }}</p>
      </div>
      <div class="rounded-xl border border-neutral-200 bg-white p-4">
        <p class="text-xs text-neutral-400">Pedidos pendentes</p>
        <p class="mt-1 text-2xl font-semibold text-neutral-800">{{ data?.pending_orders ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-neutral-200 bg-white p-4">
        <p class="text-xs text-neutral-400">Ticket médio</p>
        <p class="mt-1 text-2xl font-semibold text-neutral-800">{{ formatCurrency(data?.average_ticket ?? 0) }}</p>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Grafico -->
      <div class="rounded-xl border border-neutral-200 bg-white p-5 lg:col-span-2">
        <h2 class="mb-4 text-sm font-semibold text-neutral-700">Vendas — últimos 30 dias</h2>
        <div v-if="data?.sales_chart.length" class="flex h-40 items-end gap-1">
          <div
            v-for="point in data.sales_chart"
            :key="point.date"
            class="flex-1 rounded-t bg-brand-400"
            :style="{ height: `${(Number(point.total) / maxChartValue) * 100}%` }"
            :title="`${point.date}: ${formatCurrency(Number(point.total))}`"
          />
        </div>
        <p v-else class="py-10 text-center text-sm text-neutral-400">Sem vendas no período.</p>
      </div>

      <!-- Estoque baixo -->
      <div class="rounded-xl border border-neutral-200 bg-white p-5">
        <h2 class="mb-4 text-sm font-semibold text-neutral-700">Estoque baixo</h2>
        <ul v-if="data?.low_stock.length" class="space-y-2 text-sm">
          <li v-for="v in data.low_stock" :key="v.id" class="flex justify-between">
            <span class="text-neutral-600">{{ v.product.name }} ({{ v.variant_value }})</span>
            <span class="font-medium text-red-600">{{ v.stock_quantity }}</span>
          </li>
        </ul>
        <p v-else class="text-sm text-neutral-400">Nenhum produto com estoque baixo.</p>
      </div>
    </div>

    <!-- Ultimos pedidos -->
    <div class="mt-6 rounded-xl border border-neutral-200 bg-white p-5">
      <h2 class="mb-4 text-sm font-semibold text-neutral-700">Últimos pedidos</h2>
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-100 text-left text-xs uppercase text-neutral-400">
            <th class="pb-2">Número</th>
            <th class="pb-2">Cliente</th>
            <th class="pb-2">Status</th>
            <th class="pb-2 text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in data?.last_orders" :key="order.id" class="border-b border-neutral-50">
            <td class="py-2">
              <NuxtLink :to="`/admin/pedidos/${order.id}`" class="text-brand-600 hover:underline">{{ order.order_number }}</NuxtLink>
            </td>
            <td class="py-2 text-neutral-600">{{ order.user.name }}</td>
            <td class="py-2 text-neutral-500">{{ order.status }}</td>
            <td class="py-2 text-right font-medium">{{ formatCurrency(Number(order.total)) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
