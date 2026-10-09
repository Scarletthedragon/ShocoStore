<script setup>
import { computed, ref } from 'vue'
import { catalog as products, catalogLoading, catalogError, loadCatalog } from '../api/state.js'
import ShopCard from './ShopCard.vue'
defineEmits(['add'])
const query = ref('')
const category = ref('Visas')
const sort = ref('featured')
const filtered = computed(() => {
  const result = products.value.filter(p => (category.value === 'Visas' || p.category === category.value) && `${p.name} ${p.description}`.toLocaleLowerCase('lv').includes(query.value.trim().toLocaleLowerCase('lv')))
  if (sort.value === 'low') result.sort((a,b) => a.price - b.price)
  if (sort.value === 'high') result.sort((a,b) => b.price - a.price)
  return result
})
</script>
<template><main class="shop-page content-width"><header class="page-intro"><span class="eyebrow">✦ izvēlies savu garšīgo personību</span><h1>Iegādājies <span class="pink-text">sigma</span> kolekciju</h1><p>No zemeņu sapņiem līdz matcha mirkļiem.<br />Atrodi garšu, kas ir tikpat īpaša kā tu.</p></header><p v-if="catalogLoading" class="api-status" role="status">Ielādē šokolādi…</p><div v-if="catalogError" class="form-message" role="alert">{{ catalogError }} <button class="button outline-button" @click="loadCatalog">Mēģināt vēlreiz</button></div><div class="shop-toolbar"><div class="category-tabs" aria-label="Šokolādes veids"><button v-for="type in ['Visas', 'Piena', 'Tumšā', 'Baltā']" :key="type" :aria-pressed="category === type" :class="{ active: category === type }" @click="category = type">{{ type }}</button></div><label class="search-box"><span class="sr-only">Meklēt šokolādi</span><input v-model="query" type="search" placeholder="Meklē savu favorītu…" /></label><label class="sort-box"><span class="sr-only">Kārtot produktus</span><select v-model="sort"><option value="featured">Mūsu izlase</option><option value="low">Cena: augoši</option><option value="high">Cena: dilstoši</option></select></label></div><p class="result-count" role="status">{{ filtered.length }} garšīgas izvēles · 100 g iepakojumi</p><div class="shop-grid"><ShopCard v-for="product in filtered" :key="product.id" :product="product" @add="$emit('add', $event)" /></div><div v-if="!catalogLoading && !catalogError && !filtered.length" class="empty-state"><h2>Šī garša vēl nav atrasta.</h2><p>Pamēģini citu nosaukumu vai šokolādes veidu.</p><button class="button pink-button" @click="query = ''; category = 'Visas'">Rādīt visu kolekciju</button></div><aside class="club-banner"><span class="eyebrow">for sweet people only</span><h2>Saldākā slepenā formula?<br />Labs noskaņojums + šokolāde.</h2><p>Atklāj visas deviņas garšas un atrodi savu ikdienas favorītu.</p><span aria-hidden="true">✧</span></aside></main></template>

