import { computed, ref, watch } from 'vue'
import { catalog } from './api/state.js'
const storageKey = 'shoco-wishlist-v1'
function restore() {
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey) || '[]')
    return Array.isArray(saved) ? [...new Set(saved.filter(id => (typeof id === 'string' && id.length > 0) || (Number.isInteger(id) && id > 0)))] : []
  } catch { return [] }
}
export const wishlistIds = ref(restore())
export const wishlistProducts = computed(() => catalog.value.filter(product => wishlistIds.value.includes(product.id)))
export function isWished(id) { return wishlistIds.value.includes(id) }
export function toggleWish(id) {
  wishlistIds.value = isWished(id) ? wishlistIds.value.filter(saved => saved !== id) : [...wishlistIds.value, id]
}
watch(wishlistIds, ids => {
  try { localStorage.setItem(storageKey, JSON.stringify(ids)) } catch { /* Favorites remain available for this session. */ }
})