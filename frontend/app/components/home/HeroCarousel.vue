<script setup lang="ts">
interface Slide {
  image: string
  title: string
  subtitle: string
  cta: string
  to: string
  textSide: 'left' | 'right'
}

const slides: Slide[] = [
  { image: '/images/joia1.webp', title: 'Peças que brilham em você', subtitle: 'Prata 925 com garantia', cta: 'Ver coleção', to: '/categoria/colares', textSide: 'right' },
  { image: '/images/joia2.webp', title: 'Novidades toda semana', subtitle: 'Lançamentos exclusivos', cta: 'Conferir', to: '/busca?q=', textSide: 'left' },
  { image: '/images/joia3.webp', title: 'Frete grátis acima de R$ 219', subtitle: 'Aproveite e monte seu look', cta: 'Comprar agora', to: '/categoria/aneis', textSide: 'right' },
]

const active = ref(0)
let timer: ReturnType<typeof setInterval>

onMounted(() => {
  timer = setInterval(() => {
    active.value = (active.value + 1) % slides.length
  }, 5000)
})

onUnmounted(() => clearInterval(timer))
</script>

<template>
  <div class="relative h-[420px] overflow-hidden sm:h-[520px]">
    <TransitionGroup name="slide">
      <div
        v-for="(slide, index) in slides"
        v-show="index === active"
        :key="slide.title"
        class="absolute inset-0"
      >
        <img :src="slide.image" :alt="slide.title" class="absolute inset-0 h-full w-full object-cover" fetchpriority="high">

        <div
          class="absolute inset-0"
          :class="slide.textSide === 'right'
            ? 'bg-gradient-to-l from-brand-100/95 via-brand-100/60 to-transparent'
            : 'bg-gradient-to-r from-brand-100/95 via-brand-100/60 to-transparent'"
        />

        <div
          class="relative flex h-full items-center px-6 sm:px-16"
          :class="slide.textSide === 'right' ? 'justify-end text-right' : 'justify-start text-left'"
        >
          <div class="max-w-lg">
            <h1 class="text-balance font-display text-3xl text-brand-800 sm:text-5xl">{{ slide.title }}</h1>
            <p class="mt-3 text-neutral-600">{{ slide.subtitle }}</p>
            <NuxtLink
              :to="slide.to"
              class="mt-6 inline-block rounded-full bg-brand-600 px-8 py-3 text-sm font-medium text-white hover:bg-brand-700"
            >
              {{ slide.cta }}
            </NuxtLink>
          </div>
        </div>
      </div>
    </TransitionGroup>

    <div class="absolute bottom-5 left-1/2 flex -translate-x-1/2 gap-2">
      <button
        v-for="(slide, index) in slides"
        :key="slide.title"
        class="h-2 w-2 rounded-full transition-all"
        :class="index === active ? 'w-6 bg-brand-600' : 'bg-white/70'"
        :aria-label="`Slide ${index + 1}`"
        @click="active = index"
      />
    </div>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: opacity 0.6s ease;
}
.slide-enter-from, .slide-leave-to {
  opacity: 0;
}
</style>
