<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { createAdminCoupon, deleteProduct, getAdminCoupons, getProducts, revokeAdminCoupon, saveProduct, updateProduct } from '../api/store.js'
import ProductImage from './ProductImage.vue'

const coupons = ref([])
const products = ref([])
const emit = defineEmits(['products-updated'])
const loading = ref(true)
const busy = ref(false)
const error = ref('')
const message = ref('')
const couponForm = ref({ code: '', discountPercent: 15, usageLimit: 1, expiresAt: defaultExpiration(), productIds: [] })
const productForm = ref(blankProduct())
const editingProductId = ref(null)
const selectedImagePreview = ref('')

const sortedCoupons = computed(() => [...coupons.value].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)))

function defaultExpiration() {
  const date = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

function minimumExpiration() {
  const date = new Date()
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

function blankProduct() {
  return { name: '', price: 0, stock: 0, description: '', tone: 'berry', tag: '', image: null, currentImageUrl: '' }
}

function clearImagePreview() {
  if (selectedImagePreview.value) URL.revokeObjectURL(selectedImagePreview.value)
  selectedImagePreview.value = ''
}

function selectProductImage(event) {
  clearImagePreview()
  const image = event.target.files?.[0] ?? null
  event.target.value = ''
  if (image && image.size > 5 * 1024 * 1024) {
    productForm.value.image = null
    error.value = 'Attēla izmērs nedrīkst pārsniegt 5 MB.'
    return
  }
  productForm.value.image = image
  if (image) selectedImagePreview.value = URL.createObjectURL(image)
  error.value = ''
}

function tileForTone(tone) {
  const index = visualOptions.findIndex(([id]) => id === tone)
  return index === -1 ? 8 : index
}

const visualOptions = [
  ['berry', 'Ogu rozā'],
  ['salt', 'Tumši violeta'],
  ['matcha', 'Matcha zaļa'],
  ['caramel', 'Karamele'],
  ['mint', 'Piparmētru zaļa'],
  ['raspberry', 'Avenes'],
  ['hazelnut', 'Lazdu rieksti'],
  ['mango', 'Mango'],
  ['original', 'Klasiskā'],
]

function generateCode() {
  const random = crypto.getRandomValues(new Uint8Array(4))
  form.value.code = `CHOCO${Array.from(random, byte => byte.toString(16).padStart(2, '0')).join('').toUpperCase()}`
}

async function loadCoupons() {
  loading.value = true
  error.value = ''
  try {
    const [couponList, productList] = await Promise.all([getAdminCoupons(), getProducts()])
    coupons.value = couponList
    products.value = productList
  } catch (cause) {
    error.value = cause.message
  } finally {
    loading.value = false
  }
}

async function createCoupon() {
  if (busy.value) return
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    const coupon = await createAdminCoupon({
      code: couponForm.value.code.trim().toUpperCase(),
      discountPercent: Number(couponForm.value.discountPercent),
      usageLimit: Number(couponForm.value.usageLimit),
      expiresAt: new Date(couponForm.value.expiresAt).toISOString(),
      productIds: couponForm.value.productIds,
    })
    coupons.value.unshift(coupon)
    message.value = `Atlaižu kods ${coupon.code} izveidots.`
    couponForm.value = { code: '', discountPercent: 15, usageLimit: 1, expiresAt: defaultExpiration(), productIds: [] }
  } catch (cause) {
    error.value = cause.message
  } finally {
    busy.value = false
  }
}

function editProduct(product) {
  editingProductId.value = product.id
  productForm.value = {
    name: product.name,
    price: product.price,
    stock: product.stock,
    description: product.description,
    tone: product.visualId,
    tag: product.tag,
    image: null,
    currentImageUrl: product.imageUrl ?? '',
    tile: product.tile,
  }
  clearImagePreview()
  message.value = ''
}

function cancelProductEdit() {
  clearImagePreview()
  editingProductId.value = null
  productForm.value = blankProduct()
}

async function saveChocolate() {
  if (busy.value) return
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    const payload = { ...productForm.value }
    delete payload.currentImageUrl
    payload.price = Number(payload.price)
    payload.stock = Number(payload.stock)
    const saved = editingProductId.value
      ? await updateProduct(editingProductId.value, payload)
      : await saveProduct(payload)
    const index = products.value.findIndex(product => product.id === saved.id)
    if (index === -1) products.value.push(saved)
    else products.value[index] = saved
    message.value = editingProductId.value ? `Prece “${saved.name}” atjaunināta.` : `Prece “${saved.name}” pievienota.`
    emit('products-updated')
    cancelProductEdit()
  } catch (cause) {
    error.value = cause.message
  } finally {
    busy.value = false
  }
}

async function removeChocolate(product) {
  if (busy.value || !window.confirm(`Vai tiešām dzēst šokolādi “${product.name}”?`)) return
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    await deleteProduct(product.id)
    products.value = products.value.filter(item => item.id !== product.id)
    if (editingProductId.value === product.id) cancelProductEdit()
    message.value = `Šokolāde “${product.name}” dzēsta.`
    emit('products-updated')
  } catch (cause) {
    error.value = cause.message
  } finally {
    busy.value = false
  }
}

function couponProductNames(coupon) {
  const names = coupon.products?.map(product => product.Nosaukums) ?? []
  return names.length ? names.join(', ') : 'Visas šokolādes'
}

async function revokeCoupon(coupon) {
  if (busy.value || !coupon.active) return
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    const revoked = await revokeAdminCoupon(coupon.id)
    const index = coupons.value.findIndex(item => item.id === revoked.id)
    if (index !== -1) coupons.value[index] = revoked
  } catch (cause) {
    error.value = cause.message
  } finally {
    busy.value = false
  }
}

function couponStatus(coupon) {
  if (!coupon.active) return 'Atsaukts'
  if (new Date(coupon.expires_at) <= new Date()) return 'Beidzies'
  if (coupon.usage_limit !== null && coupon.usage_count >= coupon.usage_limit) return 'Izmantots'
  return 'Aktīvs'
}

onMounted(loadCoupons)
onBeforeUnmount(clearImagePreview)
</script>

<template>
  <main class="admin-page content-width">
    <span class="eyebrow">ShocoStore · pārvaldība</span>
    <h1>Atlaižu kodi <span class="pink-text">✦</span></h1>
    <p class="admin-intro">Izveido kodu ar procentu atlaidi, lietojumu skaitu un derīguma termiņu.</p>

    <p v-if="error" class="form-message" role="alert">{{ error }}</p>
    <p v-if="message" class="form-message" role="status">{{ message }}</p>

    <section class="admin-coupon-form" aria-labelledby="products-admin-title">
      <h2 id="products-admin-title">Šokolādes</h2>
      <p class="admin-section-intro">Pievieno jaunu šokolādi vai atjaunini tās informāciju un attēlu.</p>
      <form class="admin-product-form" @submit.prevent="saveChocolate">
        <h3>{{ editingProductId ? 'Atjaunināt šokolādi' : 'Pievienot šokolādi' }}</h3>
        <div class="admin-form-grid">
          <div><label for="product-name">Nosaukums</label><input id="product-name" v-model="productForm.name" required maxlength="100" /></div>
          <div><label for="product-price">Cena (€)</label><input id="product-price" v-model.number="productForm.price" type="number" min="0" max="99999999.99" step="0.01" required /></div>
          <div><label for="product-stock">Atlikums</label><input id="product-stock" v-model.number="productForm.stock" type="number" min="0" step="1" required /></div>
          <div><label for="product-visual">Iepakojuma stils</label><select id="product-visual" v-model="productForm.tone"><option v-for="[id, label] in visualOptions" :key="id" :value="id">{{ label }}</option></select></div>
          <div><label for="product-tag">Birka (neobligāti)</label><input id="product-tag" v-model="productForm.tag" maxlength="50" /></div>
          <div class="admin-image-field">
            <span class="admin-image-label">Šokolādes attēls</span>
            <label class="admin-image-picker" for="product-image" :aria-label="`Pievienot vai mainīt attēlu: ${productForm.name || 'šokolāde'}`">
              <ProductImage class="admin-product-preview-image" :tile="tileForTone(productForm.tone)" :label="`Attēla priekšskatījums: ${productForm.name || 'šokolāde'}`" :image-url="selectedImagePreview || productForm.currentImageUrl" />
              <span>{{ selectedImagePreview ? 'Mainīt attēlu' : productForm.currentImageUrl ? 'Nomainīt attēlu' : 'Pievienot attēlu' }}</span>
            </label>
            <input id="product-image" class="admin-image-input" type="file" accept="image/jpeg,image/png,image/webp" @change="selectProductImage" />
            <small>Uzklikšķini uz attēla, lai pievienotu vai nomainītu (JPG, PNG vai WebP; līdz 5 MB).</small>
          </div>
          <div class="admin-product-description"><label for="product-description">Apraksts</label><textarea id="product-description" v-model="productForm.description" maxlength="1000" rows="3"></textarea></div>
        </div>
        <div class="admin-form-actions">
          <button class="button pink-button" type="submit" :disabled="busy">{{ busy ? 'Saglabā…' : editingProductId ? 'Atjaunināt šokolādi' : 'Pievienot šokolādi' }}</button>
          <button v-if="editingProductId" class="button outline-button" type="button" :disabled="busy" @click="cancelProductEdit">Atcelt</button>
        </div>
      </form>

      <p v-if="loading" role="status">Ielādē šokolādes…</p>
      <p v-else-if="!products.length">Šokolāžu vēl nav.</p>
      <ul v-else class="admin-product-list">
        <li v-for="product in products" :key="product.id">
          <ProductImage class="admin-product-thumbnail" :tile="product.tile" :label="product.name" :image-url="product.imageUrl" />
          <div><strong>{{ product.name }}</strong><span>{{ product.price.toFixed(2) }} € · atlikums {{ product.stock }} · {{ product.tag || 'Bez birkas' }}</span></div>
          <div class="admin-product-actions">
            <button class="button outline-button" type="button" :disabled="busy" @click="editProduct(product)">Rediģēt</button>
            <button class="button outline-button" type="button" :disabled="busy" @click="removeChocolate(product)">Dzēst</button>
          </div>
        </li>
      </ul>
    </section>

    <form class="admin-coupon-form" @submit.prevent="createCoupon">
      <h2>Jauns atlaižu kods</h2>
      <label for="coupon-code">Kods</label>
      <div class="admin-code-field">
        <input id="coupon-code" v-model="couponForm.code" required minlength="3" maxlength="64" pattern="[A-Za-z0-9_-]+" autocomplete="off" placeholder="Piemēram, VASARA20" />
        <button class="button outline-button" type="button" @click="generateCode">Ģenerēt</button>
      </div>

      <div class="admin-form-grid">
        <div>
          <label for="coupon-percent">Atlaide (%)</label>
          <input id="coupon-percent" v-model.number="couponForm.discountPercent" type="number" min="0.01" max="100" step="0.01" required />
        </div>
        <div>
          <label for="coupon-limit">Lietojumu skaits</label>
          <select id="coupon-limit" v-model.number="couponForm.usageLimit">
            <option :value="1">Vienu reizi</option>
            <option :value="0">Neierobežoti</option>
          </select>
        </div>
        <div class="admin-expiration">
          <label for="coupon-expires">Derīgs līdz</label>
          <input id="coupon-expires" v-model="couponForm.expiresAt" type="datetime-local" :min="minimumExpiration()" required />
        </div>
      </div>
      <fieldset class="admin-coupon-products">
        <legend>Kurām šokolādēm piemērot?</legend>
        <p>Neizvēloties nevienu, atlaide attiecas uz visām šokolādēm.</p>
        <label v-for="product in products" :key="product.id">
          <input v-model="couponForm.productIds" type="checkbox" :value="Number(product.id)" />
          {{ product.name }}
        </label>
      </fieldset>
      <button class="button pink-button" type="submit" :disabled="busy">{{ busy ? 'Saglabā…' : 'Izveidot kodu ↗' }}</button>
    </form>

    <section class="admin-coupon-list" aria-labelledby="coupon-list-title">
      <h2 id="coupon-list-title">Izveidotie kodi</h2>
      <p v-if="loading" role="status">Ielādē kodus…</p>
      <p v-else-if="!coupons.length">Atlaižu kodi vēl nav izveidoti.</p>
      <div v-else class="admin-coupon-table-wrap">
        <table class="admin-coupon-table">
          <thead><tr><th>Kods</th><th>Atlaide</th><th>Lietojumi</th><th>Derīguma termiņš</th><th>Statuss</th><th></th></tr></thead>
          <tbody>
            <tr v-for="coupon in sortedCoupons" :key="coupon.id">
              <td><strong>{{ coupon.code }}</strong><small class="admin-coupon-products-summary">{{ couponProductNames(coupon) }}</small></td>
              <td>{{ Number(coupon.discount_percent) }}%</td>
              <td>{{ coupon.usage_count }} / {{ coupon.usage_limit === null ? '∞' : coupon.usage_limit }}</td>
              <td>{{ new Date(coupon.expires_at).toLocaleString() }}</td>
              <td>{{ couponStatus(coupon) }}</td>
              <td><button v-if="coupon.active" class="button outline-button" type="button" :disabled="busy" @click="revokeCoupon(coupon)">Atsaukt</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</template>
