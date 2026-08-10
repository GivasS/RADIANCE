<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

interface Address {
  id: number
  label: string | null
  recipient_name: string
  zipcode: string
  street: string
  number: string
  complement: string | null
  district: string
  city: string
  state: string
  is_default: boolean
}

interface ShippingOption { id: number, name: string, price: number, delivery_days: number, free: boolean }

const { request, mutate } = useApi()
const { user } = useAuth()
const router = useRouter()

// Carrinho
const { data: cart } = await useAsyncData('checkout-cart', () => request<{ items: any[], subtotal: number, has_unavailable: boolean }>('/api/cart'))

if (!cart.value?.items?.length) {
  await navigateTo('/carrinho')
}

// Enderecos
const { data: addresses, refresh: refreshAddresses } = await useAsyncData('checkout-addresses', () =>
  request<{ addresses: Address[] }>('/api/addresses').then(r => r.addresses), { default: () => [] })

const selectedAddressId = ref<number | null>(null)
watchEffect(() => {
  if (addresses.value?.length && !selectedAddressId.value) {
    selectedAddressId.value = (addresses.value.find(a => a.is_default) ?? addresses.value[0]).id
  }
})

const showNewAddressForm = ref(false)
const newAddress = reactive({
  recipient_name: user.value?.name ?? '',
  zipcode: '', street: '', number: '', complement: '', district: '', city: '', state: '', is_default: false,
})
const savingAddress = ref(false)

async function saveNewAddress() {
  savingAddress.value = true
  try {
    const { address } = await mutate<{ address: Address }>('/api/addresses', { method: 'POST', body: newAddress })
    await refreshAddresses()
    selectedAddressId.value = address.id
    showNewAddressForm.value = false
  } finally {
    savingAddress.value = false
  }
}

const selectedAddress = computed(() => addresses.value?.find(a => a.id === selectedAddressId.value))

// Frete
const shippingOptions = ref<ShippingOption[]>([])
const selectedShippingId = ref<number | null>(null)
const loadingShipping = ref(false)

async function loadShipping() {
  if (!selectedAddress.value) return
  loadingShipping.value = true
  try {
    const { options } = await request<{ options: ShippingOption[] }>('/api/cart/shipping/quote', {
      method: 'POST',
      body: { zipcode: selectedAddress.value.zipcode, subtotal: cart.value?.subtotal },
    })
    shippingOptions.value = options
    selectedShippingId.value = options[0]?.id ?? null
  } finally {
    loadingShipping.value = false
  }
}

watch(selectedAddressId, loadShipping, { immediate: true })

const selectedShipping = computed(() => shippingOptions.value.find(o => o.id === selectedShippingId.value))

// Cupom
const couponCode = ref('')
const appliedCoupon = ref<{ code: string, discount: number } | null>(null)
const couponError = ref('')
const applyingCoupon = ref(false)

async function applyCoupon() {
  if (!couponCode.value.trim()) return
  applyingCoupon.value = true
  couponError.value = ''
  try {
    const result = await request<{ coupon: { code: string }, discount: number }>('/api/cart/coupon/validate', {
      method: 'POST',
      params: { code: couponCode.value },
    })
    appliedCoupon.value = { code: result.coupon.code, discount: result.discount }
  } catch {
    couponError.value = 'Cupom inválido ou expirado.'
    appliedCoupon.value = null
  } finally {
    applyingCoupon.value = false
  }
}

// Totais
const subtotal = computed(() => cart.value?.subtotal ?? 0)
const discount = computed(() => appliedCoupon.value?.discount ?? 0)
const shippingCost = computed(() => selectedShipping.value?.price ?? 0)
const total = computed(() => Math.max(0, subtotal.value - discount.value + shippingCost.value))

function formatCurrency(value: number) {
  return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

// Metodo de pagamento
const { fetchInstallments, tokenizeCard } = useEfiCard()

const paymentMethod = ref<'pix' | 'cartao'>('pix')
const cvvFocused = ref(false)
const cardData = reactive({
  number: '', expiry: '', cvv: '', brand: '', cardholderName: '',
  cpf: user.value?.cpf ?? '', birth: '',
})

function maskCardNumber(v: string) {
  return v.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim()
}
function maskExpiry(v: string) {
  const digits = v.replace(/\D/g, '').slice(0, 4)
  return digits.length > 2 ? digits.replace(/(\d{2})(\d*)/, '$1/$2') : digits
}
function maskCpf(v: string) {
  return v.replace(/\D/g, '').slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')
}
function detectBrand(number: string) {
  const n = number.replace(/\s/g, '')
  if (/^4/.test(n)) return 'visa'
  if (/^5[1-5]/.test(n)) return 'mastercard'
  if (/^3[47]/.test(n)) return 'amex'
  if (/^(4011|4312|4389|4514|4576|5041|5066|5090|636368|636297)/.test(n)) return 'elo'
  if (/^(384100|384140|384160|606282|637599|637095|637568)/.test(n)) return 'hipercard'
  return ''
}

// Parcelas
const installmentOptions = ref<{ installment: number, currency: string, has_interest: boolean }[]>([])
const selectedInstallments = ref(1)
const loadingInstallments = ref(false)
const installmentsError = ref('')

async function loadInstallments() {
  if (!cardData.brand || !total.value) return
  loadingInstallments.value = true
  installmentsError.value = ''
  try {
    const installments = await fetchInstallments(cardData.brand, Math.round(total.value * 100))
    installmentOptions.value = installments
    selectedInstallments.value = installments[0]?.installment ?? 1
  } catch (e: any) {
    installmentsError.value = e.message || 'Não foi possível consultar as parcelas.'
    installmentOptions.value = []
  } finally {
    loadingInstallments.value = false
  }
}

function onCardNumberInput() {
  cardData.number = maskCardNumber(cardData.number)
  const brand = detectBrand(cardData.number)
  if (brand !== cardData.brand) {
    cardData.brand = brand
    if (brand) loadInstallments()
  }
}

const cardFormValid = computed(() =>
  cardData.number.replace(/\D/g, '').length >= 15
  && cardData.cardholderName.trim().length > 2
  && /^\d{2}\/\d{2}$/.test(cardData.expiry)
  && cardData.cvv.length >= 3
  && cardData.cpf.replace(/\D/g, '').length === 11
  && cardData.birth
  && cardData.brand,
)

// Finalizar pedido
const submitting = ref(false)
const submitError = ref('')

async function submitOrder() {
  if (!selectedAddressId.value || !selectedShippingId.value) return
  if (paymentMethod.value === 'cartao' && !cardFormValid.value) {
    submitError.value = 'Preencha todos os dados do cartão corretamente.'
    return
  }

  submitting.value = true
  submitError.value = ''

  try {
    const body: Record<string, any> = {
      address_id: selectedAddressId.value,
      shipping_rate_id: selectedShippingId.value,
      coupon_code: appliedCoupon.value?.code ?? null,
      payment_method: paymentMethod.value,
    }

    if (paymentMethod.value === 'cartao') {
      const [expMonth, expYear] = cardData.expiry.split('/')
      const { paymentToken } = await tokenizeCard({
        brand: cardData.brand,
        number: cardData.number.replace(/\s/g, ''),
        cvv: cardData.cvv,
        expirationMonth: expMonth,
        expirationYear: `20${expYear}`,
        holderName: cardData.cardholderName,
        holderDocument: cardData.cpf.replace(/\D/g, ''),
      })

      body.card = {
        payment_token: paymentToken,
        installments: selectedInstallments.value,
        holder_document: cardData.cpf.replace(/\D/g, ''),
        holder_birth: cardData.birth,
      }
    }

    const { order, payment } = await mutate<{ order: any, payment: any }>('/api/checkout', { method: 'POST', body })

    router.push(`/pedido/${order.order_number}?payment_id=${payment.id}`)
  } catch (e: any) {
    submitError.value = e?.data?.message || e?.message || 'Não foi possível finalizar o pedido. Tente novamente.'
  } finally {
    submitting.value = false
  }
}

const canSubmit = computed(() => selectedAddressId.value && selectedShippingId.value && !submitting.value)

useHead({ title: 'Finalizar Compra — Radiance' })
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="font-display text-2xl text-brand-700 sm:text-3xl">Finalizar Compra</h1>

    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[1fr_360px]">
      <div class="space-y-8">
        <!-- Entrega -->
        <section class="rounded-2xl border border-brand-50 p-6">
          <h2 class="mb-4 font-display text-lg text-brand-700">1. Entrega</h2>

          <div v-if="addresses?.length" class="space-y-2">
            <label
              v-for="addr in addresses"
              :key="addr.id"
              class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm"
              :class="selectedAddressId === addr.id ? 'border-brand-500 bg-brand-50/40' : 'border-brand-100'"
            >
              <input v-model="selectedAddressId" type="radio" :value="addr.id" class="mt-1 accent-brand-600">
              <span>
                <strong>{{ addr.street }}, {{ addr.number }}</strong> — {{ addr.district }}, {{ addr.city }}/{{ addr.state }}
                <br><span class="text-neutral-400">CEP {{ addr.zipcode }}</span>
              </span>
            </label>
          </div>

          <button
            type="button"
            class="mt-3 text-sm font-medium text-brand-600 hover:underline"
            @click="showNewAddressForm = !showNewAddressForm"
          >
            {{ showNewAddressForm ? 'Cancelar' : '+ Adicionar novo endereço' }}
          </button>

          <form v-if="showNewAddressForm" class="mt-4 grid grid-cols-2 gap-3" @submit.prevent="saveNewAddress">
            <input v-model="newAddress.recipient_name" placeholder="Nome do destinatário" required class="col-span-2 rounded-lg border border-brand-100 px-3 py-2 text-sm">
            <input v-model="newAddress.zipcode" placeholder="CEP" required class="rounded-lg border border-brand-100 px-3 py-2 text-sm">
            <input v-model="newAddress.state" placeholder="UF" maxlength="2" required class="rounded-lg border border-brand-100 px-3 py-2 text-sm">
            <input v-model="newAddress.street" placeholder="Rua" required class="col-span-2 rounded-lg border border-brand-100 px-3 py-2 text-sm">
            <input v-model="newAddress.number" placeholder="Número" required class="rounded-lg border border-brand-100 px-3 py-2 text-sm">
            <input v-model="newAddress.district" placeholder="Bairro" required class="rounded-lg border border-brand-100 px-3 py-2 text-sm">
            <input v-model="newAddress.city" placeholder="Cidade" required class="rounded-lg border border-brand-100 px-3 py-2 text-sm">
            <input v-model="newAddress.complement" placeholder="Complemento (opcional)" class="rounded-lg border border-brand-100 px-3 py-2 text-sm">
            <button type="submit" :disabled="savingAddress" class="col-span-2 rounded-full bg-brand-600 py-2 text-sm text-white hover:bg-brand-700">
              {{ savingAddress ? 'Salvando...' : 'Salvar endereço' }}
            </button>
          </form>

          <div v-if="shippingOptions.length" class="mt-5 space-y-2 border-t border-brand-50 pt-4">
            <p class="text-sm font-medium text-neutral-700">Frete</p>
            <label
              v-for="opt in shippingOptions"
              :key="opt.id"
              class="flex cursor-pointer items-center justify-between rounded-lg border p-3 text-sm"
              :class="selectedShippingId === opt.id ? 'border-brand-500 bg-brand-50/40' : 'border-brand-100'"
            >
              <span class="flex items-center gap-2">
                <input v-model="selectedShippingId" type="radio" :value="opt.id" class="accent-brand-600">
                {{ opt.name }} — até {{ opt.delivery_days }} dias
              </span>
              <span class="font-medium">{{ opt.free ? 'Grátis' : formatCurrency(opt.price) }}</span>
            </label>
          </div>
          <p v-else-if="loadingShipping" class="mt-4 text-sm text-neutral-400">Calculando frete...</p>
        </section>

        <!-- Cupom -->
        <section class="rounded-2xl border border-brand-50 p-6">
          <h2 class="mb-4 font-display text-lg text-brand-700">2. Cupom de desconto</h2>
          <div class="flex gap-2">
            <input
              v-model="couponCode"
              placeholder="Código do cupom"
              class="flex-1 rounded-lg border border-brand-100 px-3 py-2 text-sm"
            >
            <button
              type="button"
              :disabled="applyingCoupon"
              class="rounded-lg border border-brand-300 px-4 py-2 text-sm text-brand-600 hover:bg-brand-50"
              @click="applyCoupon"
            >
              Aplicar
            </button>
          </div>
          <p v-if="couponError" class="mt-2 text-sm text-red-600">{{ couponError }}</p>
          <p v-if="appliedCoupon" class="mt-2 text-sm text-accent-700">
            Cupom {{ appliedCoupon.code }} aplicado: −{{ formatCurrency(appliedCoupon.discount) }}
          </p>
        </section>

        <!-- Pagamento -->
        <section class="rounded-2xl border border-brand-50 p-6">
          <h2 class="mb-4 font-display text-lg text-brand-700">3. Pagamento</h2>

          <div class="mb-5 flex gap-2">
            <button
              type="button"
              class="flex-1 rounded-lg border py-2 text-sm font-medium"
              :class="paymentMethod === 'pix' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-brand-100 text-neutral-500'"
              @click="paymentMethod = 'pix'"
            >
              Pix (5% de desconto)
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg border py-2 text-sm font-medium"
              :class="paymentMethod === 'cartao' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-brand-100 text-neutral-500'"
              @click="paymentMethod = 'cartao'"
            >
              Cartão de Crédito
            </button>
          </div>

          <div v-if="paymentMethod === 'pix'" class="rounded-lg bg-brand-50/40 p-4 text-sm text-neutral-600">
            Ao confirmar, você vai receber um QR Code Pix para pagar em até 20 minutos.
          </div>

          <div v-else class="space-y-5">
            <CheckoutCreditCardPreview
              :number="cardData.number"
              :name="cardData.cardholderName"
              :expiry="cardData.expiry"
              :cvv="cardData.cvv"
              :brand="cardData.brand"
              :is-flipped="cvvFocused"
            />

            <div class="grid grid-cols-2 gap-3">
              <input v-model="cardData.cardholderName" placeholder="Nome no cartão" class="col-span-2 rounded-lg border border-brand-100 px-3 py-2 text-sm">
              <input
                v-model="cardData.number"
                placeholder="0000 0000 0000 0000"
                maxlength="19"
                class="col-span-2 rounded-lg border border-brand-100 px-3 py-2 text-sm font-mono"
                @input="onCardNumberInput"
              >
              <input v-model="cardData.expiry" placeholder="MM/AA" maxlength="5" class="rounded-lg border border-brand-100 px-3 py-2 text-sm" @input="cardData.expiry = maskExpiry(cardData.expiry)">
              <input
                v-model="cardData.cvv"
                placeholder="CVV"
                maxlength="4"
                class="rounded-lg border border-brand-100 px-3 py-2 text-sm"
                @focus="cvvFocused = true"
                @blur="cvvFocused = false"
              >
              <input v-model="cardData.cpf" placeholder="CPF do titular" maxlength="14" class="rounded-lg border border-brand-100 px-3 py-2 text-sm" @input="cardData.cpf = maskCpf(cardData.cpf)">
              <input v-model="cardData.birth" type="date" placeholder="Data de nascimento" class="rounded-lg border border-brand-100 px-3 py-2 text-sm">

              <div class="col-span-2">
                <select
                  v-if="installmentOptions.length"
                  v-model="selectedInstallments"
                  class="w-full rounded-lg border border-brand-100 px-3 py-2 text-sm"
                >
                  <option v-for="opt in installmentOptions" :key="opt.installment" :value="opt.installment">
                    {{ opt.installment }}x de {{ opt.currency }} {{ opt.has_interest ? '' : '(sem juros)' }}
                  </option>
                </select>
                <p v-else-if="loadingInstallments" class="text-xs text-neutral-400">Consultando parcelas...</p>
                <p v-else-if="installmentsError" class="text-xs text-red-600">{{ installmentsError }}</p>
                <p v-else class="text-xs text-neutral-400">Preencha o número do cartão pra ver as opções de parcelamento.</p>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Resumo -->
      <aside class="h-fit rounded-2xl border border-brand-50 bg-white p-6 shadow-sm">
        <h2 class="font-display text-lg text-brand-700">Resumo do pedido</h2>

        <ul class="mt-4 max-h-52 space-y-2 overflow-y-auto text-sm text-neutral-500">
          <li v-for="item in cart?.items" :key="item.id" class="flex justify-between">
            <span>{{ item.quantity }}x {{ item.product.name }}</span>
            <span>{{ formatCurrency(item.line_total) }}</span>
          </li>
        </ul>

        <div class="mt-4 space-y-1 border-t border-brand-50 pt-4 text-sm text-neutral-600">
          <div class="flex justify-between"><span>Subtotal</span><span>{{ formatCurrency(subtotal) }}</span></div>
          <div v-if="discount > 0" class="flex justify-between text-accent-700"><span>Desconto</span><span>−{{ formatCurrency(discount) }}</span></div>
          <div class="flex justify-between"><span>Frete</span><span>{{ shippingCost > 0 ? formatCurrency(shippingCost) : 'Grátis' }}</span></div>
        </div>

        <div class="mt-3 flex justify-between border-t border-brand-50 pt-3 font-display text-xl text-brand-700">
          <span>Total</span>
          <span>{{ formatCurrency(total) }}</span>
        </div>

        <p v-if="submitError" class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ submitError }}</p>

        <button
          type="button"
          :disabled="!canSubmit"
          class="mt-6 w-full rounded-full bg-brand-600 py-3 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-40"
          @click="submitOrder"
        >
          {{ submitting ? 'Processando...' : 'Confirmar pedido' }}
        </button>
      </aside>
    </div>
  </div>
</template>
