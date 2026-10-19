<script setup>
import { ref } from 'vue'
import ProductImage from './ProductImage.vue'
import { money } from '../data/products.js'

defineProps({ items: { type: Array, required: true } })
const emit = defineEmits(['quantity', 'remove', 'clear', 'checkout'])
const dialog = ref(null)

function open() {
  dialog.value.showModal()
}

function close() {
  dialog.value.close()
}

function goToCheckout() {
  close()
  emit('checkout')
}

defineExpose({ open, close })
</script>

<template>
  <dialog ref="dialog" class="cart-dialog" aria-labelledby="cart-title" @click="event => { if (event.target === dialog) close() }">
    <section class="cart-panel">
      <div class="cart-heading">
        <div><span class="eyebrow">tavi saldie atradumi</span><h2 id="cart-title">Tavs grozs <span class="pink-text">♡</span></h2></div>
        <button aria-label="Aizvērt grozu" @click="close">✕</button>
      </div>
      <div v-if="!items.length" class="empty-state">
        <span class="empty-cart-icon" aria-hidden="true">♡</span><h3>Te vēl ir vieta priekam.</h3>
        <p>Pievieno šokolādi un atrodi savu favorītu.</p><a class="button pink-button" href="#/veikals" @click="close">Uz veikalu ↗</a>
      </div>
      <template v-else>
        <ul class="cart-items">
          <li v-for="item in items" :key="item.id">
            <ProductImage :tile="item.tile" :label="item.name" :image-url="item.imageUrl" />
            <div class="cart-item-copy">
              <strong>{{ item.name }}</strong><p>100 g · {{ money(item.price) }}</p>
              <div class="quantity-control">
                <button :aria-label="`Samazināt ${item.name} daudzumu`" @click="emit('quantity', item.id, item.quantity - 1)">−</button>
                <span :aria-label="`${item.quantity} gabali`">{{ item.quantity }}</span>
                <button :disabled="item.quantity >= Math.min(99, item.stock)" :aria-label="`Palielināt ${item.name} daudzumu`" @click="emit('quantity', item.id, item.quantity + 1)">+</button>
              </div>
            </div>
            <div class="cart-item-end"><strong>{{ money(item.price * item.quantity) }}</strong><button :aria-label="`Izņemt ${item.name}`" @click="emit('remove', item.id)">Izņemt</button></div>
          </li>
        </ul>
        <div class="cart-summary"><p class="cart-total"><strong>Kopā</strong><strong>{{ money(items.reduce((sum, item) => sum + item.price * item.quantity, 0)) }}</strong></p></div>
        <button class="button pink-button continue-button" @click="goToCheckout">Noformēt pasūtījumu ↗</button>
        <button class="clear-cart" @click="emit('clear')">Iztukšot grozu</button>
      </template>
    </section>
  </dialog>
</template>
