<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { createAdminCoupon, deleteProduct, getAdminCoupons, getProducts, revokeAdminCoupon, saveProduct, updateProduct, uploadProductImage } from '../api/store.js'
import ProductImage from './ProductImage.vue'
import OrdersPanel from './OrdersPanel.vue'
import { prepareProductImage } from '../api/product-image.js'

const coupons = ref([])
const products = ref([])
const emit = defineEmits(['products-updated'])
const loading = ref(true)
const busy = ref(false)
const error = ref('')
const message = ref('')
const productError = ref('')
const couponForm = ref({ code: '', discountPercent: 15, usageLimit: 1, expiresAt: defaultExpiration(), productIds: [] })
const productForm = ref(blankProduct())
const editingProductId = ref(null)
const selectedImagePreview = ref('')
const imagePreparing = ref(false)
let imageSelection = 0
const activeSection = ref('products')
const editorVisible = ref(false)
const productQuery = ref('')
const filteredProducts = computed(() => products.value.filter(product => product.name.toLocaleLowerCase('lv').includes(productQuery.value.trim().toLocaleLowerCase('lv'))))
const totalStock = computed(() => products.value.reduce((total, product) => total + Number(product.stock), 0))
const activeCouponCount = computed(() => coupons.value.filter(coupon => couponStatus(coupon) === 'Aktīvs').length)

async function focusEditor() {
  editorVisible.value = true
  await nextTick()
  document.getElementById('product-editor')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  document.getElementById('product-name')?.focus({ preventScroll: true })
}

function startNewProduct() {
  cancelProductEdit()
  message.value = ''
  focusEditor()
}

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
  return { name: '', price: 0, stock: 0, description: '', tone: 'berry', tag: '', image: null, currentImageUrl: '', nutritionEnabled: false, nutrition: blankNutrition(), details: blankDetails() }
}

function blankDetails() {
  return { cocoaLabel: 'Kakao saturs', cocoaPercentage: '', ingredients: '', allergens: '', tastingNotes: '', texture: '', pairing: '' }
}

function contentList(value) {
  return value.split(/\r?\n/).map(item => item.trim()).filter(Boolean)
}

const nutritionFields = [
  { key: 'energyKcal', label: 'Enerģētiskā vērtība (kcal)', max: 1000 },
  { key: 'fat', label: 'Tauki (g)', max: 100 },
  { key: 'saturates', label: 'Tostarp piesātinātās taukskābes (g)', max: 100 },
  { key: 'carbohydrate', label: 'Ogļhidrāti (g)', max: 100 },
  { key: 'sugars', label: 'Tostarp cukuri (g)', max: 100 },
  { key: 'protein', label: 'Olbaltumvielas (g)', max: 100 },
  { key: 'salt', label: 'Sāls (g)', max: 100 },
]

function blankNutrition() {
  return { energyKcal: '', fat: '', saturates: '', carbohydrate: '', sugars: '', protein: '', salt: '' }
}

function clearImagePreview() {
  if (selectedImagePreview.value) URL.revokeObjectURL(selectedImagePreview.value)
  selectedImagePreview.value = ''
}

async function selectProductImage(event) {
  const selection = ++imageSelection
  const image = event.target.files?.[0] ?? null
  event.target.value = ''
  clearImagePreview()
  productForm.value.image = null
  productError.value = ''
  if (!image) return
  imagePreparing.value = true
  try {
    const prepared = await prepareProductImage(image)
    if (selection !== imageSelection) return
    productForm.value.image = prepared
    selectedImagePreview.value = URL.createObjectURL(prepared)
  } catch (cause) {
    if (selection === imageSelection) productError.value = cause.message
  } finally {
    if (selection === imageSelection) imagePreparing.value = false
  }
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
  couponForm.value.code = `CHOCO${Array.from(random, byte => byte.toString(16).padStart(2, '0')).join('').toUpperCase()}`
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
  focusEditor()
  imageSelection++; imagePreparing.value = false; productError.value = ''
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
    nutritionEnabled: Boolean(product.nutrition),
    nutrition: { ...blankNutrition(), ...product.nutrition },
    details: {
      ...blankDetails(), ...product.details,
      ingredients: (product.details?.ingredients ?? []).join('\n'),
      allergens: (product.details?.allergens ?? []).join('\n'),
    },
  }
  clearImagePreview()
  message.value = ''
}

function cancelProductEdit() {
  editorVisible.value = false
  imageSelection++; imagePreparing.value = false; productError.value = ''
  clearImagePreview()
  editingProductId.value = null
  productForm.value = blankProduct()
}

function showSavedProduct(saved) {
  const index = products.value.findIndex(product => product.id === saved.id)
  if (index === -1) products.value.push(saved)
  else products.value[index] = saved
  emit('products-updated')
}

async function saveChocolate() {
  if (busy.value || imagePreparing.value) return
  busy.value = true
  error.value = ''; message.value = ''; productError.value = ''
  const image = productForm.value.image
  const payload = {
    name: productForm.value.name.trim(),
    price: Number(productForm.value.price),
    stock: Number(productForm.value.stock),
    description: productForm.value.description,
    tone: productForm.value.tone,
    tag: productForm.value.tag,
    details: {
      cocoaLabel: productForm.value.details.cocoaLabel,
      tastingNotes: productForm.value.details.tastingNotes,
      texture: productForm.value.details.texture,
      pairing: productForm.value.details.pairing,
      cocoaPercentage: productForm.value.details.cocoaPercentage === '' || productForm.value.details.cocoaPercentage == null
        ? null : Number(productForm.value.details.cocoaPercentage),
      ingredients: contentList(productForm.value.details.ingredients),
      allergens: contentList(productForm.value.details.allergens),
    },
    nutrition: productForm.value.nutritionEnabled
      ? Object.fromEntries(nutritionFields.map(({ key }) => [key, Number(productForm.value.nutrition[key])]))
      : null,
  }
  try {
    const saved = editingProductId.value
      ? await updateProduct(editingProductId.value, payload)
      : await saveProduct(payload)
    editingProductId.value = saved.id
    productForm.value.currentImageUrl = saved.imageUrl ?? ''
    showSavedProduct(saved)
    if (image) {
      try {
        const withImage = await uploadProductImage(saved.id, payload, image)
        showSavedProduct(withImage)
      } catch (cause) {
        productError.value = 'Šokolāde ir saglabāta, bet attēlu neizdevās augšupielādēt. ' + cause.message + ' Mēģini saglabāt vēlreiz; jauns ieraksts netiks izveidots.'
        return
      }
    }
    message.value = 'Šokolāde ir saglabāta.'
    cancelProductEdit()
  } catch (cause) {
    productError.value = cause.message
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
    <h1>Veikala pārvaldība <span class="pink-text">✦</span></h1>
    <p class="admin-intro">Pārvaldi šokolādes, produktu informāciju un atlaižu kodus vienuviet.</p>
    <div class="admin-overview">
      <div><span>Šokolādes</span><strong>{{ products.length }}</strong></div>
      <div><span>Vienības noliktavā</span><strong>{{ totalStock }}</strong></div>
      <div><span>Aktīvie atlaižu kodi</span><strong>{{ activeCouponCount }}</strong></div>
    </div>
    <nav class="admin-sections" aria-label="Pārvaldības sadaļas">
      <button type="button" :aria-pressed="activeSection === 'orders'" @click="activeSection = 'orders'">Pasūtījumi</button>
      <button type="button" :aria-pressed="activeSection === 'products'" @click="activeSection = 'products'">Šokolādes <span>{{ products.length }}</span></button>
      <button type="button" :aria-pressed="activeSection === 'coupons'" @click="activeSection = 'coupons'">Atlaižu kodi <span>{{ coupons.length }}</span></button>
    </nav>

    <p v-if="error" class="form-message" role="alert">{{ error }}</p>
    <p v-if="message" class="form-message" role="status">{{ message }}</p>

    <section v-show="activeSection === 'products'" class="admin-coupon-form admin-products-panel" aria-labelledby="products-admin-title">
      <div class="admin-panel-heading"><div><h2 id="products-admin-title">Šokolādes</h2><p class="admin-section-intro">Produkti, cenas un atlikumi.</p></div><button class="button pink-button" type="button" :disabled="busy || imagePreparing" @click="startNewProduct">+ Pievienot šokolādi</button></div>
      <label class="admin-search">Meklēt šokolādi<input v-model="productQuery" type="search" placeholder="Ievadi nosaukumu…" /></label>
      <form v-if="editorVisible" id="product-editor" class="admin-product-form" @submit.prevent="saveChocolate">
        <h3>{{ editingProductId ? 'Atjaunināt šokolādi' : 'Pievienot šokolādi' }}</h3>
        <div class="admin-form-grid">
          <div><label for="product-name">Nosaukums</label><input id="product-name" v-model="productForm.name" required maxlength="100" /></div>
          <div><label for="product-price">Cena (€)</label><input id="product-price" v-model.number="productForm.price" type="number" min="0" max="99999999.99" step="0.01" required /></div>
          <div><label for="product-stock">Atlikums</label><input id="product-stock" v-model.number="productForm.stock" type="number" min="0" step="1" required /></div>
          <div><label for="product-tag">Birka (neobligāti)</label><input id="product-tag" v-model="productForm.tag" maxlength="50" /></div>
          <div class="admin-image-field">
            <span class="admin-image-label">Šokolādes attēls</span>
            <label class="admin-image-picker" for="product-image" :aria-label="`Pievienot vai mainīt attēlu: ${productForm.name || 'šokolāde'}`">
              <ProductImage class="admin-product-preview-image" :tile="tileForTone(productForm.tone)" :label="`Attēla priekšskatījums: ${productForm.name || 'šokolāde'}`" :image-url="selectedImagePreview || productForm.currentImageUrl" />
              <span>{{ selectedImagePreview ? 'Mainīt attēlu' : productForm.currentImageUrl ? 'Nomainīt attēlu' : 'Pievienot attēlu' }}</span>
            </label>
            <input id="product-image" class="admin-image-input" type="file" :disabled="busy || imagePreparing" accept="image/jpeg,image/png,image/webp" @change="selectProductImage" />
            <small>JPG, PNG vai WebP līdz 20 MB. Attēls tiks samazināts pirms augšupielādes.</small>
          </div>
          <div class="admin-product-description"><label for="product-description">Apraksts</label><textarea id="product-description" v-model="productForm.description" maxlength="1000" rows="3"></textarea></div>
        </div>
        <fieldset class="admin-nutrition" :disabled="busy">
          <legend>Produkta saturs</legend>
          <div class="admin-form-grid">
            <div><label for="cocoa-label">Satura nosaukums</label><input id="cocoa-label" v-model="productForm.details.cocoaLabel" maxlength="100" placeholder="Kakao saturs" /></div>
            <div><label for="cocoa-percentage">Kakao saturs (%)</label><input id="cocoa-percentage" v-model.number="productForm.details.cocoaPercentage" type="number" min="0" max="100" step="0.01" /></div>
            <div><label for="product-ingredients">Sastāvdaļas (katru jaunā rindā)</label><textarea id="product-ingredients" v-model="productForm.details.ingredients" rows="5"></textarea></div>
            <div><label for="product-allergens">Alergēni (katru jaunā rindā)</label><textarea id="product-allergens" v-model="productForm.details.allergens" rows="5"></textarea></div>
            <div><label for="product-taste">Garšas apraksts</label><textarea id="product-taste" v-model="productForm.details.tastingNotes" maxlength="1000" rows="3"></textarea></div>
            <div><label for="product-texture">Tekstūra</label><textarea id="product-texture" v-model="productForm.details.texture" maxlength="1000" rows="3"></textarea></div>
            <div><label for="product-pairing">Ar ko sader / pasniegšanas ieteikums</label><textarea id="product-pairing" v-model="productForm.details.pairing" maxlength="1000" rows="3"></textarea></div>
          </div>
        </fieldset>
        <fieldset class="admin-nutrition" :disabled="busy">
          <legend>Uzturvērtība uz 100 g</legend>
          <label class="admin-nutrition-toggle"><input v-model="productForm.nutritionEnabled" type="checkbox" /> Norādīt uzturvērtību</label>
          <div v-if="productForm.nutritionEnabled" class="admin-form-grid">
            <div v-for="field in nutritionFields" :key="field.key">
              <label :for="`nutrition-${field.key}`">{{ field.label }}</label>
              <input :id="`nutrition-${field.key}`" v-model.number="productForm.nutrition[field.key]" type="number" min="0" :max="field.max" step="0.01" required />
            </div>
          </div>
          <p v-if="productForm.nutritionEnabled">Enerģija: {{ Math.round(Number(productForm.nutrition.energyKcal || 0) * 4.184) }} kJ. Izmanto datus no produkta iepakojuma.</p>
        </fieldset>
        <div class="admin-form-actions">
          <button class="button pink-button" type="submit" :disabled="busy || imagePreparing">{{ imagePreparing ? 'Sagatavo attēlu…' : busy ? 'Saglabā…' : editingProductId ? 'Atjaunināt šokolādi' : 'Pievienot šokolādi' }}</button>
          <button class="button outline-button" type="button" :disabled="busy" @click="cancelProductEdit">Atcelt</button>
        </div>
        <p v-if="productError" class="form-message" role="alert">{{ productError }}</p>
      </form>

      <p v-if="loading" role="status">Ielādē šokolādes…</p>
      <p v-else-if="!products.length">Šokolāžu vēl nav.</p>
      <p v-else-if="!filteredProducts.length" class="admin-empty">Nekas netika atrasts. Izmēģini citu nosaukumu.</p>
      <ul v-else class="admin-product-list">
        <li v-for="product in filteredProducts" :key="product.id">
          <ProductImage class="admin-product-thumbnail" :tile="product.tile" :label="product.name" :image-url="product.imageUrl" />
          <div><strong>{{ product.name }}</strong><span>{{ product.price.toFixed(2) }} € · atlikums {{ product.stock }} · {{ product.tag || 'Bez birkas' }}</span></div>
          <div class="admin-product-actions">
            <button class="button outline-button" type="button" :disabled="busy" @click="editProduct(product)">Rediģēt</button>
            <button class="button outline-button" type="button" :disabled="busy" @click="removeChocolate(product)">Dzēst</button>
          </div>
        </li>
      </ul>
    </section>

    <OrdersPanel v-if="activeSection === 'orders'" admin />
    <div v-show="activeSection === 'coupons'" class="admin-coupons-layout">
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
    </div>
  </main>
</template>

<style scoped>
.admin-page{padding-top:36px}
.admin-page>h1{font-size:clamp(30px,4vw,44px);letter-spacing:-1px}
.admin-overview{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:28px 0}
.admin-overview>div{display:grid;gap:12px;padding:22px;border:1px solid var(--theme-border);border-radius:18px;background:var(--surface)}
.admin-overview span{font-size:13px;color:var(--muted)}
.admin-overview strong{font-family:'Outfit',sans-serif;font-size:32px;line-height:1}
.admin-sections{display:flex;gap:8px;border-bottom:1px solid var(--theme-border);padding-bottom:14px;margin-bottom:24px}
.admin-sections button{display:flex;align-items:center;gap:10px;padding:12px 18px;border:1px solid var(--theme-border);border-radius:30px;background:var(--surface);color:var(--page-text);font-weight:600;font-size:14px}
.admin-sections button[aria-pressed=true]{background:var(--brown);color:var(--cream)}
.admin-sections span{font-size:11px;opacity:.75}
.admin-page .admin-coupon-form,.admin-page .admin-coupon-list{margin-top:0;padding:24px;border:1px solid var(--theme-border);border-radius:20px;background:var(--surface);color:var(--page-text)}
.admin-panel-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.admin-panel-heading h2{margin-bottom:6px}
.admin-page .admin-section-intro{color:var(--muted);font-size:13px}
.admin-search{display:grid;gap:8px;margin:12px 0;font-size:13px;font-weight:600;max-width:400px}
.admin-page :is(input:not([type=checkbox]):not([type=file]),select,textarea){width:100%;padding:12px;border:1px solid var(--theme-border);border-radius:10px;background:var(--page-bg);color:var(--page-text);font-size:14px}
.admin-page label{line-height:1.5}
.admin-page .admin-product-form{margin:10px 0 24px;padding:24px;border:1px solid var(--theme-border);border-radius:16px;scroll-margin-top:24px}
.admin-product-form>h3{font-size:24px;margin-bottom:20px}
.admin-page .admin-form-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.admin-page .admin-product-list{margin-top:0}
.admin-page .admin-product-list li{padding:18px 0;border-color:var(--theme-border)}
.admin-page .admin-product-list li>div span{color:var(--muted);line-height:1.6}
.admin-page .admin-product-actions .button{font-size:12px;min-height:40px;padding:9px 13px}
.admin-page .admin-form-actions{margin-top:20px;padding-top:18px;border-top:1px solid var(--theme-border)}
.admin-coupons-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.35fr);gap:24px;align-items:start}
.admin-coupons-layout .admin-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.admin-expiration{grid-column:1/-1}
.admin-coupons-layout .admin-coupon-products{max-height:260px;overflow:auto;background:var(--page-bg);border-color:var(--theme-border)}
.admin-coupons-layout .admin-coupon-products label{flex-basis:100%;padding:4px 0}
.admin-coupons-layout .admin-coupon-products>p{color:var(--muted)}
.admin-empty{padding:24px 0;color:var(--muted)}
.admin-page>.form-message{margin-bottom:20px}
@media(max-width:1000px){.admin-coupons-layout{grid-template-columns:minmax(0,1fr)}}
@media(max-width:650px){.admin-overview{gap:8px}.admin-overview>div{padding:14px}.admin-overview span{font-size:11px}.admin-overview strong{font-size:26px}.admin-page .admin-coupon-form,.admin-page .admin-coupon-list{padding:16px}.admin-page .admin-product-form{padding:14px}.admin-page .admin-form-grid{grid-template-columns:minmax(0,1fr)}.admin-sections button{padding:10px 14px}.admin-panel-heading>.button{width:100%}.admin-search{max-width:none}}
.admin-nutrition{margin:18px 0;padding:18px;border:1px solid var(--theme-border);border-radius:14px;min-width:0}
.admin-nutrition legend{font-weight:700;padding-inline:8px}
.admin-nutrition .admin-nutrition-toggle{display:flex;align-items:center;gap:10px;margin-bottom:16px}
.admin-nutrition .admin-nutrition-toggle input{width:auto;min-height:0}
.admin-nutrition p{font-size:12px;color:var(--muted);margin-top:12px}
</style>
