<script setup lang="ts">
const { mutate } = useApi()
const { refreshUser } = useAuth()
const { refreshCart, cart } = useCart()
const route = useRoute()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

async function submit() {
  submitting.value = true
  errors.value = {}
  generalError.value = ''

  try {
    const previousItemCount = cart.value?.items?.length ?? 0

    const { cart_merge } = await mutate<{ user: any, cart_merge: { merged: boolean } | null }>('/api/auth/login', {
      method: 'POST',
      body: form,
    })

    await refreshUser()
    await refreshCart()

    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (e: any) {
    if (e?.response?.status === 422) {
      errors.value = e.data?.errors ?? e.response?._data?.errors ?? {}
      generalError.value = Object.values(errors.value)[0]?.[0] ?? 'Verifique os dados informados.'
    } else {
      generalError.value = 'Não foi possível entrar. Tente novamente.'
    }
  } finally {
    submitting.value = false
  }
}

useHead({ title: 'Entrar — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
    <h1 class="font-display text-2xl text-brand-700">Entrar</h1>
    <p class="mt-2 text-sm text-neutral-500">
      Ainda não tem conta?
      <NuxtLink :to="{ path: '/cadastro', query: route.query }" class="font-medium text-brand-600 hover:underline">Cadastre-se</NuxtLink>
    </p>

    <form class="mt-8 space-y-4" @submit.prevent="submit">
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">E-mail</label>
        <input
          v-model="form.email"
          type="email"
          required
          class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
        >
      </div>

      <div>
        <div class="mb-1 flex items-center justify-between">
          <label class="text-sm font-medium text-neutral-700">Senha</label>
          <NuxtLink to="/recuperar-senha" class="text-xs text-brand-600 hover:underline">Esqueci minha senha</NuxtLink>
        </div>
        <input
          v-model="form.password"
          type="password"
          required
          class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
        >
      </div>

      <p v-if="generalError" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ generalError }}</p>

      <button
        type="submit"
        :disabled="submitting"
        class="w-full rounded-full bg-brand-600 py-3 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
      >
        {{ submitting ? 'Entrando...' : 'Entrar' }}
      </button>
    </form>
  </div>
</template>
