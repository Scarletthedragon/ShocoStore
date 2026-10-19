<script setup>
import { computed, ref } from 'vue'
import ProductImage from './ProductImage.vue'
import { money } from '../data/products.js'

const dialog = ref(null)
const product = ref(null)
const nutritionRows = computed(() => {
  const nutrition = product.value?.details?.nutrition
  if (!nutrition) return []

  return [
    { label: 'Enerģētiskā vērtība', value: `${Math.round(nutrition.energyKcal * 4.184)} kJ / ${nutrition.energyKcal} kcal` },
    { label: 'Tauki', value: `${nutrition.fat} g` },
    { label: 'tostarp piesātinātās taukskābes', value: `${nutrition.saturates} g` },
    { label: 'Ogļhidrāti', value: `${nutrition.carbohydrate} g` },
    { label: 'tostarp cukuri', value: `${nutrition.sugars} g` },
    { label: 'Olbaltumvielas', value: `${nutrition.protein} g` },
    { label: 'Sāls', value: `${nutrition.salt} g` },
  ]
})

function open(item) {
  product.value = item
  dialog.value?.showModal()
}

function close() {
  dialog.value?.close()
}

defineExpose({ open, close })
</script>

<template>
  <dialog
    ref="dialog"
    class="product-details-dialog"
    aria-labelledby="product-details-title"
    @click="event => { if (event.target === dialog) close() }"
  >
    <article v-if="product" class="product-details-content">
      <button class="product-details-close" type="button" aria-label="Aizvērt produkta detaļas" @click="close">✕</button>
      <div class="product-details-intro">
        <div class="product-details-image">
          <ProductImage :tile="product.tile" :label="product.name" :image-url="product.imageUrl" />
        </div>
        <div class="product-details-summary">
          <span class="product-tag">{{ product.tag }}</span>
          <h2 id="product-details-title">{{ product.name }}</h2>
          <p>{{ product.description }}</p>
          <strong class="product-details-price">{{ money(product.price) }} <span>/ 100 g</span></strong>
          <dl class="product-details-facts">
            <div><dt>Šokolādes veids</dt><dd>{{ product.category }}</dd></div>
            <div v-if="product.details"><dt>{{ product.details.cocoaLabel }}</dt><dd>{{ product.details.cocoaPercentage }}%</dd></div>
            <div><dt>Iepakojums</dt><dd>100 g</dd></div>
          </dl>
        </div>
      </div>

      <template v-if="product.details">
        <section class="product-details-section">
          <h3>Garša un tekstūra</h3>
          <p>{{ product.details.tastingNotes }}</p>
          <p>{{ product.details.texture }}</p>
          <p><strong>Ieteikums:</strong> {{ product.details.pairing }}</p>
        </section>

        <section class="product-details-section">
          <h3>Sastāvdaļas</h3>
          <p>{{ product.details.ingredients.join(', ') }}</p>
        </section>

        <section class="product-details-section">
          <h3>Alergēni</h3>
          <p>{{ product.details.allergens.length ? product.details.allergens.join(', ') : 'Nav norādīti.' }}</p>
        </section>

        <section class="product-details-section nutrition-section">
          <div class="nutrition-heading"><h3>Uzturvērtība</h3><span>uz 100 g</span></div>
          <dl class="product-nutrition">
            <div v-for="row in nutritionRows" :key="row.label"><dt>{{ row.label }}</dt><dd>{{ row.value }}</dd></div>
          </dl>
        </section>

        <p class="product-details-disclaimer">Uzturvērtības, sastāvdaļas un alergēni ir šīs receptūras koncepta aplēses, nevis pārbaudīts marķējums. Pirms lietošanas pārbaudi informāciju uz faktiskā iepakojuma.</p>
      </template>
      <p v-else class="product-details-disclaimer">Šim produktam detalizēta receptūras informācija vēl nav pievienota.</p>
    </article>
  </dialog>
</template>