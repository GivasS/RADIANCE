export default defineNuxtRouteMiddleware(async (to) => {
  const { user, isLoggedIn, status, refreshUser } = useAuth()

  if (status.value !== 'success' && status.value !== 'error') {
    await refreshUser()
  }

  if (!isLoggedIn.value) {
    return navigateTo({ path: '/admin/login', query: { redirect: to.fullPath } })
  }

  if (user.value?.role !== 'admin') {
    return navigateTo('/admin/login')
  }
})
