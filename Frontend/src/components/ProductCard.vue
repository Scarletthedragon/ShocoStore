<script setup>
defineProps({
  name: String,
  description: String,
  price: Number,
  tone: String,
  tag: String
})
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

      <button>
        +
      </button>
    </div>

  </article>
</template>
