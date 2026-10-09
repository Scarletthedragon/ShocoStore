<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import logo from './assets/images/choco.png'
import { money } from './data/products.js'
import { catalog, catalogLoading, catalogError, loadCatalog, restoreSession, user, signOut } from './api/state.js'
import ProductImage from './components/ProductImage.vue'
import ShopPage from './components/ShopPage.vue'
import LoginPage from './components/LoginPage.vue'
import RegisterPage from './components/RegisterPage.vue'
import CartDrawer from './components/CartDrawer.vue'
const products = computed(() => catalog.value.slice(0, 4))
const mango = computed(() => catalog.value.find(item => item.tile === 7))
const logoutBusy = ref(false)
const page = ref('home')
const cartDrawer = ref(null)
const loginModal = ref(null)
const cart = ref([])
const announcement = ref('')
const email = ref('')
const discount = ref(false)
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
function claimDiscount() { discount.value = true }
async function syncPage() {
  let hash = window.location.hash
  const legacyLogin = hash === '#/login'
  if (legacyLogin) { history.replaceState(null, '', '#/sakums'); hash = '#/sakums' }
  loginModal.value?.close()
  page.value = hash === '#/veikals' ? 'shop' : hash === '#/registracija' ? 'register' : 'home'
  document.documentElement.lang = 'lv'
  document.title = `${page.value === 'shop' ? 'Šokolādes veikals' : page.value === 'register' ? 'Reģistrēties' : 'Šokolāde ar raksturu'} | ShocoStore`
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
      <nav aria-label="Galvenā navigācija"><a href="#/veikals" :aria-current="page === 'shop' ? 'page' : undefined">Veikals</a><a href="#jaunums">Jaunumi</a><a href="#par-mums">Stāsts</a></nav>
      <div class="header-actions"><button v-if="!user" class="login-link" type="button" @click="loginModal.open()">Log in ↗</button><button v-else class="login-link" type="button" :disabled="logoutBusy" :aria-label="`Izrakstīties: ${user.name}`" @click="logoutAccount">Izrakstīties</button><button class="cart-button" @click="cartDrawer.open()">grozs <span>{{ count }}</span></button></div>
    </header>

    <ShopPage v-if="page === 'shop'" @add="add" /><RegisterPage v-else-if="page === 'register'" @login="loginModal.open()" /><main v-else id="sakums"><p v-if="catalogLoading" class="api-status content-width" role="status">Ielādē produktus…</p><div v-if="catalogError" class="form-message content-width" role="alert">{{ catalogError }} <button class="button outline-button" @click="refreshProducts">Mēģināt vēlreiz</button></div>
      <section class="hero content-width" aria-labelledby="hero-title">
        <div class="hero-copy">
          <span class="eyebrow">♡ Šokolāde ar raksturu</span>
          <h1 id="hero-title">67 choco tikai ar<br /><span class="pink-text">sigma</span> enerģiju.</h1>
          <span class="scribble" aria-hidden="true"></span>
          <p>Mēs ticam, ka šokolāde ir kas vairāk par saldumu. Tā ir tava mazā uzvara, garšīgākā pauze un enerģija lielām lietām.</p>
          <div class="hero-actions"><a class="button pink-button" href="#/veikals">Izvēlies savu garšu ↗</a><a class="button outline-button" href="#par-mums">Kas ir sigma?</a></div>
          <div class="flavor-note"><span class="color-drops" aria-hidden="true"><i></i><i></i><i></i><i></i></span><span>4 garšas. Tava individualitāte.</span></div>
        </div>
        <div class="hero-art photo-hero"><span class="floating-label mint-label">atklāj savu spēku ✦</span><ProductImage :tile="8" label="67 Original šokolādes iepakojums" /><span class="floating-label art-label">100% tavs moments</span></div></section>

      <section id="produkti" class="flavors" aria-labelledby="flavors-title">
        <div class="flavors-inner content-width">
          <div class="section-heading"><h2 id="flavors-title">Izvēlies savu <span>varoni</span></h2><span class="collection-note">četras garšas ↙</span></div>
          <div class="product-grid">
            <article v-for="product in products" :key="product.id" class="flavor-card" :class="product.tone">
              <ProductImage class="home-product-photo" :tile="product.tile" :label="product.name" />
              <span class="product-tag">{{ product.tag }}</span><h3>{{ product.name }}</h3><p>{{ product.description }}</p>
              <div class="card-bottom"><span>{{ money(product.price) }}</span><button :disabled="product.stock <= 0" :aria-label="`Pievienot grozam: ${product.name}`" @click="add(product)">{{ product.stock > 0 ? 'add +' : 'Izpārdots' }}</button></div>
            </article>
          </div>
        </div>
      </section>

      <section id="jaunums" class="spotlight content-width" aria-labelledby="mango-title">
        <div class="spotlight-copy"><span class="handwritten">✦ mazliet saules, daudz garšas</span><h2 id="mango-title">KŪSTOŠAIS MANGO</h2><p>Saulains mango. Maiga baltā šokolāde. Tavs tropiskais piedzīvojums katrā kumosā.</p><button class="button pink-button" :disabled="!mango || mango.stock <= 0" @click="add(mango)">{{ mango ? `Ķer sauli · ${money(mango.price)} ↗` : 'Drīzumā' }}</button><span class="spotlight-note">Vasaras sajūta<br />jebkurā gadalaikā.</span></div>
        <div class="mango-art photo-mango"><span class="new-label">New drop ↗</span><ProductImage :tile="7" label="Kūstošais mango šokolādes iepakojums" /></div></section>

      <section class="newsletter content-width" aria-labelledby="newsletter-title">
        <span class="newsletter-tag">Join the club</span><span class="sparkle" aria-hidden="true">✧</span><h2 id="newsletter-title">15% no tava pirmā pirkuma.<br />Bez meliem.</h2><p>Pievienojies mūsu saldajam klubam un saņem atlaižu kodu savam pirmajam pirkumam.</p>
        <form @submit.prevent="claimDiscount"><label class="sr-only" for="email">Tava e-pasta adrese</label><input id="email" v-model="email" type="email" placeholder="Tavs e-pasts" autocomplete="email" required /><button class="button brown-button" type="submit">Saņemt kodu ↗</button></form>
        <p v-if="discount" class="discount-message" role="status">Tavs demonstrācijas kods: SIGMA15. E-pasts netiek nosūtīts.</p>
      </section>
      <section id="par-mums" class="brand-story content-width"><span>MAZS GABALIŅŠ. LIELA ENERĢIJA.</span><p>Shoco ir par garšām ar raksturu un prieku būt pašam. Atrodi savu favorītu un padari ikdienu mazliet saldāku.</p></section>
    </main>

    <footer class="site-footer"><div class="content-width footer-inner"><a class="brand footer-brand" href="#/sakums"><img :src="logo" alt="ShocoStore" /></a><p>Šokolāde ar raksturu. © {{ new Date().getFullYear() }} ShocoStore</p><a href="#/veikals">Veikals ↗</a><a href="#par-mums">Mūsu stāsts ↗</a></div></footer>
    <p class="cart-status" role="status">{{ announcement }}</p>
    <LoginPage ref="loginModal" /><CartDrawer ref="cartDrawer" :items="cart" @quantity="setQuantity" @remove="remove" @clear="cart = []" @login="loginModal.open()" @ordered="refreshProducts" />
  </div>
</template>




