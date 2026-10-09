<script setup>
import { ref, watch } from 'vue'
import { register } from '../api/store.js'
import { user } from '../api/state.js'
import ProductImage from './ProductImage.vue'
defineEmits(['login'])
const busy = ref(false)
const name = ref('')
const email = ref('')
const password = ref('')
const confirmation = ref('')
const showPassword = ref(false)
const message = ref('')
const confirmationInput = ref(null)
watch([password, confirmation], () => {
  confirmationInput.value?.setCustomValidity(confirmation.value && password.value !== confirmation.value ? 'Paroles nesakrīt.' : '')
})
async function submit() {
  if (busy.value) return
  busy.value = true; message.value = ''
  try {
    user.value = (await register({ name: name.value.trim(), email: email.value, password: password.value, password_confirmation: confirmation.value })).user
    window.location.hash = '#/veikals'
  } catch (error) { message.value = error.message }
  finally { busy.value = false; password.value = ''; confirmation.value = ''; showPassword.value = false }
}
</script>

<template>
  <main class="login-page registration-page content-width">
    <section class="login-art">
      <span class="eyebrow">join the sweet side</span>
      <h2>Tavs konts.<br />Tava <span>garša.</span></h2>
      <ProductImage :tile="0" label="Zemeņu krēma šokolāde" />
      <p>Saldie mirkļi sākas šeit. ♡</p>
    </section>
    <section class="login-form-panel">
      <a href="#/veikals" class="back-link">← Atpakaļ pie šokolādes</a>
      <span class="newsletter-tag">Your sweet era starts here</span>
      <h1>Reģistrējies <span class="pink-text">♡</span></h1>
      <p>Izveido savu Shoco kontu.</p>
      <form @submit.prevent="submit">
        <label for="register-name">Tavs vārds</label>
        <input id="register-name" v-model="name" type="text" autocomplete="name" placeholder="Kā tevi sauc?" required maxlength="100" pattern=".*\S.*" />
        <label for="register-email">E-pasta adrese</label>
        <input id="register-email" v-model="email" type="email" autocomplete="email" placeholder="tavs@epasts.lv" required maxlength="254" />
        <label for="register-password">Parole</label>
        <div class="password-field">
          <input id="register-password" v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="Vismaz 8 rakstzīmes" required minlength="8" aria-describedby="password-hint" />
          <button type="button" :aria-pressed="showPassword" @click="showPassword = !showPassword">{{ showPassword ? 'Slēpt' : 'Rādīt' }}</button>
        </div>
        <p id="password-hint" class="password-hint">Izvēlies paroli ar vismaz 8 rakstzīmēm.</p>
        <label for="register-confirmation">Atkārto paroli</label>
        <input id="register-confirmation" ref="confirmationInput" v-model="confirmation" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" placeholder="Vēlreiz tava parole" required minlength="8" />
        <button class="button pink-button login-submit" type="submit" :disabled="busy">{{ busy ? 'Veido kontu…' : 'Izveidot kontu ↗' }}</button>
        <p v-if="message" class="form-message" role="status">{{ message }}</p>
      </form>
      <p class="account-switch">Jau ir konts? <button type="button" @click="$emit('login')">Pieslēdzies ↗</button></p>
    </section>
  </main>
</template>

