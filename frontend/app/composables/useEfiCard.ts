interface Installment {
  installment: number
  has_interest: boolean
  value: number
  currency: string
  interest_percentage: number
}

interface CreditCardData {
  brand: string
  number: string
  cvv: string
  expirationMonth: string
  expirationYear: string
  holderName: string
  holderDocument: string
}

/**
 * Tokenização de cartão via SDK da EFI — roda só no navegador (os dados do
 * cartão nunca chegam no nosso backend, só o payment_token resultante).
 * Import dinâmico pra garantir que essa lib nunca entra no bundle/execução SSR.
 *
 * IMPORTANTE: o `EfiPay.CreditCard` do SDK é um Proxy que lança erro pra
 * qualquer propriedade fora de uma lista fixa — "then" não está nela. Uma
 * função async que *retorna* esse objeto builder (sem chamar o método final)
 * dispara esse erro sozinha, porque o JS tenta ler `.then` nele pra ver se é
 * "thenable". Por isso o builder inteiro (setAccount → ... → getX()) tem que
 * ser montado numa expressão só, e só o Promise real que getX() devolve é
 * que pode ser aguardado.
 */
export function useEfiCard() {
  const config = useRuntimeConfig()

  async function loadEfiPay() {
    const { default: EfiPay } = await import('payment-token-efi')
    return EfiPay
  }

  function baseBuilder(EfiPay: Awaited<ReturnType<typeof loadEfiPay>>) {
    const environment = config.public.efiSandbox ? 'sandbox' : 'production'
    return EfiPay.CreditCard.setAccount(config.public.efiAccountId as string).setEnvironment(environment)
  }

  async function fetchInstallments(brand: string, totalInCents: number): Promise<Installment[]> {
    const EfiPay = await loadEfiPay()
    const result = await baseBuilder(EfiPay).setBrand(brand).setTotal(totalInCents).getInstallments()

    if ('error' in result) throw new Error(result.error_description || 'Não foi possível consultar as parcelas.')

    return result.installments
  }

  async function tokenizeCard(data: CreditCardData): Promise<{ paymentToken: string, cardMask: string }> {
    const EfiPay = await loadEfiPay()
    const result = await baseBuilder(EfiPay).setCreditCardData({ ...data, reuse: false }).getPaymentToken()

    if ('error' in result) throw new Error(result.error_description || 'Não foi possível validar o cartão.')

    return { paymentToken: result.payment_token, cardMask: result.card_mask }
  }

  return { fetchInstallments, tokenizeCard }
}
