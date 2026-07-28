<script setup lang="ts">
const { mutate } = useApi()
const route = useRoute()

const hasToken = computed(() => !!route.query.token && !!route.query.email)

// Etapa 1: solicitar link
const email = ref('')
const requestSent = ref(false)
const requesting = ref(false)

async function requestReset() {
  requesting.value = true
  try {
    await mutate('/api/auth/forgot-password', { method: 'POST', body: { email: email.value } })
    requestSent.value = true
  } finally {
    requesting.value = false
  }
}

// Etapa 2: definir nova senha
const password = ref('')
const passwordConfirmation = ref('')
const resetting = ref(false)
const resetError = ref('')
const resetDone = ref(false)

async function resetPassword() {
  resetting.value = true
  resetError.value = ''
  try {
    await mutate('/api/auth/reset-password', {
      method: 'POST',
      body: {
        token: route.query.token,
        email: route.query.email,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
      },
    })
    resetDone.value = true
  } catch (e: any) {
    resetError.value = e?.response?._data?.errors?.token?.[0] ?? 'Não foi possível redefinir a senha. O link pode ter expirado.'
  } finally {
    resetting.value = false
  }
}

useHead({ title: 'Recuperar Senha — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
    <!-- Etapa 2: definir nova senha -->
    <template v-if="hasToken">
      <h1 class="font-display text-2xl text-brand-700">Definir nova senha</h1>

      <div v-if="resetDone" class="mt-8 rounded-lg bg-accent-50 p-4 text-sm text-accent-700">
        Senha redefinida com sucesso!
        <NuxtLink to="/login" class="mt-2 block font-medium underline">Fazer login</NuxtLink>
      </div>

      <form v-else class="mt-8 space-y-4" @submit.prevent="resetPassword">
        <div>
          <label class="mb-1 block text-sm font-medium text-neutral-700">Nova senha</label>
          <input v-model="password" type="password" required class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-neutral-700">Confirmar nova senha</label>
          <input v-model="passwordConfirmation" type="password" required class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <p v-if="resetError" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ resetError }}</p>
        <button type="submit" :disabled="resetting" class="w-full rounded-full bg-brand-600 py-3 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
          {{ resetting ? 'Salvando...' : 'Redefinir senha' }}
        </button>
      </form>
    </template>

    <!-- Etapa 1: solicitar link -->
    <template v-else>
      <h1 class="font-display text-2xl text-brand-700">Recuperar senha</h1>
      <p class="mt-2 text-sm text-neutral-500">Informe seu e-mail e enviaremos um link para redefinir sua senha.</p>

      <div v-if="requestSent" class="mt-8 rounded-lg bg-accent-50 p-4 text-sm text-accent-700">
        Se esse e-mail estiver cadastrado, você vai receber as instruções em instantes.
      </div>

      <form v-else class="mt-8 space-y-4" @submit.prevent="requestReset">
        <div>
          <label class="mb-1 block text-sm font-medium text-neutral-700">E-mail</label>
          <input v-model="email" type="email" required class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
        </div>
        <button type="submit" :disabled="requesting" class="w-full rounded-full bg-brand-600 py-3 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
          {{ requesting ? 'Enviando...' : 'Enviar link de recuperação' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-neutral-500">
        <NuxtLink to="/login" class="text-brand-600 hover:underline">Voltar ao login</NuxtLink>
      </p>
    </template>
  </div>
</template>
