<script setup lang="ts">
import { CheckCircle2, X, XCircle } from 'lucide-vue-next'

const { toasts, dismiss } = useToast()
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-2 rounded-lg border p-3 text-sm shadow-lg"
        :class="toast.type === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
      >
        <XCircle v-if="toast.type === 'error'" :size="18" class="mt-0.5 shrink-0" />
        <CheckCircle2 v-else :size="18" class="mt-0.5 shrink-0" />
        <p class="flex-1">{{ toast.message }}</p>
        <button type="button" class="shrink-0 opacity-60 hover:opacity-100" @click="dismiss(toast.id)">
          <X :size="16" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.toast-enter-from, .toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
