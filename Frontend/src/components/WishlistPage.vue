<script setup>
import { catalogLoading, catalogError, loadCatalog } from '../api/state.js'
import { wishlistIds, wishlistProducts } from '../wishlist.js'
import ShopCard from './ShopCard.vue'
defineEmits(['add'])
</script>
<template>
  <main class="wishlist-page shop-page content-width" aria-labelledby="wishlist-title">
    <header class="page-intro"><span class="eyebrow">♡ saglabā mazliet prieka vēlākam</span><h1 id="wishlist-title">Tavs <span class="pink-text">vēlmju saraksts</span></h1><p>Visas tavas mīļākās garšas vienuviet.<br />Noskaties šodien, palutini sevi, kad sirds vēlas.</p></header>
    <p v-if="catalogLoading" class="api-status" role="status">Ielādē tavas iecienītās šokolādes…</p>
    <div v-else-if="catalogError" class="form-message" role="alert">{{ catalogError }} <button class="button outline-button" @click="loadCatalog">Mēģināt vēlreiz</button></div>
    <template v-else>
      <p v-if="wishlistProducts.length" class="result-count" role="status">Saglabātās garšas: {{ wishlistProducts.length }}</p>
      <div v-if="wishlistProducts.length" class="shop-grid"><ShopCard v-for="product in wishlistProducts" :key="product.id" :product="product" @add="$emit('add', $event)" /></div>
      <div v-else class="empty-state wishlist-empty"><span class="empty-cart-icon" aria-hidden="true">♡</span><h2>{{ wishlistIds.length ? 'Šīs garšas šobrīd nav pieejamas.' : 'Te vēl ir vieta tavām mīļākajām garšām.' }}</h2><p>{{ wishlistIds.length ? 'Tavas izvēles ir saglabātas. Tikmēr atklāj citas šokolādes veikalā.' : 'Spied sirsniņu pie šokolādes, lai saglabātu to vēlākam.' }}</p><a class="button pink-button" href="#/veikals">Atklāj garšas ↗</a></div>
      <p v-if="wishlistProducts.length < wishlistIds.length && wishlistProducts.length" class="api-status">Dažas saglabātās šokolādes šobrīd nav atrodamas katalogā.</p>
    </template>
    <p class="wishlist-note">Tavs saraksts saglabājas šajā pārlūkā — arī bez konta.</p>
  </main>
</template>