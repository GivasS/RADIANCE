<script setup lang="ts">
definePageMeta({ layout: false })

const { mutate } = useApi()
const { refreshUser, user } = useAuth()
const route = useRoute()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const submitting = ref(false)
const error = ref('')

async function submit() {
  submitting.value = true
  error.value = ''

  try {
    await mutate('/api/auth/login', { method: 'POST', body: form })
    await refreshUser()

    if (user.value?.role !== 'admin') {
      error.value = 'Essa conta não tem acesso ao painel administrativo.'
      return
    }

    router.push((route.query.redirect as string) || '/admin')
  } catch (e: any) {
    error.value = e?.response?.status === 422
      ? 'Credenciais inválidas.'
      : 'Não foi possível entrar. Tente novamente.'
  } finally {
    submitting.value = false
  }
}

useHead({ title: 'Painel Administrativo — Radiance' })
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-neutral-900 px-4">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-xl">
      <h1 class="text-center font-display text-xl text-brand-700">Radiance <span class="block text-xs font-sans text-neutral-400">painel administrativo</span></h1>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">E-mail</label>
          <input v-model="form.email" type="email" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-neutral-500">Senha</label>
          <input v-model="form.password" type="password" required class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <p v-if="error" class="rounded-lg bg-red-50 p-3 text-xs text-red-700">{{ error }}</p>
        <button type="submit" :disabled="submitting" class="w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-50">
          {{ submitting ? 'Entrando...' : 'Entrar' }}
        </button>
      </form>
    </div>
  </div>
</template>
