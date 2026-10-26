<script setup>
import { ref } from 'vue'
import { getUser, resendVerification } from '../api/store.js'
import { user } from '../api/state.js'

const busy = ref(false)
const message = ref('')
const error = ref('')
async function checkVerification() {
  if (busy.value) return
  busy.value = true; message.value = ''; error.value = ''
  try {
    const result = await getUser()
    user.value.emailVerified = result.user.emailVerified
    user.value.isAdmin = result.user.isAdmin
    if (!result.user.emailVerified) message.value = 'E-pasta adrese vēl nav apstiprināta. Atver saiti saņemtajā e-pastā.'
  } catch (cause) { error.value = cause.message }
  finally { busy.value = false }
}
async function resend() {
  if (busy.value) return
  busy.value = true; message.value = ''; error.value = ''
  try {
    const result = await resendVerification()
    user.value.emailVerified = result.user.emailVerified
    user.value.isAdmin = result.user.isAdmin
    user.value.verificationDeliveryFailed = false
    message.value = 'Apstiprinājuma e-pasts nosūtīts. Pārbaudi arī mēstuļu mapi. Jaunu saiti vari pieprasīt pēc minūtes.'
  } catch (cause) {
    error.value = cause.status === 429 ? 'Lūdzu, uzgaidi minūti pirms nākamā pieprasījuma.' : cause.message
  } finally { busy.value = false }
}
</script>

<template>
  <section v-if="user" class="email-verification" aria-labelledby="email-verification-title">
    <template v-if="user.emailVerified">
      <h2 id="email-verification-title">E-pasts apstiprināts ✓</h2>
      <p>{{ user.email }}</p>
    </template>
    <template v-else>
      <h2 id="email-verification-title">Apstiprini savu e-pastu</h2>
      <p>Atver apstiprinājuma saiti, kas nosūtīta uz <strong>{{ user.email }}</strong>. Saite ir derīga 60 minūtes. Pirms pasūtījuma veikšanas apstiprini savu adresi.</p>
      <p v-if="user.verificationDeliveryFailed" role="alert">E-pastu neizdevās nosūtīt. Pieprasi jaunu saiti.</p>
      <div class="profile-form-actions">
        <button class="button outline-button" type="button" :disabled="busy" @click="resend">Nosūtīt saiti vēlreiz</button>
        <button class="button outline-button" type="button" :disabled="busy" @click="checkVerification">Pārbaudīt statusu</button>
      </div>
      <p v-if="message" role="status">{{ message }}</p>
      <p v-if="error" class="form-message" role="alert">{{ error }}</p>
    </template>
  </section>
</template>
