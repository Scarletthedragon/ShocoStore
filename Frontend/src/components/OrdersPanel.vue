<script setup>
import { computed, onMounted, ref } from 'vue'
import { getShipments, updateShipment } from '../api/store.js'
import { money } from '../data/products.js'
import AddressFields from './AddressFields.vue'

const props = defineProps({ admin: Boolean })
const orders = ref([]), loading = ref(true), busy = ref(false), error = ref(''), message = ref('')
const filter = ref(''), editing = ref(null), address = ref({}), quantities = ref({}), tracking = ref('')
const selectedStatuses = ref({})
const labels = { gaida: 'Gaida apstiprinājumu', apstiprinats: 'Apstiprināts', sagatavo: 'Tiek sagatavots', nosutits: 'Nosūtīts', piegadats: 'Piegādāts', atcelts: 'Atcelts' }
const transitions = { gaida: ['apstiprinats', 'atcelts'], apstiprinats: ['sagatavo', 'atcelts'], sagatavo: ['nosutits', 'atcelts'], nosutits: ['piegadats'], piegadats: [], atcelts: [] }
const visibleOrders = computed(() => orders.value.filter(order => !filter.value || order.status === filter.value))
async function load() {
  loading.value = true; error.value = ''
  try {
    orders.value = await getShipments(props.admin)
    selectedStatuses.value = Object.fromEntries(orders.value.map(order => [order.id, order.status]))
  }
  catch (cause) { error.value = cause.message }
  finally { loading.value = false }
}
function canEdit(order) { return (props.admin ? ['gaida', 'apstiprinats', 'sagatavo'] : ['gaida']).includes(order.status) }
function startEdit(order) {
  editing.value = order.id
  address.value = { country: 'Latvija', ...order.address }
  quantities.value = Object.fromEntries(order.items.map(item => [item.id, item.quantity]))
  tracking.value = order.trackingNumber || ''
}
async function change(order, data) {
  if (busy.value) return
  if (data.status === 'atcelts' && !window.confirm('Vai atcelt visu pasūtījumu? Preces tiks atgrieztas noliktavā.')) return
  busy.value = true; error.value = ''; message.value = ''
  try {
    await updateShipment(order.id, data, props.admin)
    editing.value = null
    await load()
    message.value = 'Pasūtījums atjaunināts.'
  } catch (cause) { error.value = cause.message }
  finally { busy.value = false }
}
function save(order) {
  const data = { address: address.value, items: order.items.map(item => ({ id: Number(item.id), quantity: Number(quantities.value[item.id]) })) }
  if (props.admin) data.trackingNumber = tracking.value || null
  change(order, data)
}
onMounted(load)
</script>

<template>
  <section class="orders-panel">
    <header class="orders-heading"><div><h2>{{ admin ? 'Pasūtījumu pārvaldība' : 'Mani pasūtījumi' }}</h2><p>{{ admin ? 'Apstiprini pasūtījumus un atjaunini sūtījumu statusus.' : 'Seko piegādei. Līdz apstiprināšanai vari mainīt adresi un daudzumus.' }}</p></div><button class="button outline-button" :disabled="busy || loading" @click="load">Atjaunot</button></header>
    <label class="orders-filter">Statuss<select v-model="filter"><option value="">Visi pasūtījumi</option><option v-for="(label, status) in labels" :key="status" :value="status">{{ label }}</option></select></label>
    <p v-if="error" class="form-message" role="alert">{{ error }}</p>
    <p v-if="message" role="status">{{ message }}</p>
    <p v-if="loading" role="status">Ielādē pasūtījumus…</p>
    <p v-else-if="!visibleOrders.length">Nav pasūtījumu ar izvēlēto statusu.</p>
    <article v-for="order in visibleOrders" :key="order.id" class="order-card">
      <header class="orders-heading"><div><strong>Nr. {{ order.id }}</strong><p>{{ new Date(order.createdAt).toLocaleString('lv-LV') }}<template v-if="admin"> · {{ order.customerName }}</template></p></div><span class="order-status">{{ labels[order.status] || order.status }}</span></header>
      <ul><li v-for="item in order.items" :key="item.id"><span>{{ item.name }} × {{ item.quantity }}</span><strong>{{ money(item.total) }}</strong></li></ul>
      <p class="order-total">Kopā: {{ money(order.total) }}</p>
      <p v-if="order.address" class="order-address"><strong>Piegāde:</strong> {{ order.address.recipient }}, {{ order.address.street }}, {{ order.address.city }}, {{ order.address.postalCode }}, {{ order.address.country }} · {{ order.address.phone }}</p>
      <p v-else>Šim agrākajam pasūtījumam piegādes adrese nav norādīta.</p>
      <p v-if="order.trackingNumber">Sūtījuma izsekošanas numurs: <strong>{{ order.trackingNumber }}</strong></p>
      <details v-if="order.history.length" class="order-history"><summary>Statusu vēsture</summary><p v-for="(entry, index) in order.history" :key="index">{{ new Date(entry.at).toLocaleString('lv-LV') }} · {{ labels[entry.status] || entry.status }}{{ entry.action === 'edited' ? ' · Informācija mainīta' : '' }}</p></details>
      <form v-if="editing === order.id" @submit.prevent="save(order)">
        <AddressFields v-model="address" :disabled="busy" :prefix="`order-${order.id}`" />
        <div v-for="item in order.items" :key="item.id" class="order-quantity"><label :for="`quantity-${item.id}`">{{ item.name }} — daudzums</label><input :id="`quantity-${item.id}`" v-model.number="quantities[item.id]" type="number" min="1" max="99" required :disabled="busy" /></div>
        <label v-if="admin">Izsekošanas numurs<input v-model="tracking" maxlength="120" :disabled="busy" /></label>
        <div class="order-actions"><button class="button pink-button" :disabled="busy">Saglabāt izmaiņas</button><button class="button outline-button" type="button" :disabled="busy" @click="editing = null">Atcelt rediģēšanu</button></div>
      </form>
      <div v-else class="order-actions">
        <button v-if="canEdit(order)" class="button outline-button" :disabled="busy" @click="startEdit(order)">Mainīt pasūtījumu</button>
        <form v-if="admin" class="order-status-form" @submit.prevent="change(order, { status: selectedStatuses[order.id] })">
          <label :for="`status-${order.id}`">Pasūtījuma statuss</label>
          <select :id="`status-${order.id}`" v-model="selectedStatuses[order.id]" :disabled="busy || loading">
            <option v-for="(label, status) in labels" :key="status" :value="status" :disabled="status !== order.status && !(transitions[order.status] || []).includes(status)">{{ label }}</option>
          </select>
          <button class="button pink-button" type="submit" :disabled="busy || loading || selectedStatuses[order.id] === order.status">Saglabāt statusu</button>
        </form>
        <button v-else-if="['gaida', 'apstiprinats', 'sagatavo'].includes(order.status)" class="button outline-button" :disabled="busy" @click="change(order, { status: 'atcelts' })">Atcelt pasūtījumu</button>
      </div>
    </article>
  </section>
</template>

<style scoped>
.orders-panel{margin-top:28px;padding:24px;border:1px solid var(--theme-border);border-radius:20px;background:var(--surface);min-width:0}
.orders-heading{display:flex;justify-content:space-between;align-items:start;gap:16px;flex-wrap:wrap}.orders-heading p{font-size:13px;color:var(--muted);margin-top:8px;line-height:1.6}.orders-heading strong{overflow-wrap:anywhere}
.orders-filter{display:grid;gap:8px;margin:20px 0;max-width:320px;font-size:13px}
.order-status-form{display:flex;align-items:center;gap:10px;flex-wrap:wrap;min-width:0}.order-status-form label{font-size:13px}.orders-panel .order-status-form select{width:auto;max-width:100%}
.orders-panel :is(input,select){width:100%;padding:11px;border:1px solid var(--theme-border);border-radius:10px;background:var(--page-bg);color:var(--page-text);font:inherit}
.order-card{border-top:1px solid var(--theme-border);padding:24px 0}.order-card ul{list-style:none;padding:0;margin:18px 0}.order-card li{display:flex;justify-content:space-between;gap:16px;padding:7px 0;font-size:14px}.order-card p{font-size:13px;line-height:1.7}.order-total{font-weight:700;text-align:right}.order-status{padding:6px 12px;background:var(--page-bg);border:1px solid var(--theme-border);border-radius:20px;font-size:12px}.order-address{margin:12px 0;overflow-wrap:anywhere}.order-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.order-history{margin:12px 0;font-size:13px}.order-history summary{cursor:pointer}.order-quantity{display:grid;grid-template-columns:minmax(0,1fr) 90px;gap:16px;align-items:center;margin:12px 0}.orders-panel .form-message{margin:14px 0}
@media(max-width:560px){.orders-panel{padding:16px}.order-actions .button{width:100%}}
</style>
