<script setup>
import { ref } from 'vue'
import { login } from '../api/store.js'
import { user } from '../api/state.js'
const dialog = ref(null)
const busy = ref(false)
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const message = ref('')
function open() { message.value = ''; dialog.value.showModal() }
function close() { dialog.value.close() }
function reset() { password.value = ''; showPassword.value = false }
async function submit() {
  if (busy.value) return
  busy.value = true; message.value = ''
  try { user.value = (await login({ email: email.value, password: password.value })).user; close() }
  catch (error) { message.value = error.message }
  finally { busy.value = false; reset() }
}
defineExpose({ open, close })
</script>

<template>
  <dialog ref="dialog" class="login-dialog" aria-labelledby="login-title" @close="reset" @click="event => { if (event.target === dialog) close() }">
    <section class="login-form-panel login-modal-content">
      <button class="login-close" type="button" aria-label="Aizvērt pieteikšanos" @click="close">✕</button>
      <span class="newsletter-tag">Sweet to see you</span>
      <h1 id="login-title">Čau, atkal! <span class="pink-text">♡</span></h1>
      <p>Piesakies savā Shoco kontā.</p>
      <form @submit.prevent="submit">
        <label for="login-email">E-pasta adrese</label>
        <input id="login-email" v-model="email" type="email" autocomplete="username" placeholder="tavs@epasts.lv" required maxlength="254" autofocus />
        <label for="login-password">Parole</label>
        <div class="password-field">
          <input id="login-password" v-model="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" placeholder="Tava parole" required />
          <button type="button" :aria-pressed="showPassword" @click="showPassword = !showPassword">{{ showPassword ? 'Slēpt' : 'Rādīt' }}</button>
        </div>
        <button class="button pink-button login-submit" type="submit" :disabled="busy">{{ busy ? 'Pieslēdzas…' : 'Pieslēgties ↗' }}</button>
        <p v-if="message" class="form-message" role="status">{{ message }}</p>
      </form>
      <p class="account-switch">Vēl nav konta? <a href="#/registracija" @click="close">Reģistrējies ↗</a></p>
    </section>
  </dialog>
</template>

