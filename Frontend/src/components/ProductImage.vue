<script setup>
import atlas from '../assets/images/chocolate-atlas.png'
defineProps({ tile: { type: Number, default: 0 }, label: String, imageUrl: { type: String, default: '' } })
</script>
<template><div class="product-photo" role="img" :aria-label="label" :style="{ backgroundImage: `url(${imageUrl || atlas})`, backgroundPosition: imageUrl ? 'center' : `${(tile % 3) * 50}% ${Math.floor(tile / 3) * 50}%`, backgroundSize: imageUrl ? 'cover' : '300% 300%' }"></div></template>
