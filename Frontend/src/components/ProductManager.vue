<script setup>
import { onMounted, reactive, ref } from 'vue'
import { getProducts, saveProduct } from '../api/store.js'

const products = ref([])
const loading = ref(true)
const saving = ref(false)
const message = ref('')
const hasError = ref(false)
const form = reactive({ name: '', price: '', stock: 0 })

onMounted(loadProducts)

async function loadProducts() {
  loading.value = true
  hasError.value = false

  try {
    products.value = await getProducts()
  } catch (error) {
    hasError.value = true
    message.value = error.message
  } finally {
    loading.value = false
  }
}

async function addProduct() {
  saving.value = true
  hasError.value = false

  try {
    const product = await saveProduct({
      name: form.name.trim(),
      price: Number(form.price),
      stock: Number(form.stock),
    })
    products.value = [...products.value, product]
    message.value = `Produkts saglabāts: ${product.name}`
    form.name = ''
    form.price = ''
    form.stock = 0
  } catch (error) {
    hasError.value = true
    message.value = error.message
  } finally {
    saving.value = false
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('lv-LV', {
    style: 'currency',
    currency: 'EUR',
  }).format(price)
}
</script>

<template>
  <section id="produktu-parvaldiba" class="product-manager" aria-labelledby="product-manager-title">
    <div class="product-manager__heading">
      <div>
        <p class="product-manager__eyebrow">INVENTĀRS</p>
        <h2 id="product-manager-title">Produkti</h2>
      </div>
      <button class="product-manager__refresh" type="button" :disabled="loading" @click="loadProducts">
        Atjaunot sarakstu
      </button>
    </div>

    <form class="product-manager__form" @submit.prevent="addProduct">
      <label>
        Nosaukums
        <input v-model.trim="form.name" name="name" maxlength="100" required>
      </label>
      <label>
        Cena (€)
        <input v-model.number="form.price" name="price" type="number" min="0" step="0.01" required>
      </label>
      <label>
        Atlikums
        <input v-model.number="form.stock" name="stock" type="number" min="0" step="1" required>
      </label>
      <button class="product-manager__submit" type="submit" :disabled="saving">
        {{ saving ? 'Saglabā...' : 'Pievienot produktu' }}
      </button>
    </form>

    <p v-if="message" class="product-manager__message" :class="{ 'is-error': hasError }" role="status">
      {{ message }}
    </p>

    <p v-if="loading" class="product-manager__empty">Ielādē produktus...</p>
    <p v-else-if="!products.length && !hasError" class="product-manager__empty">Produktu vēl nav.</p>

    <div v-else-if="products.length" class="product-manager__table-wrap">
      <table>
        <thead>
          <tr>
            <th scope="col">Produkts</th>
            <th scope="col">Cena</th>
            <th scope="col">Atlikums</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in products" :key="product.id">
            <td>{{ product.name }}</td>
            <td>{{ formatPrice(product.price) }}</td>
            <td>{{ product.stock }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>