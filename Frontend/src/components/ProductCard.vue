<script setup>
defineProps({
  id: String,
  name: String,
  description: String,
  price: Number,
  tone: String,
  tag: String
})

defineEmits(['add-to-cart'])
</script>

<template>
  <article class="product-card">

    <div class="product-image">
      <div class="product-pack" :class="`product-pack--${tone}`" role="img" :aria-label="name">
        <span class="product-pack__brand">SHOCO</span>
        <strong>67</strong>
        <span class="product-pack__name">{{ name }}</span>
      </div>
    </div>

    <span class="tag">
      {{ tag }}
    </span>

    <h3>
      {{ name }}
    </h3>

    <p>
      {{ description }}
    </p>

    <div class="bottom">
      <strong>
        €{{ price.toFixed(2) }}
      </strong>

      <button type="button" :aria-label="`Pasūtīt ${name}`" @click="$emit('add-to-cart', id)">
        +
      </button>
    </div>

  </article>
</template>
