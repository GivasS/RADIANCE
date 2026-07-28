<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { user, refreshUser } = useAuth()
const { mutate } = useApi()
const { refreshCart } = useCart()
const router = useRouter()

const form = reactive({
  name: user.value?.name ?? '',
  email: user.value?.email ?? '',
  phone: user.value?.phone ?? '',
  cpf: user.value?.cpf ?? '',
})

const saving = ref(false)

// TODO: falta o endpoint no backend pra salvar esses dados (PUT /api/auth/profile
// ainda nao existe) - por enquanto o formulario so mostra os dados atuais.
function save() {
  saving.value = true
  setTimeout(() => { saving.value = false }, 500)
}

const loggingOut = ref(false)
async function logout() {
  loggingOut.value = true
  try {
    await mutate('/api/auth/logout', { method: 'POST' })
    await refreshUser()
    await refreshCart()
    router.push('/')
  } finally {
    loggingOut.value = false
  }
}

useHead({ title: 'Meus Dados — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <h1 class="font-display text-2xl text-brand-700">Minha Conta</h1>
    <p class="mt-1 text-sm text-neutral-500">Olá, {{ user?.name }}</p>

    <div class="mt-6">
      <AccountTabs />
    </div>

    <form class="mt-8 grid max-w-lg gap-5" @submit.prevent="save">
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">Nome</label>
        <input
          v-model="form.name"
          type="text"
          class="w-full rounded-lg border border-brand-100 px-4 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
        >
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">E-mail</label>
        <input
          v-model="form.email"
          type="email"
          class="w-full rounded-lg border border-brand-100 px-4 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
        >
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">Telefone</label>
        <input
          v-model="form.phone"
          type="tel"
          placeholder="(11) 90000-0000"
          class="w-full rounded-lg border border-brand-100 px-4 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
        >
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">CPF</label>
        <input
          v-model="form.cpf"
          type="text"
          placeholder="000.000.000-00"
          class="w-full rounded-lg border border-brand-100 px-4 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
        >
      </div>

      <div class="flex items-center gap-4 pt-2">
        <button
          type="submit"
          :disabled="saving"
          class="rounded-full bg-brand-600 px-6 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
        >
          {{ saving ? 'Salvando...' : 'Salvar alterações' }}
        </button>
        <NuxtLink to="/recuperar-senha" class="text-sm text-neutral-500 hover:text-brand-600">
          Alterar senha
        </NuxtLink>
      </div>
    </form>

    <button
      type="button"
      :disabled="loggingOut"
      class="mt-10 text-sm text-neutral-400 hover:text-red-600"
      @click="logout"
    >
      {{ loggingOut ? 'Saindo...' : 'Sair da conta' }}
    </button>
  </div>
</template>
