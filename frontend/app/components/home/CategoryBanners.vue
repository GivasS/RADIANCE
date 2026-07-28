<script setup lang="ts">
const { data: categories } = useCategories()

const gradients = ['from-brand-200 to-brand-50', 'from-accent-200 to-accent-50', 'from-brand-300 to-brand-100']

const featured = computed(() => (categories.value ?? []).slice(0, 3))
</script>

<template>
  <section v-if="featured.length" class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <NuxtLink
        v-for="(category, index) in featured"
        :key="category.id"
        :to="`/categoria/${category.slug}`"
        class="group relative flex h-40 items-end overflow-hidden rounded-2xl bg-gradient-to-br p-5"
        :class="gradients[index % gradients.length]"
      >
        <span class="font-display text-xl text-brand-800 transition-transform group-hover:translate-x-1">
          {{ category.name }}
        </span>
      </NuxtLink>
    </div>
  </section>
</template>
