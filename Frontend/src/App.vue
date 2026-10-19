<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue'
import logo from './assets/images/choco.png'
import heroImage from './assets/images/hero-67.jpeg'
import { theme, toggleTheme } from './theme.js'
import { money } from './data/products.js'
import { catalog, catalogLoading, catalogError, loadCatalog, restoreSession, user, signOut } from './api/state.js'
import ProductImage from './components/ProductImage.vue'
import ShopPage from './components/ShopPage.vue'
import AboutPage from './components/AboutPage.vue'
import WishlistPage from './components/WishlistPage.vue'
import WishButton from './components/WishButton.vue'
import { wishlistIds } from './wishlist.js'
import LoginPage from './components/LoginPage.vue'
import RegisterPage from './components/RegisterPage.vue'
import CartDrawer from './components/CartDrawer.vue'
import CheckoutPage from './components/CheckoutPage.vue'
import ProductDetailDialog from './components/ProductDetailDialog.vue'
import AdminPage from './components/AdminPage.vue'
const products = computed(() => catalog.value.slice(0, 4))
const mango = computed(() => catalog.value.find(item => item.tile === 7))
const logoutBusy = ref(false)
const page = ref('home')
const cartDrawer = ref(null)
const productDetailDialog = ref(null)
const loginModal = ref(null)
const cart = ref([])
const announcement = ref('')
let toastTimer
function loadCart() {
  try {
    const saved = JSON.parse(localStorage.getItem('shoco-cart-v1') || '[]')
    if (!Array.isArray(saved)) return []
    return catalog.value.flatMap(product => {
      const quantity = saved.filter(item => item && item.id === product.id && Number.isInteger(item.quantity) && item.quantity > 0).reduce((sum,item) => sum + item.quantity, 0)
      return quantity ? [{ ...product, quantity: Math.min(quantity, 99) }] : []
    })
  } catch { return [] }
}
watch(cart, value => {
  try { localStorage.setItem('shoco-cart-v1', JSON.stringify(value.map(({ id, quantity }) => ({ id, quantity })))) } catch { /* Cart remains available in memory. */ }
}, { deep: true })
const count = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0))
function notify(message) { announcement.value = message; clearTimeout(toastTimer); toastTimer = setTimeout(() => { announcement.value = '' }, 3200) }
function showProductDetails(product) { productDetailDialog.value?.open(product) }
provide('openProductDetails', showProductDetails)
function add(product) {
  const canonical = catalog.value.find(item => item.id === product.id)
  if (!canonical) return
  const existing = cart.value.find(item => item.id === product.id)
  if (!canonical.stock || (existing?.quantity || 0) >= canonical.stock) { notify('Šobrīd nav pieejams lielāks daudzums.'); return }
  if (existing?.quantity >= 99) { notify('Vienam produktam grozā var būt līdz 99 gabaliem.'); return }
  if (existing) existing.quantity++
  else cart.value.push({ ...canonical, quantity: 1 })
  notify(`${canonical.name} pievienots grozam.`)
}
function remove(id) { cart.value = cart.value.filter(item => item.id !== id) }
function setQuantity(id, quantity) {
  if (!Number.isInteger(quantity)) return
  if (quantity <= 0) { remove(id); return }
  const item = cart.value.find(item => item.id === id)
  if (item) item.quantity = Math.min(99, Math.max(1, item.stock), quantity)
}
function goToCheckout() {
  cartDrawer.value?.close()
  window.location.hash = '#/pasutijums'
}
async function syncPage() {
  let hash = window.location.hash
  const legacyLogin = hash === '#/login'
  if (legacyLogin) { history.replaceState(null, '', '#/sakums'); hash = '#/sakums' }
  loginModal.value?.close()
  page.value = ['#/pasutijums', '#/noformesana'].includes(hash) ? 'checkout' : hash === '#/admin' ? 'admin' : hash === '#/velmes' ? 'wishlist' : hash === '#/veikals' ? 'shop' : hash === '#/registracija' ? 'register' : ['#/par-mums', '#par-mums'].includes(hash) ? 'about' : 'home'
  document.documentElement.lang = 'lv'
  document.title = `${page.value === 'checkout' ? 'Noformēt pasūtījumu' : page.value === 'admin' ? 'Atlaižu kodi' : page.value === 'wishlist' ? 'Vēlmju saraksts' : page.value === 'about' ? 'Mūsu stāsts' : page.value === 'shop' ? 'Šokolādes veikals' : page.value === 'register' ? 'Reģistrēties' : 'Šokolāde ar raksturu'} | ShocoStore`
  await nextTick()
  if (legacyLogin) loginModal.value?.open()
  const target = ['#jaunums', '#par-mums', '#produkti'].includes(hash) ? document.getElementById(hash.slice(1)) : null
  if (target) target.scrollIntoView({ behavior: 'smooth' })
  else window.scrollTo({ top: 0 })
}
async function refreshProducts() {
  await loadCatalog()
  if (!catalogError.value) cart.value = loadCart()
}
async function logoutAccount() {
  if (logoutBusy.value) return
  logoutBusy.value = true
  try { await signOut(); notify('Tu esi izrakstījies.') } catch (error) { notify(error.message) }
  finally { logoutBusy.value = false }
}
onMounted(() => { syncPage(); window.addEventListener('hashchange', syncPage); refreshProducts(); restoreSession() })
onBeforeUnmount(() => { clearTimeout(toastTimer); window.removeEventListener('hashchange', syncPage) })
</script>

<template>
  <div class="storefront">
    <header class="site-header content-width">
      <a class="brand" href="#/sakums" aria-label="ShocoStore sākumlapa"><img :src="logo" alt="ShocoStore" /></a>
      <nav aria-label="Galvenā navigācija"><a href="#/veikals" :aria-current="page === 'shop' ? 'page' : undefined">Veikals</a><a href="#jaunums">Jaunumi</a><a href="#/par-mums" :aria-current="page === 'about' ? 'page' : undefined">Stāsts</a><a v-if="user?.isAdmin" href="#/admin" :aria-current="page === 'admin' ? 'page' : undefined">Admin</a></nav>
      <div class="header-actions"><a class="wishlist-link" href="#/velmes" :aria-current="page === 'wishlist' ? 'page' : undefined" :aria-label="`Vēlmju saraksts: ${wishlistIds.length} produkti`" title="Vēlmju saraksts"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z" /></svg><span v-if="wishlistIds.length" class="wishlist-badge">{{ wishlistIds.length }}</span></a><button class="theme-toggle" type="button" :aria-pressed="theme === 'dark'" aria-label="Tumšais režīms" :title="theme === 'dark' ? 'Ieslēgt gaišo režīmu' : 'Ieslēgt tumšo režīmu'" @click="toggleTheme"><svg v-if="theme === 'light'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 13.1A8.5 8.5 0 0 1 10.9 3.5a8.5 8.5 0 1 0 9.6 9.6Z" /></svg><svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" /></svg></button><button v-if="!user" class="login-link" type="button" @click="loginModal.open()">Log in ↗</button><button v-else class="login-link" type="button" :disabled="logoutBusy" :aria-label="`Izrakstīties: ${user.name}`" @click="logoutAccount">Izrakstīties</button><button class="cart-button" @click="cartDrawer.open()">grozs <span>{{ count }}</span></button></div>
    </header>

    <CheckoutPage v-if="page === 'checkout'" :items="cart" @quantity="setQuantity" @remove="remove" @clear="cart = []" @login="loginModal.open()" @ordered="refreshProducts" /><AdminPage v-else-if="page === 'admin' && user?.isAdmin" @products-updated="refreshProducts" /><main v-else-if="page === 'admin'" class="admin-page content-width"><h1>Admin piekļuve nepieciešama</h1><p class="admin-intro">Pieslēdzies ar kontu, kas norādīts servera ADMIN_EMAIL iestatījumā.</p><button v-if="!user" class="button pink-button" @click="loginModal.open()">Pieslēgties</button></main><ShopPage v-else-if="page === 'shop'" @add="add" /><WishlistPage v-else-if="page === 'wishlist'" @add="add" /><AboutPage v-else-if="page === 'about'" /><RegisterPage v-else-if="page === 'register'" @login="loginModal.open()" /><main v-else id="sakums"><p v-if="catalogLoading" class="api-status content-width" role="status">Ielādē produktus…</p><div v-if="catalogError" class="form-message content-width" role="alert">{{ catalogError }} <button class="button outline-button" @click="refreshProducts">Mēģināt vēlreiz</button></div>
      <section class="hero content-width" aria-labelledby="hero-title">
        <div class="hero-copy">
          <span class="eyebrow">♡ Šokolāde ar raksturu</span>
          <h1 id="hero-title">67 choco tikai ar<br /><span class="pink-text">sigma</span> enerģiju.</h1>
          <span class="scribble" aria-hidden="true"></span>
          <p>Mēs ticam, ka šokolāde ir kas vairāk par saldumu. Tā ir tava mazā uzvara, garšīgākā pauze un enerģija lielām lietām.</p>
          <div class="hero-actions"><a class="button pink-button" href="#/veikals">Izvēlies savu garšu ↗</a><a class="button outline-button" href="#/par-mums">Kas ir sigma?</a></div>
          <div class="flavor-note"><span class="color-drops" aria-hidden="true"><i></i><i></i><i></i><i></i></span><span>4 garšas. Tava individualitāte.</span></div>
        </div>
        <div class="hero-art photo-hero"><span class="floating-label mint-label">atklāj savu spēku ✦</span><img class="hero-photo" :src="heroImage" alt="67 šokolāde — ar 6 un 7 spēku" width="1254" height="1254" /><span class="floating-label art-label">100% tavs moments</span></div></section>

      <section id="produkti" class="flavors" aria-labelledby="flavors-title">
        <div class="flavors-inner content-width">
          <div class="section-heading"><h2 id="flavors-title">Izvēlies savu <span>varoni</span></h2><span class="collection-note">četras garšas ↙</span></div>
          <div class="product-grid">
            <article v-for="product in products" :key="product.id" class="flavor-card" :class="product.tone">
              <WishButton :product="product" /><button class="product-detail-trigger home-product-detail-trigger" type="button" :aria-label="`Skatīt ${product.name} detaļas`" @click="showProductDetails(product)"><ProductImage class="home-product-photo" :tile="product.tile" :label="product.name" :image-url="product.imageUrl" /><span class="product-tag">{{ product.tag }}</span><h3>{{ product.name }}</h3><p>{{ product.description }}</p></button>
              <div class="card-bottom"><span>{{ money(product.price) }}</span><button :disabled="product.stock <= 0" :aria-label="`Pievienot grozam: ${product.name}`" @click="add(product)">{{ product.stock > 0 ? 'add +' : 'Izpārdots' }}</button></div>
            </article>
          </div>
        </div>
      </section>

      <section id="jaunums" class="spotlight content-width" aria-labelledby="mango-title">
        <div class="spotlight-copy"><span class="handwritten">✦ mazliet saules, daudz garšas</span><h2 id="mango-title">KŪSTOŠAIS MANGO</h2><p>Saulains mango. Maiga baltā šokolāde. Tavs tropiskais piedzīvojums katrā kumosā.</p><button class="button pink-button" :disabled="!mango || mango.stock <= 0" @click="add(mango)">{{ mango ? `Ķer sauli · ${money(mango.price)} ↗` : 'Drīzumā' }}</button><WishButton v-if="mango" class="spotlight-wish" :product="mango" /><span class="spotlight-note">Vasaras sajūta<br />jebkurā gadalaikā.</span></div>
        <div class="mango-art photo-mango"><span class="new-label">New drop ↗</span><ProductImage :tile="7" label="Kūstošais mango šokolādes iepakojums" /></div></section>

    </main>

    <footer class="site-footer"><div class="content-width footer-inner"><a class="brand footer-brand" href="#/sakums"><img :src="logo" alt="ShocoStore" /></a><p>Šokolāde ar raksturu. © {{ new Date().getFullYear() }} ShocoStore</p><a href="#/veikals">Veikals ↗</a><a href="#/par-mums">Mūsu stāsts ↗</a></div></footer>
    <p class="cart-status" role="status">{{ announcement }}</p>
    <LoginPage ref="loginModal" /><CartDrawer ref="cartDrawer" :items="cart" @quantity="setQuantity" @remove="remove" @clear="cart = []" @checkout="goToCheckout" /><ProductDetailDialog ref="productDetailDialog" />
  </div>
</template>
