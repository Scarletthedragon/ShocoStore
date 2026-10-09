<script setup>
import atlas from '../assets/images/chocolate-atlas.png'
defineProps({ tile: { type: Number, default: 0 }, label: String })
</script>
<template><div class="product-photo" role="img" :aria-label="label" :style="{ backgroundImage: `url(${atlas})`, backgroundPosition: `${(tile % 3) * 50}% ${Math.floor(tile / 3) * 50}%` }"></div></template>
