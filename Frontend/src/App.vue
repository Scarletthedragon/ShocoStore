<script setup>
import Navbar from './components/Navbar.vue'
import ProductGrid from './components/ProductGrid.vue'
import ProductManager from './components/ProductManager.vue'
</script>

<template>
  <div class="app">
    <Navbar />

    <main>
      <section class="hero">
        <div class="hero-content">

          <div class="hero-text">
            <p class="eyebrow">
              ENERĢIJA KATRAM MIRKLIM
            </p>

            <h1>
              67 choco tīki ar
              <span>sigma</span> enerģiju!
            </h1>

            <p class="description">
              Atklāj mūsu īpašo šokolādes kolekciju.
              Garšīga enerģija katram tavam mirklim.
            </p>

            <button class="hero-button">
              IEGĀDĀTIES →
            </button>
          </div>

          <div class="hero-image">
            <div class="hero-package" role="img" aria-label="Shoco šokolāde">
              <span>SHOCO</span>
              <strong>67</strong>
              <span>ENERGY CHOCOLATE</span>
            </div>
          </div>

        </div>
      </section>

      <ProductGrid />
      <ProductManager />
    </main>
  </div>
</template>