<script setup>
import { onMounted, ref } from 'vue'
import ProductCard from './ProductCard.vue'
import { getProducts, saveOrder } from '../api/store.js'

const products = ref([])
const message = ref('')

onMounted(async () => {
  try {
    products.value = await getProducts()
  } catch (error) {
    message.value = error.message
  }
})

async function placeOrder(productId) {
  try {
    const order = await saveOrder({ productId, quantity: 1, customerName: 'Guest' })
    const product = products.value.find((item) => item.id === productId)
    if (product) product.stock -= 1
    message.value = `Pasūtījums saglabāts: €${order.total.toFixed(2)}`
  } catch (error) {
    message.value = error.message
  }
}
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
        :key="product.id"
        :id="product.id"
        :name="product.name"
        :description="product.description"
        :price="product.price"
        :tone="product.tone"
        :tag="product.tag"
        @add-to-cart="placeOrder"
      />

    </div>
    <p v-if="message" role="status">{{ message }}</p>

  </section>
</template>
