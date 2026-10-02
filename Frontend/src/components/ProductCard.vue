<script setup>
defineProps({
  name: String,
  description: String,
  price: Number,
  image: String,
  tag: String
})
</script>

<template>
  <article class="product-card">

    <div class="product-image">
      <img
        :src="image"
        :alt="name"
      >
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
