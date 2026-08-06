<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin' })

const TRANSITIONS: Record<string, string[]> = {
  pendente: ['aguardando_pagamento', 'cancelado'],
  aguardando_pagamento: ['pago', 'expirado', 'cancelado'],
  pago: ['separando', 'estornado'],
  separando: ['enviado', 'estornado'],
  enviado: ['entregue'],
  entregue: [],
  cancelado: [],
  expirado: [],
  estornado: [],
}

const route = useRoute()
const { request, mutate } = useApi()

const orderId = computed(() => route.params.id as string)

const { data, refresh } = await useAsyncData(
  () => `admin-order-${orderId.value}`,
  () => request<{ order: any, timeline: { label: string, at: string }[] }>(`/api/admin/orders/${orderId.value}`),
)

const nextStatuses = computed(() => TRANSITIONS[data.value?.order?.status] ?? [])
const { success, error: toastError } = useToast()

const changingStatus = ref(false)
async function changeStatus(status: string) {
  if (!confirm(`Mudar status para "${status}"?`)) return
  changingStatus.value = true
  try {
    await mutate(`/api/admin/orders/${orderId.value}/status`, { method: 'PUT', body: { status } })
    await refresh()
    success('Status atualizado.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível mudar o status do pedido.'))
  } finally {
    changingStatus.value = false
  }
}

const trackingCode = ref('')
const savingTracking = ref(false)
async function saveTracking() {
  if (!trackingCode.value) return
  savingTracking.value = true
  try {
    await mutate(`/api/admin/orders/${orderId.value}/tracking`, { method: 'PUT', body: { tracking_code: trackingCode.value } })
    await refresh()
    success('Código de rastreio salvo.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível salvar o código de rastreio.'))
  } finally {
    savingTracking.value = false
  }
}

const cancelling = ref(false)
async function cancelOrder() {
  if (!confirm('Cancelar este pedido? Se já estava pago, o estoque será devolvido.')) return
  cancelling.value = true
  try {
    await mutate(`/api/admin/orders/${orderId.value}/cancel`, { method: 'POST' })
    await refresh()
    success('Pedido cancelado.')
  } catch (e: any) {
    toastError(apiErrorMessage(e, 'Não foi possível cancelar o pedido.'))
  } finally {
    cancelling.value = false
  }
}

function formatCurrency(v: string | number) {
  return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

useHead({ title: () => `Pedido ${data.value?.order?.order_number} — Admin Radiance` })
</script>

<template>
  <div v-if="data" class="max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-neutral-800">{{ data.order.order_number }}</h1>
      <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600">{{ data.order.status }}</span>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <!-- Itens -->
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-neutral-700">Itens</h2>
          <table class="w-full text-sm">
            <tbody>
              <tr v-for="item in data.order.items" :key="item.id" class="border-b border-neutral-50">
                <td class="py-2">{{ item.product_name }} <span class="text-neutral-400">({{ item.variant_label }})</span></td>
                <td class="py-2 text-neutral-500">{{ item.quantity }}x</td>
                <td class="py-2 text-right">{{ formatCurrency(item.line_total) }}</td>
              </tr>
            </tbody>
          </table>
          <div class="mt-3 space-y-1 border-t border-neutral-100 pt-3 text-sm">
            <div class="flex justify-between text-neutral-500"><span>Subtotal</span><span>{{ formatCurrency(data.order.subtotal) }}</span></div>
            <div class="flex justify-between text-neutral-500"><span>Desconto</span><span>−{{ formatCurrency(data.order.discount_total) }}</span></div>
            <div class="flex justify-between text-neutral-500"><span>Frete</span><span>{{ formatCurrency(data.order.shipping_total) }}</span></div>
            <div class="flex justify-between font-semibold text-neutral-800"><span>Total</span><span>{{ formatCurrency(data.order.total) }}</span></div>
          </div>
        </div>

        <!-- Cliente e endereco -->
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-neutral-700">Cliente</h2>
          <p class="text-sm text-neutral-600">{{ data.order.customer_snapshot?.name }} — {{ data.order.customer_snapshot?.email }}</p>
          <p class="text-sm text-neutral-500">{{ data.order.customer_snapshot?.cpf }} · {{ data.order.customer_snapshot?.phone }}</p>

          <h2 class="mb-1 mt-4 text-sm font-semibold text-neutral-700">Endereço de entrega</h2>
          <p class="text-sm text-neutral-500">
            {{ data.order.shipping_snapshot?.street }}, {{ data.order.shipping_snapshot?.number }}
            — {{ data.order.shipping_snapshot?.district }}, {{ data.order.shipping_snapshot?.city }}/{{ data.order.shipping_snapshot?.state }}
          </p>
          <p class="text-sm text-neutral-500">{{ data.order.shipping_method }} — CEP {{ data.order.shipping_snapshot?.zipcode }}</p>
        </div>

        <!-- Pagamento -->
        <div v-if="data.order.payment" class="rounded-xl border border-neutral-200 bg-white p-5">
          <h2 class="mb-2 text-sm font-semibold text-neutral-700">Pagamento</h2>
          <p class="text-sm text-neutral-500">Método: {{ data.order.payment.method }} · Status: {{ data.order.payment.status }}</p>
          <p class="text-sm text-neutral-500">TxID: {{ data.order.payment.efi_txid }}</p>
        </div>

        <!-- Linha do tempo -->
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-neutral-700">Linha do tempo</h2>
          <ul class="space-y-2 text-sm">
            <li v-for="(event, i) in data.timeline" :key="i" class="flex justify-between text-neutral-500">
              <span>{{ event.label }}</span>
              <span>{{ new Date(event.at).toLocaleString('pt-BR') }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Acoes -->
      <div class="space-y-4">
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-neutral-700">Mudar status</h2>
          <div v-if="nextStatuses.length" class="flex flex-wrap gap-2">
            <button
              v-for="status in nextStatuses"
              :key="status"
              type="button"
              :disabled="changingStatus"
              class="rounded-lg border border-brand-200 px-3 py-1.5 text-xs text-brand-700 hover:bg-brand-50"
              @click="changeStatus(status)"
            >
              {{ status }}
            </button>
          </div>
          <p v-else class="text-xs text-neutral-400">Status final, sem transições.</p>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-5">
          <h2 class="mb-2 text-sm font-semibold text-neutral-700">Código de rastreio</h2>
          <p v-if="data.order.tracking_code" class="mb-2 text-sm text-neutral-600">{{ data.order.tracking_code }}</p>
          <div class="flex gap-2">
            <input v-model="trackingCode" placeholder="Código" class="flex-1 rounded border border-neutral-200 px-2 py-1.5 text-sm">
            <button type="button" :disabled="savingTracking" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs text-white hover:bg-brand-700" @click="saveTracking">Salvar</button>
          </div>
        </div>

        <button
          v-if="!['entregue', 'cancelado', 'expirado', 'estornado'].includes(data.order.status)"
          type="button"
          :disabled="cancelling"
          class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm text-red-600 hover:bg-red-50"
          @click="cancelOrder"
        >
          {{ ['pago', 'separando'].includes(data.order.status) ? 'Estornar pedido (devolve estoque)' : 'Cancelar pedido' }}
        </button>
      </div>
    </div>
  </div>
</template>
