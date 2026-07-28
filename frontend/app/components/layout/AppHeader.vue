<script setup lang="ts">
import { Home, Menu, Search, Settings, ShoppingBag, User, X } from 'lucide-vue-next'

const { data: categories } = useCategories()
const { itemCount } = useCart()
const { isLoggedIn, user } = useAuth()

const mobileMenuOpen = ref(false)
const searchTerm = ref('')

const router = useRouter()

function submitSearch() {
  if (!searchTerm.value.trim()) return
  router.push({ path: '/busca', query: { q: searchTerm.value } })
}
</script>

<template>
  <header class="sticky top-0 z-40 bg-white shadow-sm">
    <AnnouncementBar />

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-3 items-center py-4 gap-4">
        <!-- Busca (esquerda) -->
        <div class="hidden md:flex items-center">
          <form class="relative w-full max-w-xs" @submit.prevent="submitSearch">
            <input
              v-model="searchTerm"
              type="search"
              placeholder="Buscar joias..."
              class="w-full rounded-full border border-brand-100 bg-brand-50/50 py-2 pl-4 pr-10 text-sm text-neutral-700 placeholder:text-neutral-400 focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400"
            >
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-brand-500" aria-label="Buscar">
              <Search :size="18" />
            </button>
          </form>
        </div>

        <button class="md:hidden justify-self-start text-brand-700" aria-label="Abrir menu" @click="mobileMenuOpen = true">
          <Menu :size="26" />
        </button>

        <!-- Logo (centro) -->
        <NuxtLink to="/" class="justify-self-center text-center">
          <span class="font-display text-2xl sm:text-3xl tracking-wide text-brand-700">Radiance</span>
        </NuxtLink>

        <!-- Conta / Carrinho (direita) -->
        <div class="flex items-center justify-self-end gap-4 sm:gap-6">
          <NuxtLink to="/" class="flex items-center gap-1 text-neutral-700 hover:text-brand-600" aria-label="Início">
            <Home :size="22" />
            <span class="hidden lg:inline text-sm">Início</span>
          </NuxtLink>

          <NuxtLink
            v-if="isLoggedIn && user?.role === 'admin'"
            to="/admin"
            class="flex items-center gap-1 rounded-full border border-brand-200 px-3 py-1 text-neutral-700 hover:border-brand-400 hover:text-brand-600"
          >
            <Settings :size="18" />
            <span class="hidden lg:inline text-sm">Painel Admin</span>
          </NuxtLink>

          <NuxtLink
            :to="isLoggedIn ? '/minha-conta' : '/login'"
            class="flex items-center gap-1 text-neutral-700 hover:text-brand-600"
          >
            <User :size="22" />
            <span class="hidden lg:inline text-sm">{{ isLoggedIn ? user?.name?.split(' ')[0] : 'Entrar' }}</span>
          </NuxtLink>

          <NuxtLink to="/carrinho" class="flex items-center gap-1 text-neutral-700 hover:text-brand-600">
            <span class="relative flex items-center">
              <ShoppingBag :size="22" />
              <span
                v-if="itemCount > 0"
                class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-accent-600 text-[11px] font-semibold text-white"
              >
                {{ itemCount }}
              </span>
            </span>
            <span class="hidden lg:inline text-sm">Carrinho</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Menu principal (desktop) -->
      <nav class="hidden md:flex justify-center gap-8 border-t border-brand-50 py-3 text-sm tracking-wider">
        <div v-for="category in categories" :key="category.id" class="group relative">
          <NuxtLink
            :to="`/categoria/${category.slug}`"
            class="font-medium uppercase text-neutral-700 hover:text-brand-600"
          >
            {{ category.name }}
          </NuxtLink>

          <div
            v-if="category.children?.length"
            class="invisible absolute left-1/2 top-full z-50 w-48 -translate-x-1/2 translate-y-1 rounded-lg border border-brand-50 bg-white p-2 opacity-0 shadow-lg transition-all duration-150 group-hover:visible group-hover:opacity-100"
          >
            <NuxtLink
              v-for="child in category.children"
              :key="child.id"
              :to="`/categoria/${child.slug}`"
              class="block rounded-md px-3 py-2 text-neutral-600 normal-case hover:bg-brand-50 hover:text-brand-700"
            >
              {{ child.name }}
            </NuxtLink>
          </div>
        </div>
      </nav>
    </div>

    <!-- Menu mobile -->
    <Transition name="fade">
      <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 md:hidden">
        <div class="absolute inset-0 bg-black/40" @click="mobileMenuOpen = false" />
        <div class="absolute left-0 top-0 h-full w-72 overflow-y-auto bg-white p-6 shadow-xl">
          <div class="mb-6 flex items-center justify-between">
            <span class="font-display text-xl text-brand-700">Radiance</span>
            <button class="text-neutral-500" aria-label="Fechar menu" @click="mobileMenuOpen = false"><X :size="24" /></button>
          </div>

          <form class="mb-6 relative" @submit.prevent="submitSearch(); mobileMenuOpen = false">
            <input
              v-model="searchTerm"
              type="search"
              placeholder="Buscar joias..."
              class="w-full rounded-full border border-brand-100 bg-brand-50/50 py-2 pl-4 pr-10 text-sm focus:border-brand-400 focus:outline-none"
            >
          </form>

          <NuxtLink
            to="/"
            class="mb-4 flex items-center gap-2 py-1 font-medium uppercase tracking-wide text-neutral-800"
            @click="mobileMenuOpen = false"
          >
            <Home :size="18" /> Início
          </NuxtLink>

          <NuxtLink
            v-if="isLoggedIn && user?.role === 'admin'"
            to="/admin"
            class="mb-4 flex items-center gap-2 rounded-lg border border-brand-200 px-3 py-2 text-sm font-medium text-brand-700"
            @click="mobileMenuOpen = false"
          >
            <Settings :size="16" /> Painel Admin
          </NuxtLink>

          <div v-for="category in categories" :key="category.id" class="mb-4">
            <NuxtLink
              :to="`/categoria/${category.slug}`"
              class="block py-1 font-medium uppercase tracking-wide text-neutral-800"
              @click="mobileMenuOpen = false"
            >
              {{ category.name }}
            </NuxtLink>
            <div v-if="category.children?.length" class="ml-3 mt-1 flex flex-col gap-1">
              <NuxtLink
                v-for="child in category.children"
                :key="child.id"
                :to="`/categoria/${child.slug}`"
                class="py-1 text-sm text-neutral-500"
                @click="mobileMenuOpen = false"
              >
                {{ child.name }}
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
