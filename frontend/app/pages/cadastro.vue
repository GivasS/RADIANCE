<script setup lang="ts">
const { mutate } = useApi()
const { refreshUser } = useAuth()
const { refreshCart } = useCart()
const route = useRoute()
const router = useRouter()

const form = reactive({
  name: '', email: '', cpf: '', phone: '', password: '', password_confirmation: '',
})
const acceptedTerms = ref(false)
const submitting = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

function maskCpf(v: string) {
  const n = v.replace(/\D/g, '').slice(0, 11)
  return n.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')
}
function maskPhone(v: string) {
  const n = v.replace(/\D/g, '').slice(0, 11)
  if (n.length > 10) return n.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
  if (n.length > 6) return n.replace(/(\d{2})(\d{4})(\d*)/, '($1) $2-$3')
  if (n.length > 2) return n.replace(/(\d{2})(\d*)/, '($1) $2')
  return n
}

// Indicador de forca da senha
const passwordStrength = computed(() => {
  const p = form.password
  let score = 0
  if (p.length >= 8) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  return score
})
const strengthLabel = computed(() => ['Muito fraca', 'Fraca', 'Razoável', 'Boa', 'Forte'][passwordStrength.value])
const strengthColor = computed(() => ['bg-red-400', 'bg-orange-400', 'bg-amber-400', 'bg-lime-500', 'bg-accent-600'][passwordStrength.value])

async function submit() {
  if (!acceptedTerms.value) {
    generalError.value = 'Você precisa aceitar os termos de uso.'
    return
  }

  submitting.value = true
  errors.value = {}
  generalError.value = ''

  try {
    await mutate('/api/auth/register', { method: 'POST', body: form })
    await refreshUser()
    await refreshCart()

    const redirect = (route.query.redirect as string) || '/'
    router.push(redirect)
  } catch (e: any) {
    if (e?.response?.status === 422) {
      errors.value = e.response?._data?.errors ?? {}
      generalError.value = Object.values(errors.value)[0]?.[0] ?? 'Verifique os dados informados.'
    } else {
      generalError.value = 'Não foi possível criar sua conta. Tente novamente.'
    }
  } finally {
    submitting.value = false
  }
}

useHead({ title: 'Criar Conta — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-md px-4 py-16 sm:px-6 lg:px-8">
    <h1 class="font-display text-2xl text-brand-700">Criar Conta</h1>
    <p class="mt-2 text-sm text-neutral-500">
      Já tem conta?
      <NuxtLink :to="{ path: '/login', query: route.query }" class="font-medium text-brand-600 hover:underline">Entrar</NuxtLink>
    </p>

    <form class="mt-8 space-y-4" @submit.prevent="submit">
      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">Nome completo</label>
        <input v-model="form.name" type="text" required class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400">
        <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">E-mail</label>
        <input v-model="form.email" type="email" required class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400">
        <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email[0] }}</p>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-neutral-700">CPF</label>
          <input
            :value="form.cpf"
            type="text"
            required
            placeholder="000.000.000-00"
            class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
            @input="form.cpf = maskCpf(($event.target as HTMLInputElement).value)"
          >
          <p v-if="errors.cpf" class="mt-1 text-xs text-red-600">{{ errors.cpf[0] }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-neutral-700">Telefone</label>
          <input
            :value="form.phone"
            type="text"
            placeholder="(11) 90000-0000"
            class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
            @input="form.phone = maskPhone(($event.target as HTMLInputElement).value)"
          >
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">Senha</label>
        <input v-model="form.password" type="password" required class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400">
        <div v-if="form.password" class="mt-2">
          <div class="flex gap-1">
            <span v-for="n in 4" :key="n" class="h-1 flex-1 rounded-full" :class="n <= passwordStrength ? strengthColor : 'bg-neutral-100'" />
          </div>
          <p class="mt-1 text-xs text-neutral-400">{{ strengthLabel }}</p>
        </div>
        <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password[0] }}</p>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-neutral-700">Confirmar senha</label>
        <input v-model="form.password_confirmation" type="password" required class="w-full rounded-lg border border-brand-100 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400">
      </div>

      <label class="flex items-start gap-2 text-xs text-neutral-500">
        <input v-model="acceptedTerms" type="checkbox" class="mt-0.5 accent-brand-600">
        <span>
          Li e aceito os <NuxtLink to="/termos-de-uso" class="text-brand-600 underline" target="_blank">Termos de Uso</NuxtLink>
          e a <NuxtLink to="/politica-de-privacidade" class="text-brand-600 underline" target="_blank">Política de Privacidade</NuxtLink>.
        </span>
      </label>

      <p v-if="generalError" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ generalError }}</p>

      <button
        type="submit"
        :disabled="submitting"
        class="w-full rounded-full bg-brand-600 py-3 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
      >
        {{ submitting ? 'Criando conta...' : 'Criar conta' }}
      </button>
    </form>
  </div>
</template>
