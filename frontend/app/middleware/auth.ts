export default defineNuxtRouteMiddleware(async (to) => {
  const { isLoggedIn, status, refreshUser } = useAuth()

  // Sempre espera a busca terminar antes de checar - com status "pending"
  // (useAsyncData ja disparou sozinho) nao da pra confiar so no "idle".
  if (status.value !== 'success' && status.value !== 'error') {
    await refreshUser()
  }

  if (!isLoggedIn.value) {
    return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
  }
})
