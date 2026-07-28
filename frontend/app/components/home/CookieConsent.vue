<script setup lang="ts">
const accepted = ref(true)

onMounted(() => {
  accepted.value = localStorage.getItem('radiance:cookies-accepted') === '1'
})

function accept() {
  localStorage.setItem('radiance:cookies-accepted', '1')
  accepted.value = true
}
</script>

<template>
  <Transition name="fade">
    <div
      v-if="!accepted"
      class="fixed inset-x-0 bottom-0 z-50 border-t border-brand-100 bg-white/95 px-4 py-4 shadow-[0_-4px_12px_rgba(0,0,0,0.06)] backdrop-blur"
    >
      <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-center text-sm text-neutral-600 sm:text-left">
          Usamos cookies pra melhorar sua experiência. Ao continuar navegando, você concorda com nossa
          <NuxtLink to="/politica-de-privacidade" class="text-brand-600 underline">Política de Privacidade</NuxtLink>.
        </p>
        <button
          class="shrink-0 rounded-full bg-brand-600 px-6 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="accept"
        >
          Entendi
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
