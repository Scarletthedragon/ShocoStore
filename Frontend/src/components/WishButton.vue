<script setup>
import { isWished, toggleWish } from '../wishlist.js'
defineProps({ product: { type: Object, required: true } })
</script>
<template>
  <button class="wish-button" type="button" :class="{ 'is-wished': isWished(product.id) }" :aria-pressed="isWished(product.id)" :aria-label="`Vēlmju saraksts: ${product.name}`" :title="isWished(product.id) ? 'Izņemt no vēlmju saraksta' : 'Pievienot vēlmju sarakstam'" @click="toggleWish(product.id)">
    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" :fill="isWished(product.id) ? 'currentColor' : 'none'" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z" /></svg>
  </button>
</template>