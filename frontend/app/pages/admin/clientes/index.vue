<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

const { request } = useApi()
const search = ref('')

const { data: customers, pending } = await useAsyncData(
  () => `admin-customers-${search.value}`,
  () => request<{ data: any[] }>('/api/admin/customers', { params: { search: search.value || undefined } }),
  { watch: [search] },
)

useHead({ title: 'Clientes — Admin Radiance' })
</script>

<template>
  <div>
    <h1 class="mb-6 text-xl font-semibold text-neutral-800">Clientes</h1>

    <input v-model="search" placeholder="Buscar por nome ou e-mail..." class="mb-4 w-full max-w-sm rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">

    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase text-neutral-400">
            <th class="p-3">Nome</th>
            <th class="p-3">E-mail</th>
            <th class="p-3">Pedidos</th>
            <th class="p-3">Cadastro</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="pending"><td colspan="4" class="p-6 text-center text-neutral-400">Carregando...</td></tr>
          <tr v-else-if="!customers?.data?.length"><td colspan="4" class="p-6 text-center text-neutral-400">Nenhum cliente encontrado.</td></tr>
          <tr v-for="customer in customers?.data" :key="customer.id" class="border-b border-neutral-50">
            <td class="p-3"><NuxtLink :to="`/admin/clientes/${customer.id}`" class="text-brand-600 hover:underline">{{ customer.name }}</NuxtLink></td>
            <td class="p-3 text-neutral-500">{{ customer.email }}</td>
            <td class="p-3 text-neutral-500">{{ customer.orders_count }}</td>
            <td class="p-3 text-neutral-400">{{ new Date(customer.created_at).toLocaleDateString('pt-BR') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
