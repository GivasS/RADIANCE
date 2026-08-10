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
 */
export function useEfiCard() {
  const config = useRuntimeConfig()

  async function loadSdk() {
    const { default: EfiPay } = await import('payment-token-efi')
    const environment = config.public.efiSandbox ? 'sandbox' : 'production'

    return EfiPay.CreditCard.setAccount(config.public.efiAccountId as string).setEnvironment(environment)
  }

  async function fetchInstallments(brand: string, totalInCents: number): Promise<Installment[]> {
    const sdk = await loadSdk()
    const result = await sdk.setBrand(brand).setTotal(totalInCents).getInstallments()

    if ('error' in result) throw new Error(result.error_description || 'Não foi possível consultar as parcelas.')

    return result.installments
  }

  async function tokenizeCard(data: CreditCardData): Promise<{ paymentToken: string, cardMask: string }> {
    const sdk = await loadSdk()
    const result = await sdk.setCreditCardData({ ...data, reuse: false }).getPaymentToken()

    if ('error' in result) throw new Error(result.error_description || 'Não foi possível validar o cartão.')

    return { paymentToken: result.payment_token, cardMask: result.card_mask }
  }

  return { fetchInstallments, tokenizeCard }
}
