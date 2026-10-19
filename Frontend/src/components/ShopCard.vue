<script setup>
import { inject } from 'vue'
import ProductImage from './ProductImage.vue'
import WishButton from './WishButton.vue'
import { money } from '../data/products.js'
defineProps({ product: { type: Object, required: true } })
const openProductDetails = inject('openProductDetails')
defineEmits(['add'])
</script>
<template><article class="shop-card" :class="product.tone"><WishButton :product="product" /><button class="product-detail-trigger shop-card-image-trigger" type="button" :aria-label="`Skatīt ${product.name} detaļas`" @click="openProductDetails?.(product)"><ProductImage :tile="product.tile" :label="product.name" :image-url="product.imageUrl" /></button><div class="shop-card-copy"><span class="product-tag">{{ product.tag }}</span><button class="product-detail-trigger shop-card-title-trigger" type="button" @click="openProductDetails?.(product)"><h3>{{ product.name }}</h3></button><p>{{ product.description }}</p><button class="product-details-link" type="button" @click="openProductDetails?.(product)">Produkta informācija <span aria-hidden="true">↗</span></button><div class="card-bottom"><span>{{ money(product.price) }} <small>/ 100 g</small></span><button :disabled="product.stock <= 0" :aria-label="`Pievienot grozam: ${product.name}`" @click="$emit('add', product)">{{ product.stock > 0 ? 'add +' : 'Izpārdots' }}</button></div></div></article></template>
