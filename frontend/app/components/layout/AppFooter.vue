<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<template>
  <footer class="mt-16 border-t border-brand-50 bg-brand-50/40">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
        <div>
          <h3 class="mb-3 font-display text-lg text-brand-700">Radiance</h3>
          <p class="text-sm text-neutral-500">Joias que contam a sua história.</p>
        </div>

        <div>
          <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-700">Institucional</h4>
          <ul class="space-y-2 text-sm text-neutral-500">
            <li><NuxtLink to="/quem-somos" class="hover:text-brand-600">Quem Somos</NuxtLink></li>
            <li><NuxtLink to="/garantia" class="hover:text-brand-600">Garantia Prata 925</NuxtLink></li>
            <li><NuxtLink to="/trocas-e-devolucoes" class="hover:text-brand-600">Trocas e Devoluções</NuxtLink></li>
            <li><NuxtLink to="/politica-de-privacidade" class="hover:text-brand-600">Política de Privacidade</NuxtLink></li>
            <li><NuxtLink to="/termos-de-uso" class="hover:text-brand-600">Termos de Uso</NuxtLink></li>
          </ul>
        </div>

        <div>
          <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-700">Minha Conta</h4>
          <ul class="space-y-2 text-sm text-neutral-500">
            <li><NuxtLink to="/minha-conta" class="hover:text-brand-600">Meus Dados</NuxtLink></li>
            <li><NuxtLink to="/minha-conta/pedidos" class="hover:text-brand-600">Meus Pedidos</NuxtLink></li>
            <li><NuxtLink to="/carrinho" class="hover:text-brand-600">Carrinho</NuxtLink></li>
          </ul>
        </div>

        <div>
          <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-700">Atendimento</h4>
          <ul class="space-y-2 text-sm text-neutral-500">
            <li>contato@radiance.com.br</li>
            <li>WhatsApp: (11) 90000-0000</li>
          </ul>
        </div>
      </div>

      <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-brand-100 pt-6 text-xs text-neutral-400 sm:flex-row">
        <span>© {{ year }} Radiance · CNPJ 00.000.000/0001-00</span>
        <span>Pagamento via Pix e Cartão de Crédito</span>
      </div>
    </div>
  </footer>
</template>
