<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

const route = useRoute()
const { request } = useApi()

const { data: customer } = await useAsyncData(`admin-customer-${route.params.id}`, () =>
  request<{ customer: any }>(`/api/admin/customers/${route.params.id}`).then(r => r.customer))

function formatCurrency(v: string) {
  return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

useHead({ title: () => `${customer.value?.name ?? 'Cliente'} — Admin Radiance` })
</script>

<template>
  <div v-if="customer" class="max-w-2xl">
    <NuxtLink to="/admin/clientes" class="text-sm text-brand-600 hover:underline">← Voltar</NuxtLink>
    <h1 class="mb-1 mt-2 text-xl font-semibold text-neutral-800">{{ customer.name }}</h1>
    <p class="mb-6 text-sm text-neutral-500">{{ customer.email }} {{ customer.cpf ? `· ${customer.cpf}` : '' }} {{ customer.phone ? `· ${customer.phone}` : '' }}</p>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="rounded-xl border border-neutral-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-neutral-700">Endereços</h2>
        <p v-if="!customer.addresses?.length" class="text-sm text-neutral-400">Nenhum endereço salvo.</p>
        <div v-for="addr in customer.addresses" :key="addr.id" class="mb-2 text-sm text-neutral-500">
          {{ addr.street }}, {{ addr.number }} — {{ addr.city }}/{{ addr.state }}
        </div>
      </div>

      <div class="rounded-xl border border-neutral-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-neutral-700">Histórico de pedidos</h2>
        <p v-if="!customer.orders?.length" class="text-sm text-neutral-400">Nenhum pedido ainda.</p>
        <ul class="space-y-2 text-sm">
          <li v-for="order in customer.orders" :key="order.id" class="flex justify-between">
            <NuxtLink :to="`/admin/pedidos/${order.id}`" class="text-brand-600 hover:underline">{{ order.order_number }}</NuxtLink>
            <span class="text-neutral-500">{{ formatCurrency(order.total) }} · {{ order.status }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
