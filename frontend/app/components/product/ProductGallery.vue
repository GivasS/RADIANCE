<script setup lang="ts">
import { Gem } from 'lucide-vue-next'

const props = defineProps<{ images: { id: number, path: string, alt_text: string | null }[] }>()

const { imageUrl } = useImageUrl()
const active = ref(0)

const activeImage = computed(() => imageUrl(props.images[active.value]?.path))
</script>

<template>
  <div>
    <div class="group relative aspect-square overflow-hidden rounded-2xl bg-brand-50/50">
      <img
        v-if="activeImage"
        :src="activeImage"
        :alt="images[active]?.alt_text ?? ''"
        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
      >
      <div v-else class="flex h-full w-full items-center justify-center text-brand-200"><Gem :size="56" /></div>
    </div>

    <div v-if="images.length > 1" class="mt-3 flex gap-2 overflow-x-auto">
      <button
        v-for="(image, index) in images"
        :key="image.id"
        class="h-16 w-16 flex-none overflow-hidden rounded-lg border-2 transition-colors"
        :class="index === active ? 'border-brand-500' : 'border-transparent'"
        @click="active = index"
      >
        <img :src="imageUrl(image.path)" :alt="image.alt_text ?? ''" class="h-full w-full object-cover">
      </button>
    </div>
  </div>
</template>
