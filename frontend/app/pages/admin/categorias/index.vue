<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

interface Category { id: number, parent_id: number | null, name: string, slug: string, active: boolean, position: number, children?: Category[] }

const { request, mutate } = useApi()

const { data: categories, refresh } = await useAsyncData('admin-categories', () =>
  request<{ categories: Category[] }>('/api/admin/categories').then(r => r.categories))

const flatParents = computed(() => categories.value ?? [])

const editing = ref<number | null>(null)
const form = reactive({ name: '', parent_id: '', position: 0, active: true })
const creating = ref(false)
const saving = ref(false)
const { success, error: toastError } = useToast()

function startCreate(parentId: number | null = null) {
  editing.value = -1
  Object.assign(form, { name: '', parent_id: parentId ?? '', position: 0, active: true })
}

function startEdit(cat: Category) {
  editing.value = cat.id
  Object.assign(form, { name: cat.name, parent_id: cat.parent_id ?? '', position: cat.position, active: cat.active })
}

async function save() {
  saving.value = true
  try {
    if (editing.value === -1) {
      await mutate('/api/admin/categories', { method: 'POST', body: { ...form, parent_id: form.parent_id || null } })
    } else {
      await mutate(`/api/admin/categories/${editing.value}`, { method: 'PUT', body: { ...form, parent_id: form.parent_id || null } })
    }
    editing.value = null
    await refresh()
    success('Categoria salva.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível salvar a categoria.'))
  } finally {
    saving.value = false
  }
}

async function destroyCategory(cat: Category) {
  if (!confirm(`Apagar a categoria "${cat.name}"?`)) return
  try {
    await mutate(`/api/admin/categories/${cat.id}`, { method: 'DELETE' })
    await refresh()
    success('Categoria excluída.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível excluir a categoria.'))
  }
}

useHead({ title: 'Categorias — Admin Radiance' })
</script>

<template>
  <div class="max-w-2xl">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-neutral-800">Categorias</h1>
      <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white hover:bg-brand-700" @click="startCreate()">
        + Nova categoria
      </button>
    </div>

    <form v-if="editing !== null" class="mb-6 flex flex-wrap items-end gap-3 rounded-xl border border-neutral-200 bg-white p-4" @submit.prevent="save">
      <div>
        <label class="mb-1 block text-xs text-neutral-500">Nome</label>
        <input v-model="form.name" required class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs text-neutral-500">Categoria pai</label>
        <select v-model="form.parent_id" class="rounded border border-neutral-200 px-2 py-1.5 text-sm">
          <option value="">Nenhuma (categoria principal)</option>
          <option v-for="c in flatParents" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-xs text-neutral-500">Posição</label>
        <input v-model="form.position" type="number" class="w-16 rounded border border-neutral-200 px-2 py-1.5 text-sm">
      </div>
      <label class="flex items-center gap-1 text-sm text-neutral-600"><input v-model="form.active" type="checkbox" class="accent-brand-600"> Ativa</label>
      <button type="submit" :disabled="saving" class="rounded-lg bg-brand-600 px-4 py-1.5 text-sm text-white hover:bg-brand-700">Salvar</button>
      <button type="button" class="text-sm text-neutral-400" @click="editing = null">Cancelar</button>
    </form>

    <div class="space-y-3">
      <div v-for="cat in categories" :key="cat.id" class="rounded-xl border border-neutral-200 bg-white p-4">
        <div class="flex items-center justify-between">
          <span class="font-medium text-neutral-800">{{ cat.name }}</span>
          <div class="flex gap-3 text-xs">
            <button type="button" class="text-brand-600 hover:underline" @click="startCreate(cat.id)">+ subcategoria</button>
            <button type="button" class="text-neutral-500 hover:underline" @click="startEdit(cat)">Editar</button>
            <button type="button" class="text-red-500 hover:underline" @click="destroyCategory(cat)">Excluir</button>
          </div>
        </div>
        <div v-if="cat.children?.length" class="mt-2 space-y-1 border-t border-neutral-100 pt-2">
          <div v-for="child in cat.children" :key="child.id" class="flex items-center justify-between pl-4 text-sm">
            <span class="text-neutral-600">{{ child.name }}</span>
            <div class="flex gap-3 text-xs">
              <button type="button" class="text-neutral-500 hover:underline" @click="startEdit(child)">Editar</button>
              <button type="button" class="text-red-500 hover:underline" @click="destroyCategory(child)">Excluir</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
