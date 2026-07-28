<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

const { request, mutate } = useApi()

const { data: products, refresh } = await useAsyncData('admin-products-trash', () =>
  request<{ products: any[] }>('/api/admin/products/trashed').then(r => r.products))

async function restore(id: number) {
  await mutate(`/api/admin/products/${id}/restore`, { method: 'POST' })
  await refresh()
}

useHead({ title: 'Lixeira de Produtos — Admin Radiance' })
</script>

<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-neutral-800">Lixeira de Produtos</h1>
      <NuxtLink to="/admin/produtos" class="text-sm text-brand-600 hover:underline">← Voltar</NuxtLink>
    </div>

    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase text-neutral-400">
            <th class="p-3">Produto</th>
            <th class="p-3">SKU</th>
            <th class="p-3">Categoria</th>
            <th class="p-3" />
          </tr>
        </thead>
        <tbody>
          <tr v-if="!products?.length"><td colspan="4" class="p-6 text-center text-neutral-400">Lixeira vazia.</td></tr>
          <tr v-for="product in products" :key="product.id" class="border-b border-neutral-50">
            <td class="p-3 text-neutral-600">{{ product.name }}</td>
            <td class="p-3 text-neutral-500">{{ product.sku }}</td>
            <td class="p-3 text-neutral-500">{{ product.category?.name }}</td>
            <td class="p-3 text-right">
              <button type="button" class="text-brand-600 hover:underline" @click="restore(product.id)">Restaurar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
