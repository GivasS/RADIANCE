<script setup lang="ts">
import { Gem, LayoutDashboard, Menu, Package, Settings, Tags, Ticket, Truck, Users } from 'lucide-vue-next'

const { user, refreshUser } = useAuth()
const { mutate } = useApi()
const router = useRouter()
const route = useRoute()

const links = [
  { to: '/admin', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/admin/produtos', label: 'Produtos', icon: Gem },
  { to: '/admin/categorias', label: 'Categorias', icon: Tags },
  { to: '/admin/pedidos', label: 'Pedidos', icon: Package },
  { to: '/admin/cupons', label: 'Cupons', icon: Ticket },
  { to: '/admin/frete', label: 'Frete', icon: Truck },
  { to: '/admin/clientes', label: 'Clientes', icon: Users },
  { to: '/admin/configuracoes', label: 'Configurações', icon: Settings },
]

const mobileOpen = ref(false)

async function logout() {
  await mutate('/api/auth/logout', { method: 'POST' })
  await refreshUser()
  router.push('/admin/login')
}
</script>

<template>
  <div class="flex min-h-screen bg-neutral-50 text-neutral-800">
    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transform border-r border-neutral-200 bg-white transition-transform lg:static lg:translate-x-0"
      :class="mobileOpen ? 'translate-x-0' : ''"
    >
      <div class="flex h-16 items-center border-b border-neutral-100 px-6">
        <span class="font-display text-lg text-brand-700">Radiance <span class="text-xs font-sans text-neutral-400">admin</span></span>
      </div>

      <nav class="p-3">
        <NuxtLink
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-neutral-600 hover:bg-brand-50 hover:text-brand-700"
          :class="route.path === link.to ? 'bg-brand-50 font-medium text-brand-700' : ''"
          @click="mobileOpen = false"
        >
          <component :is="link.icon" :size="18" /> {{ link.label }}
        </NuxtLink>
      </nav>
    </aside>

    <div v-if="mobileOpen" class="fixed inset-0 z-30 bg-black/30 lg:hidden" @click="mobileOpen = false" />

    <!-- Conteudo -->
    <div class="flex-1">
      <header class="flex h-16 items-center justify-between border-b border-neutral-200 bg-white px-4 sm:px-6">
        <button class="text-neutral-500 lg:hidden" @click="mobileOpen = true"><Menu :size="22" /></button>
        <span class="hidden text-sm text-neutral-400 lg:block" />
        <div class="flex items-center gap-4">
          <span class="text-sm text-neutral-600">{{ user?.name }}</span>
          <button type="button" class="text-sm text-neutral-400 hover:text-red-600" @click="logout">Sair</button>
        </div>
      </header>

      <main class="p-4 sm:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
