<script setup>
import logo from '../assets/images/choco.png'
</script>
<template>
  <header class="navbar">
    <a class="brand" href="#" aria-label="ShocoStore home">
      <img :src="logo" alt="" />
      <span>ShocoStore</span>
    </a>

    <div class="navbar-center">
      <nav>
        <a href="#">Sākumlapa</a>
        <a href="#produkti">Produkti</a>
        <a href="#">Par mums</a>
        <a href="#produktu-parvaldiba">Pārvaldīt</a>
      </nav>

      <div class="actions">
        <button>⌕</button>
        <button>🛒</button>
      </div>
    </div>

    <div class="account-actions">
      <a class="login-button" href="#login">Log in</a>
      <a class="signup-button" href="#signup">Sign up</a>
    </div>

  </header>
</template>