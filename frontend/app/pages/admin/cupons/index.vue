<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

const { request, mutate } = useApi()

const { data: coupons, refresh } = await useAsyncData('admin-coupons', () =>
  request<{ data: any[] }>('/api/admin/coupons').then(r => r.data))

const editing = ref<number | null>(null)
const form = reactive({
  code: '', description: '', type: 'percent', value: '', min_order_value: '0',
  max_uses: '', max_uses_per_user: '1', expires_at: '', active: true,
})
const saving = ref(false)

function startCreate() {
  editing.value = -1
  Object.assign(form, { code: '', description: '', type: 'percent', value: '', min_order_value: '0', max_uses: '', max_uses_per_user: '1', expires_at: '', active: true })
}

function startEdit(coupon: any) {
  editing.value = coupon.id
  Object.assign(form, {
    code: coupon.code, description: coupon.description ?? '', type: coupon.type, value: coupon.value,
    min_order_value: coupon.min_order_value, max_uses: coupon.max_uses ?? '', max_uses_per_user: coupon.max_uses_per_user,
    expires_at: coupon.expires_at?.slice(0, 10) ?? '', active: coupon.active,
  })
}

async function save() {
  saving.value = true
  try {
    const body = { ...form, max_uses: form.max_uses || null, expires_at: form.expires_at || null }
    if (editing.value === -1) {
      await mutate('/api/admin/coupons', { method: 'POST', body })
    } else {
      await mutate(`/api/admin/coupons/${editing.value}`, { method: 'PUT', body })
    }
    editing.value = null
    await refresh()
  } finally {
    saving.value = false
  }
}

async function destroyCoupon(id: number) {
  if (!confirm('Remover este cupom?')) return
  await mutate(`/api/admin/coupons/${id}`, { method: 'DELETE' })
  await refresh()
}

useHead({ title: 'Cupons — Admin Radiance' })
</script>

<template>
  <div class="max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-neutral-800">Cupons</h1>
      <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white hover:bg-brand-700" @click="startCreate">+ Novo cupom</button>
    </div>

    <form v-if="editing !== null" class="mb-6 grid grid-cols-2 gap-3 rounded-xl border border-neutral-200 bg-white p-4" @submit.prevent="save">
      <input v-model="form.code" placeholder="Código" required class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <select v-model="form.type" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
        <option value="percent">Percentual</option>
        <option value="fixed">Valor fixo</option>
      </select>
      <input v-model="form.value" type="number" step="0.01" placeholder="Valor" required class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.min_order_value" type="number" step="0.01" placeholder="Compra mínima" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.max_uses" type="number" placeholder="Limite total de usos" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.max_uses_per_user" type="number" placeholder="Limite por usuário" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.expires_at" type="date" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <input v-model="form.description" placeholder="Descrição" class="col-span-2 rounded border border-neutral-200 px-2 py-1.5 text-sm">
      <label class="flex items-center gap-1 text-sm text-neutral-600"><input v-model="form.active" type="checkbox" class="accent-brand-600"> Ativo</label>
      <div class="col-span-2 flex gap-2">
        <button type="submit" :disabled="saving" class="rounded-lg bg-brand-600 px-4 py-1.5 text-sm text-white hover:bg-brand-700">Salvar</button>
        <button type="button" class="text-sm text-neutral-400" @click="editing = null">Cancelar</button>
      </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase text-neutral-400">
            <th class="p-3">Código</th>
            <th class="p-3">Valor</th>
            <th class="p-3">Usos</th>
            <th class="p-3">Status</th>
            <th class="p-3" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="coupon in coupons" :key="coupon.id" class="border-b border-neutral-50">
            <td class="p-3 font-medium text-neutral-800">{{ coupon.code }}</td>
            <td class="p-3 text-neutral-500">{{ coupon.type === 'percent' ? `${coupon.value}%` : `R$ ${coupon.value}` }}</td>
            <td class="p-3 text-neutral-500">{{ coupon.used_count }}{{ coupon.max_uses ? ` / ${coupon.max_uses}` : '' }}</td>
            <td class="p-3">
              <span class="rounded-full px-2 py-0.5 text-xs" :class="coupon.active ? 'bg-accent-100 text-accent-700' : 'bg-neutral-100 text-neutral-500'">
                {{ coupon.active ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="p-3 text-right">
              <button type="button" class="mr-3 text-brand-600 hover:underline" @click="startEdit(coupon)">Editar</button>
              <button type="button" class="text-red-500 hover:underline" @click="destroyCoupon(coupon.id)">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
