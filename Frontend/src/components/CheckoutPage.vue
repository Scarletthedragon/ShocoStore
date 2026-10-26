<script setup>
import { computed, ref } from 'vue'
import ProductImage from './ProductImage.vue'
import { checkout, validateCoupon } from '../api/store.js'
import { user } from '../api/state.js'
import EmailVerificationNotice from './EmailVerificationNotice.vue'
import { money } from '../data/products.js'

const props = defineProps({ items: { type: Array, required: true } })
const emit = defineEmits(['quantity', 'remove', 'clear', 'login', 'ordered'])
const couponCode = ref('')
const coupon = ref(null)
const couponError = ref('')
const orderError = ref('')
const orderMessage = ref('')
const applying = ref(false)
const busy = ref(false)
const termsAccepted = ref(false)
const termsError = ref('')
const termsDialog = ref(null)

const subtotal = computed(() => props.items.reduce((sum, item) => sum + item.price * item.quantity, 0))
const discount = computed(() => {
  if (!coupon.value) return 0
  return props.items.reduce((sum, item) => {
    if (coupon.value.productIds.length && !coupon.value.productIds.includes(String(item.id))) return sum
    const lineCents = Math.round(item.price * 100) * item.quantity
    return sum + lineCents - Math.round(lineCents * (100 - coupon.value.discountPercent) / 100)
  }, 0) / 100
})

async function applyCoupon() {
  if (applying.value || busy.value) return
  applying.value = true
  couponError.value = ''
  coupon.value = null
  try {
    coupon.value = await validateCoupon(couponCode.value.trim().toUpperCase(), props.items.map(item => item.id))
  } catch (error) {
    couponError.value = error.message
  } finally {
    applying.value = false
  }
}

async function placeOrder() {
  if (busy.value || !props.items.length) return
  if (!termsAccepted.value) {
    termsError.value = 'Lai turpinātu, izlasi un apstiprini pasūtījuma noteikumus.'
    return
  }
  termsError.value = ''
  if (!user.value) {
    emit('login')
    return
  }
  if (!user.value.emailVerified) {
    orderError.value = 'Pirms pasūtījuma veikšanas apstiprini savu e-pasta adresi.'
    return
  }

  busy.value = true
  orderError.value = ''
  orderMessage.value = ''
  try {
    const result = await checkout({
      items: props.items.map(item => ({ productId: item.id, quantity: item.quantity })),
      coupon: coupon.value?.code || null,
    })
    orderMessage.value = `Pasūtījums saglabāts! Nr. ${result.orderIds.join(', ')} · ${money(result.total)}. Statuss: gaida. Apmaksa nav veikta.`
    emit('clear')
    emit('ordered')
    coupon.value = null
    couponCode.value = ''
  } catch (error) {
    orderError.value = error.message
    if (error.status === 422 && coupon.value && /atlaižu kods/i.test(error.message)) {
      couponError.value = 'Atlaižu kods vairs nav derīgs. Pārbaudi to un mēģini vēlreiz.'
      coupon.value = null
    }
    if (error.status === 401) {
      user.value = null
      emit('login')
    }
  } finally {
    busy.value = false
  }
}

function openTerms() {
  termsDialog.value?.showModal()
}

function closeTerms() {
  termsDialog.value?.close()
}
</script>

<template>
  <main class="checkout-page content-width">
    <a class="checkout-back" href="#/veikals">← Turpināt iepirkšanos</a>
    <span v-if="!orderMessage" class="eyebrow">pēdējais solis līdz šokolādei</span>
    <h1>{{ orderMessage ? 'Paldies par pasūtījumu!' : 'Noformēt pasūtījumu' }} <span class="pink-text">♡</span></h1>
    <p v-if="!orderMessage" class="checkout-intro">Pārbaudi grozu un apstiprini pasūtījumu. Tiešsaistes apmaksa un piegāde vēl nav pieejama.</p>

    <p v-if="orderError" class="form-message" role="alert">{{ orderError }}</p>

    <div v-if="!items.length" class="checkout-empty">
      <h2 v-if="!orderMessage">Grozs ir tukšs</h2>
      <p v-if="orderMessage" class="checkout-confirmation" role="status">{{ orderMessage }}</p>
      <p v-else>Pievieno grozam šokolādi, lai varētu noformēt pasūtījumu.</p>
      <a class="button pink-button" href="#/veikals">Uz veikalu ↗</a>
    </div>

    <div v-else class="checkout-layout">
      <section class="checkout-order" aria-labelledby="checkout-items-title">
        <h2 id="checkout-items-title">Tavs pasūtījums</h2>
        <ul class="checkout-items">
          <li v-for="item in items" :key="item.id">
            <ProductImage :tile="item.tile" :label="item.name" :image-url="item.imageUrl" />
            <div class="checkout-item-copy">
              <strong>{{ item.name }}</strong>
              <span>{{ money(item.price) }} · 100 g</span>
              <div class="quantity-control">
                <button :disabled="busy" :aria-label="`Samazināt ${item.name} daudzumu`" @click="emit('quantity', item.id, item.quantity - 1)">−</button>
                <span>{{ item.quantity }}</span>
                <button :disabled="busy || item.quantity >= Math.min(99, item.stock)" :aria-label="`Palielināt ${item.name} daudzumu`" @click="emit('quantity', item.id, item.quantity + 1)">+</button>
              </div>
            </div>
            <div class="checkout-item-total">
              <strong>{{ money(item.price * item.quantity) }}</strong>
              <button :disabled="busy" :aria-label="`Izņemt ${item.name}`" @click="emit('remove', item.id)">Izņemt</button>
            </div>
          </li>
        </ul>
        <button class="clear-cart" :disabled="busy" @click="emit('clear')">Iztukšot grozu</button>
      </section>

      <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
        <h2 id="checkout-summary-title">Kopsavilkums</h2>
        <form class="promo-form" @submit.prevent="applyCoupon">
          <label for="checkout-coupon">Atlaižu kods</label>
          <div>
            <input id="checkout-coupon" v-model="couponCode" :disabled="busy || applying" placeholder="Ievadi kodu" autocomplete="off" />
            <button class="button brown-button" :disabled="busy || applying">{{ applying ? 'Pārbauda…' : 'Pielietot' }}</button>
          </div>
          <p v-if="couponError" role="alert">{{ couponError }}</p>
          <p v-if="coupon" role="status">{{ coupon.code }} — {{ coupon.discountPercent }}% atlaide piemērota{{ coupon.productIds.length ? ' izvēlētajām šokolādēm' : '' }}.</p>
        </form>

        <div class="cart-summary">
          <p><span>Preču summa</span><span>{{ money(subtotal) }}</span></p>
          <p v-if="coupon"><span>Atlaide ({{ coupon.discountPercent }}%)</span><span>−{{ money(discount) }}</span></p>
          <p class="cart-total"><strong>Kopā</strong><strong>{{ money(subtotal - discount) }}</strong></p>
        </div>
        <EmailVerificationNotice v-if="user && !user.emailVerified" />
        <p class="checkout-account" v-if="user">Pasūtījums tiks piesaistīts kontam {{ user.email }}.</p>
        <p class="checkout-account" v-else>Lai saglabātu pasūtījumu, vispirms pieslēdzies savam kontam.</p>
        <div class="checkout-terms">
          <input id="checkout-terms" v-model="termsAccepted" type="checkbox" :disabled="busy" :aria-describedby="termsError ? 'checkout-terms-error' : undefined" @change="termsError = ''" />
          <div>
            <label for="checkout-terms">Esmu izlasījis un iepazinies ar&nbsp;</label>
            <button class="checkout-terms-link" type="button" @click="openTerms">pasūtījuma noteikumiem</button>.
          </div>
        </div>
        <p v-if="termsError" id="checkout-terms-error" class="checkout-terms-error" role="alert">{{ termsError }}</p>
        <button class="button pink-button checkout-submit" :disabled="busy || (user && !user.emailVerified)" @click="placeOrder">
          {{ busy ? 'Saglabā pasūtījumu…' : user ? 'Apstiprināt pasūtījumu ↗' : 'Pieslēgties un pasūtīt ↗' }}
        </button>
        <p class="checkout-payment-note">Pasūtījums tiks saglabāts veikalā. Tiešsaistes apmaksa netiek veikta.</p>
      </aside>
    </div>

    <dialog ref="termsDialog" class="checkout-terms-dialog" aria-labelledby="checkout-terms-title" @click="event => { if (event.target === termsDialog) closeTerms() }">
      <button class="checkout-terms-close" type="button" aria-label="Aizvērt noteikumus" @click="closeTerms">×</button>
      <header class="checkout-terms-header">
        <p class="checkout-terms-eyebrow">Informācija pirms pasūtījuma</p>
        <h2 id="checkout-terms-title">Pasūtījuma noteikumi</h2>
        <p class="checkout-terms-intro">Šeit apkopota informācija par pasūtījuma iesniegšanu un pašreiz pieejamo pakalpojumu apjomu.</p>
      </header>
      <div class="checkout-terms-body" tabindex="0" aria-label="Pasūtījuma noteikumi">
        <section>
          <h3>1. Vispārīgie noteikumi</h3>
          <p>Šie noteikumi apraksta pasūtījuma noformēšanu ShocoStore vietnē. Pasūtījumu var iesniegt pēc pieslēgšanās lietotāja kontam.</p>
        </section>
        <section>
          <h3>2. Pasūtījuma iesniegšana</h3>
          <p>Pirms apstiprināšanas pārbaudi izvēlētās preces, to daudzumu un kopsavilkumā norādīto summu. Pasūtījums tiek saglabāts un piesaistīts kontam pēc veiksmīgas apstrādes. Saglabātajam pasūtījumam tiek piešķirts statuss “gaida”.</p>
        </section>
        <section>
          <h3>3. Preču pieejamība</h3>
          <p>Apstiprinot pasūtījumu, sistēma pārbauda preču atlikumu. Ja prece vajadzīgajā daudzumā vairs nav pieejama, pasūtījumu nevarēs apstiprināt. Pēc veiksmīgas pasūtījuma reģistrēšanas pasūtītais daudzums tiek atskaitīts no preču atlikuma.</p>
        </section>
        <section>
          <h3>4. Cenas un atlaižu kodi</h3>
          <p>Preču cenas un pasūtījuma kopsumma tiek parādīta pirms apstiprināšanas. Atlaižu kods tiek piemērots, ja sistēma to atzīst par derīgu un atlaide ir redzama kopsavilkumā. Atsevišķi kodi var attiekties tikai uz noteiktām precēm.</p>
        </section>
        <section>
          <h3>5. Apmaksa un piegāde</h3>
          <p>Šobrīd vietnē nav pieejama tiešsaistes apmaksa vai piegādes noformēšana. Pasūtījuma saglabāšana nav uzskatāma par apmaksu un neapstiprina preču piegādi.</p>
        </section>
        <section>
          <h3>6. Lietotāja konts</h3>
          <p>Pasūtījums tiek piesaistīts kontam, kurā esi pieslēdzies. Pirms apstiprināšanas pārliecinies, ka izmanto pareizo kontu.</p>
        </section>
      </div>
    </dialog>
  </main>
</template>
