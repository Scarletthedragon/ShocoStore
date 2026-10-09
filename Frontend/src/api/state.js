import { ref } from 'vue'
import { getProducts, getUser, logout } from './store.js'
export const catalog = ref([])
export const catalogLoading = ref(true)
export const catalogError = ref('')
export const user = ref(null)
export async function loadCatalog() {
  catalogLoading.value = true
  catalogError.value = ''
  try { catalog.value = await getProducts() }
  catch (error) { catalogError.value = error.message }
  finally { catalogLoading.value = false }
}
export async function restoreSession() {
  try { user.value = (await getUser()).user }
  catch { user.value = null }
}
export async function signOut() { await logout(); user.value = null }
