<script setup lang="ts">
import { PartyPopper } from 'lucide-vue-next'

definePageMeta({ middleware: 'auth' })

interface OrderDetail {
  id: number
  order_number: string
  status: string
  total: string
  payment?: {
    id: number
    status: string
    method: string
    installments: number
    qr_code: string | null
    qr_code_image: string | null
    copia_e_cola: string | null
    expires_at: string | null
    raw_response: any
  }
}

const route = useRoute()
const { request } = useApi()
const { refreshCart } = useCart()

const orderNumber = computed(() => route.params.orderNumber as string)

const { data: order, refresh } = await useAsyncData<OrderDetail | null>(
  () => `order-${orderNumber.value}`,
  () => request<{ order: OrderDetail }>(`/api/orders/${orderNumber.value}`).then(r => r.order).catch(() => null),
)

const copied = ref(false)
function copyCode() {
  if (!order.value?.payment?.copia_e_cola) return
  navigator.clipboard.writeText(order.value.payment.copia_e_cola)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

// Contador regressivo
const secondsLeft = ref(0)
let countdownTimer: ReturnType<typeof setInterval>
let pollTimer: ReturnType<typeof setInterval>

function updateCountdown() {
  if (!order.value?.payment?.expires_at) return
  const diff = Math.floor((new Date(order.value.payment.expires_at).getTime() - Date.now()) / 1000)
  secondsLeft.value = Math.max(0, diff)
}

const countdownText = computed(() => {
  const m = Math.floor(secondsLeft.value / 60)
  const s = secondsLeft.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})

const isPaid = computed(() => order.value?.status === 'pago' || order.value?.payment?.status === 'aprovado')
const isExpired = computed(() => ['expirado', 'cancelado'].includes(order.value?.status ?? ''))
const isDeclined = computed(() => order.value?.payment?.method === 'credit_card' && order.value?.payment?.status === 'recusado')
const declineReason = computed(() => order.value?.payment?.raw_response?.data?.refusal?.reason ?? 'O cartão foi recusado.')

onMounted(() => {
  updateCountdown()
  countdownTimer = setInterval(updateCountdown, 1000)

  // Polling do status a cada 5s (especificacoes.txt 4.2.9)
  pollTimer = setInterval(async () => {
    if (isPaid.value || isExpired.value || isDeclined.value) return
    await refresh()
    if (isPaid.value) await refreshCart()
  }, 5000)
})

onUnmounted(() => {
  clearInterval(countdownTimer)
  clearInterval(pollTimer)
})

useHead({ title: () => `Pedido ${orderNumber.value} — Radiance` })
</script>

<template>
  <div class="mx-auto max-w-lg px-4 py-16 text-center sm:px-6">
    <div v-if="!order" class="text-neutral-400">Pedido não encontrado.</div>

    <template v-else>
      <!-- Pago -->
      <div v-if="isPaid">
        <PartyPopper :size="48" class="mx-auto text-brand-600" />
        <h1 class="mt-4 font-display text-2xl text-brand-700">Pagamento confirmado!</h1>
        <p class="mt-2 text-neutral-500">
          Pedido <strong>{{ order.order_number }}</strong> — em breve você recebe os próximos passos por e-mail.
        </p>
        <div class="mt-8 flex justify-center gap-3">
          <NuxtLink to="/minha-conta/pedidos" class="rounded-full border border-brand-300 px-6 py-2 text-sm text-brand-600 hover:bg-brand-50">
            Ver meus pedidos
          </NuxtLink>
          <NuxtLink to="/" class="rounded-full bg-brand-600 px-6 py-2 text-sm text-white hover:bg-brand-700">
            Continuar comprando
          </NuxtLink>
        </div>
      </div>

      <!-- Cartao recusado -->
      <div v-else-if="isDeclined">
        <p class="text-5xl">💳</p>
        <h1 class="mt-4 font-display text-2xl text-brand-700">Pagamento recusado</h1>
        <p class="mt-2 text-neutral-500">{{ declineReason }}</p>
        <NuxtLink to="/carrinho" class="mt-6 inline-block rounded-full bg-brand-600 px-6 py-2 text-sm text-white hover:bg-brand-700">
          Tentar novamente
        </NuxtLink>
      </div>

      <!-- Expirado -->
      <div v-else-if="isExpired">
        <p class="text-5xl">⏱️</p>
        <h1 class="mt-4 font-display text-2xl text-brand-700">Cobrança expirada</h1>
        <p class="mt-2 text-neutral-500">O tempo para pagamento acabou. Volte ao carrinho pra tentar novamente.</p>
        <NuxtLink to="/carrinho" class="mt-6 inline-block rounded-full bg-brand-600 px-6 py-2 text-sm text-white hover:bg-brand-700">
          Voltar ao carrinho
        </NuxtLink>
      </div>

      <!-- Aguardando pagamento (Pix) -->
      <div v-else-if="order.payment?.qr_code_image">
        <h1 class="font-display text-2xl text-brand-700">Pague com Pix pra confirmar</h1>
        <p class="mt-1 text-sm text-neutral-500">Pedido {{ order.order_number }} · {{ Number(order.total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}</p>

        <div class="mx-auto mt-6 w-48 overflow-hidden rounded-xl border border-brand-100 p-2">
          <img :src="order.payment.qr_code_image" alt="QR Code Pix" class="w-full">
        </div>

        <p class="mt-3 text-sm font-medium text-brand-700">Expira em {{ countdownText }}</p>

        <div class="mt-5 rounded-xl border border-brand-100 bg-brand-50/30 p-3">
          <p class="break-all font-mono text-xs text-neutral-500">{{ order.payment.copia_e_cola }}</p>
          <button type="button" class="mt-2 rounded-full bg-brand-600 px-4 py-1.5 text-xs text-white hover:bg-brand-700" @click="copyCode">
            {{ copied ? 'Copiado!' : 'Copiar código' }}
          </button>
        </div>

        <ol class="mt-8 space-y-1 text-left text-sm text-neutral-500">
          <li>1. Abra o app do seu banco</li>
          <li>2. Escolha pagar via Pix</li>
          <li>3. Escaneie o QR Code ou cole o código copiado</li>
        </ol>

        <p class="mt-6 text-xs text-neutral-400">Esta página atualiza sozinha assim que o pagamento for aprovado.</p>
      </div>

      <div v-else class="text-neutral-400">
        Pedido {{ order.order_number }} — status: {{ order.status }}
      </div>
    </template>
  </div>
</template>
