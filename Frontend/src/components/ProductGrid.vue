<script setup>
import ProductCard from './ProductCard.vue'

const products = [
  {
    name: 'Strawberry Energy',
    description: 'Šokolāde ar zemeņu garšu',
    price: 4.99,
    image: '/src/assets/images/product1.png',
    tag: 'JAUNUMS'
  },

  {
    name: 'Banana Energy',
    description: 'Šokolāde ar banānu garšu',
    price: 4.99,
    image: '/src/assets/images/product2.png',
    tag: 'POPULĀRĀKAIS'
  },

  {
    name: 'Mango Energy',
    description: 'Šokolāde ar mango garšu',
    price: 5.49,
    image: '/src/assets/images/product3.png',
    tag: 'LIMITĒTS'
  }
]
</script>

<template>
  <section
    class="products"
    id="produkti"
  >

    <div class="heading">

      <div>
        <span>KOLEKCIJA</span>

        <h2>
          Iegādājies
          <strong>sigma</strong>
          kolekciju
        </h2>
      </div>

      <button class="filter">
        FILTRĒT ☰
      </button>

    </div>

    <div class="grid">

      <ProductCard
        v-for="product in products"
        :key="product.name"
        :name="product.name"
        :description="product.description"
        :price="product.price"
        :image="product.image"
        :tag="product.tag"
      />

    </div>

  </section>
</template>
