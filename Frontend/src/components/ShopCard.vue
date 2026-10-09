<script setup>
import ProductImage from './ProductImage.vue'
import { money } from '../data/products.js'
defineProps({ product: { type: Object, required: true } })
defineEmits(['add'])
</script>
<template><article class="shop-card" :class="product.tone"><ProductImage :tile="product.tile" :label="product.name" /><div class="shop-card-copy"><span class="product-tag">{{ product.tag }}</span><h3>{{ product.name }}</h3><p>{{ product.description }}</p><div class="card-bottom"><span>{{ money(product.price) }} <small>/ 100 g</small></span><button :disabled="product.stock <= 0" :aria-label="`Pievienot grozam: ${product.name}`" @click="$emit('add', product)">{{ product.stock > 0 ? 'add +' : 'Izpārdots' }}</button></div></div></article></template>

