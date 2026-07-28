export interface StoreSettings {
  store_name: string
  store_email: string
  store_whatsapp: string
  free_shipping_threshold: number
  max_installments: number
  min_installment_value: number
  pix_discount_percent: number
  low_stock_alert: number
}

/**
 * Configuracoes publicas da loja (parcelamento, desconto Pix, frete gratis...).
 * Usado pro calculo de preco/selo nos cards de produto.
 */
export function useSettings() {
  const { request } = useApi()

  return useAsyncData<StoreSettings>('store-settings', () =>
    request<StoreSettings>('/api/settings/public'), {
    default: () => ({
      store_name: 'Radiance',
      store_email: '',
      store_whatsapp: '',
      free_shipping_threshold: 219,
      max_installments: 3,
      min_installment_value: 20,
      pix_discount_percent: 5,
      low_stock_alert: 5,
    }),
  })
}
