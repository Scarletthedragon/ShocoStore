<script setup>
import { computed, ref } from 'vue'
import ProductImage from './ProductImage.vue'
import { checkout } from '../api/store.js'
import { user } from '../api/state.js'
import { money } from '../data/products.js'
const props = defineProps({ items: Array })
const emit = defineEmits(['quantity', 'remove', 'clear', 'login', 'ordered'])
const dialog = ref(null)
const busy = ref(false)
const orderMessage = ref('')
const orderError = ref('')
const code = ref('')
const applied = ref(false)
const error = ref('')
const subtotal = computed(() => props.items.reduce((sum,item) => sum + item.price * item.quantity, 0))
const discount = computed(() => applied.value ? props.items.reduce((sum,item) => { const cents = Math.round(item.price * 100) * item.quantity; return sum + cents - Math.round(cents * .85) }, 0) / 100 : 0)
function apply() { applied.value = code.value.trim().toUpperCase() === 'SIGMA15'; error.value = applied.value ? '' : 'Šis kods nav derīgs. Izmēģini SIGMA15.' }
function open() { orderMessage.value = ''; orderError.value = ''; dialog.value.showModal() }
function close() { if (!busy.value) dialog.value.close() }
async function placeOrder() {
  if (busy.value) return
  if (!user.value) { close(); emit('login'); return }
  busy.value = true; orderError.value = ''; orderMessage.value = ''
  try {
    const result = await checkout({ items: props.items.map(item => ({ productId: item.id, quantity: item.quantity })), coupon: applied.value ? 'SIGMA15' : null })
    orderMessage.value = `Pasūtījums saglabāts! Nr. ${result.orderIds.join(', ')} · ${money(result.total)}. Statuss: gaida. Apmaksa nav veikta.`
    emit('clear'); emit('ordered'); applied.value = false; code.value = ''
  } catch (error) {
    orderError.value = error.message
    if (error.status === 401) { user.value = null; busy.value = false; close(); emit('login') }
  } finally { busy.value = false }
}
defineExpose({ open })
</script>
<template><dialog ref="dialog" class="cart-dialog" aria-labelledby="cart-title" @cancel="event => { if (busy) event.preventDefault() }" @click="event => { if (event.target === dialog) close() }"><section class="cart-panel"><div class="cart-heading"><div><span class="eyebrow">tavi saldie atradumi</span><h2 id="cart-title">Tavs grozs <span class="pink-text">♡</span></h2></div><button aria-label="Aizvērt grozu" @click="close">✕</button></div><p v-if="orderMessage" class="form-message" role="status">{{ orderMessage }}</p><p v-if="orderError" class="form-message" role="alert">{{ orderError }}</p><div v-if="!items.length" class="empty-state"><span class="empty-cart-icon" aria-hidden="true">♡</span><h3>Te vēl ir vieta priekam.</h3><p>Pievieno šokolādi un atrodi savu favorītu.</p><a class="button pink-button" href="#/veikals" @click="close">Uz veikalu ↗</a></div><template v-else><ul class="cart-items"><li v-for="item in items" :key="item.id"><ProductImage :tile="item.tile" :label="item.name" /><div class="cart-item-copy"><strong>{{ item.name }}</strong><p>100 g · {{ money(item.price) }}</p><div class="quantity-control"><button :disabled="busy" :aria-label="`Samazināt ${item.name} daudzumu`" @click="emit('quantity', item.id, item.quantity - 1)">−</button><span :aria-label="`${item.quantity} gabali`">{{ item.quantity }}</span><button :disabled="busy || item.quantity >= Math.min(99, item.stock)" :aria-label="`Palielināt ${item.name} daudzumu`" @click="emit('quantity', item.id, item.quantity + 1)">+</button></div></div><div class="cart-item-end"><strong>{{ money(item.price * item.quantity) }}</strong><button :disabled="busy" :aria-label="`Izņemt ${item.name}`" @click="emit('remove', item.id)">Izņemt</button></div></li></ul><form class="promo-form" @submit.prevent="apply"><label for="promo-code">Ir atlaižu kods?</label><div><input id="promo-code" v-model="code" :disabled="busy" placeholder="Piemēram, SIGMA15" /><button :disabled="busy" class="button brown-button">Pielietot</button></div><p v-if="error" role="status">{{ error }}</p><p v-if="applied" role="status">SIGMA15 — 15% atlaide piemērota!</p></form><div class="cart-summary"><p><span>Preču summa</span><span>{{ money(subtotal) }}</span></p><p v-if="applied"><span>Atlaide (15%)</span><span>−{{ money(discount) }}</span></p><p class="cart-total"><strong>Kopā</strong><strong>{{ money(subtotal - discount) }}</strong></p></div><p class="cart-info">Pasūtījums tiks saglabāts veikalā. Tiešsaistes apmaksa vēl nav pieejama.</p><button class="button pink-button continue-button" :disabled="busy" @click="placeOrder">{{ busy ? 'Saglabā pasūtījumu…' : user ? 'Noformēt pasūtījumu ↗' : 'Pieslēgties un pasūtīt ↗' }}</button><button :disabled="busy" class="clear-cart" @click="emit('clear')">Iztukšot grozu</button></template></section></dialog></template>

