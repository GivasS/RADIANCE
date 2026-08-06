<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

const { request, mutate } = useApi()

const { data: rates, refresh } = await useAsyncData('admin-shipping-rates', () =>
  request<{ rates: any[] }>('/api/admin/shipping-rates').then(r => r.rates))

const editing = ref<number | null>(null)
const form = reactive({ name: '', state: '', price: '', delivery_days: '7', free_above: '', position: 0, active: true })
const saving = ref(false)
const { success, error: toastError } = useToast()

function startCreate() {
  editing.value = -1
  Object.assign(form, { name: '', state: '', price: '', delivery_days: '7', free_above: '', position: 0, active: true })
}

function startEdit(rate: any) {
  editing.value = rate.id
  Object.assign(form, {
    name: rate.name, state: rate.state ?? '', price: rate.price, delivery_days: rate.delivery_days,
    free_above: rate.free_above ?? '', position: rate.position, active: rate.active,
  })
}

async function save() {
  saving.value = true
  try {
    const body = { ...form, state: form.state || null, free_above: form.free_above || null }
    if (editing.value === -1) {
      await mutate('/api/admin/shipping-rates', { method: 'POST', body })
    } else {
      await mutate(`/api/admin/shipping-rates/${editing.value}`, { method: 'PUT', body })
    }
    editing.value = null
    await refresh()
    success('Faixa de frete salva.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível salvar a faixa de frete.'))
  } finally {
    saving.value = false
  }
}

async function destroyRate(id: number) {
  if (!confirm('Remover essa faixa de frete?')) return
  try {
    await mutate(`/api/admin/shipping-rates/${id}`, { method: 'DELETE' })
    await refresh()
    success('Faixa de frete removida.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível remover a faixa de frete.'))
  }
}

useHead({ title: 'Frete — Admin Radiance' })
</script>

<template>
  <div class="max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-neutral-800">Frete</h1>
      <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white hover:bg-brand-700" @click="startCreate">+ Nova faixa</button>
    </div>

    <form v-if="editing !== null" class="mb-6 grid grid-cols-2 gap-3 rounded-xl border border-neutral-200 bg-white p-4" @submit.prevent="save">
      <input v-model="form.name" placeholder="Nome (ex: PAC - Sudeste)" required class="col-span-2 rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.state" placeholder="UF (vazio = qualquer)" maxlength="2" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.price" type="number" step="0.01" placeholder="Preço" required class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.delivery_days" type="number" placeholder="Prazo (dias)" required class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.free_above" type="number" step="0.01" placeholder="Grátis acima de (opcional)" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <label class="col-span-2 flex items-center gap-1 text-sm text-neutral-600"><input v-model="form.active" type="checkbox" class="accent-brand-600"> Ativa</label>
      <div class="col-span-2 flex gap-2">
        <button type="submit" :disabled="saving" class="rounded-lg bg-brand-600 px-4 py-1.5 text-sm text-white hover:bg-brand-700">Salvar</button>
        <button type="button" class="text-sm text-neutral-400" @click="editing = null">Cancelar</button>
      </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase text-neutral-400">
            <th class="p-3">Nome</th>
            <th class="p-3">UF</th>
            <th class="p-3">Preço</th>
            <th class="p-3">Prazo</th>
            <th class="p-3" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="rate in rates" :key="rate.id" class="border-b border-neutral-50">
            <td class="p-3 text-neutral-800">{{ rate.name }}</td>
            <td class="p-3 text-neutral-500">{{ rate.state ?? 'Qualquer' }}</td>
            <td class="p-3 text-neutral-500">R$ {{ rate.price }}</td>
            <td class="p-3 text-neutral-500">{{ rate.delivery_days }} dias</td>
            <td class="p-3 text-right">
              <button type="button" class="mr-3 text-brand-600 hover:underline" @click="startEdit(rate)">Editar</button>
              <button type="button" class="text-red-500 hover:underline" @click="destroyRate(rate.id)">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
