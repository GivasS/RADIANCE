<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

interface Setting { id: number, key_name: string, value: string, type: string, description: string | null }

const { request, mutate } = useApi()

const { data: settings, refresh } = await useAsyncData('admin-settings', () =>
  request<{ settings: Setting[] }>('/api/admin/settings').then(r => r.settings))

const values = reactive<Record<string, string>>({})
watchEffect(() => {
  settings.value?.forEach(s => { values[s.key_name] = s.value })
})

const saving = ref(false)
const saved = ref(false)

async function save() {
  saving.value = true
  saved.value = false
  try {
    await mutate('/api/admin/settings', {
      method: 'PUT',
      body: { settings: Object.entries(values).map(([key_name, value]) => ({ key_name, value })) },
    })
    await refresh()
    saved.value = true
    setTimeout(() => { saved.value = false }, 2000)
  } finally {
    saving.value = false
  }
}

useHead({ title: 'Configurações — Admin Radiance' })
</script>

<template>
  <div class="max-w-xl">
    <h1 class="mb-6 text-xl font-semibold text-neutral-800">Configurações</h1>

    <form class="space-y-4 rounded-xl border border-neutral-200 bg-white p-6" @submit.prevent="save">
      <div v-for="setting in settings" :key="setting.id">
        <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">{{ setting.description ?? setting.key_name }}</label>
        <input
          v-model="values[setting.key_name]"
          :type="setting.type === 'int' || setting.type === 'decimal' ? 'number' : 'text'"
          class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none"
        >
      </div>

      <button type="submit" :disabled="saving" class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
        {{ saving ? 'Salvando...' : 'Salvar configurações' }}
      </button>
      <span v-if="saved" class="ml-3 text-sm text-accent-700">Salvo!</span>
    </form>
  </div>
</template>
