import { ref, watch } from 'vue'

const storageKey = 'shoco-theme'
function initialTheme() {
  try {
    const saved = localStorage.getItem(storageKey)
    if (saved === 'dark' || saved === 'light') return saved
  } catch { /* Use the device preference when storage is unavailable. */ }
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}
export const theme = ref(initialTheme())
watch(theme, value => {
  document.documentElement.dataset.theme = value
  try { localStorage.setItem(storageKey, value) } catch { /* Keep the current session theme. */ }
}, { immediate: true })
export function toggleTheme() { theme.value = theme.value === 'dark' ? 'light' : 'dark' }