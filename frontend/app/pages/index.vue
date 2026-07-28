<script setup lang="ts">
const { data: featuredProducts } = useFeaturedProducts()
const { data: latestProducts } = useLatestProducts()

useHead({ title: 'Radiance — Joias que contam a sua história' })
</script>

<template>
  <div>
    <HeroCarousel />
    <BenefitsBar />
    <CategoryBanners />

    <ProductShowcase title="Mais Vendidos" :products="featuredProducts ?? []" />
    <ProductShowcase title="Você Pode Gostar" :products="latestProducts ?? []" />

    <NewsletterSignup />
    <CookieConsent />
  </div>
</template>
