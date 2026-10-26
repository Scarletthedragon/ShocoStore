<script setup>
import { computed, ref, watch } from 'vue'
import { user } from '../api/state.js'
import { deleteAccount, updateProfile } from '../api/store.js'
import EmailVerificationNotice from './EmailVerificationNotice.vue'

defineEmits(['login'])
const name = ref('')
const email = ref('')
const busy = ref(false)
const error = ref('')
const success = ref('')
const showDeleteForm = ref(false)
const deletePassword = ref('')
const deleteConfirmed = ref(false)
const deleteError = ref('')
const deleting = ref(false)
const accountDeleted = ref(false)
function cancelDeletion() {
  showDeleteForm.value = false
  deletePassword.value = ''
  deleteConfirmed.value = false
  deleteError.value = ''
}
async function removeAccount() {
  if (deleting.value || busy.value || !deleteConfirmed.value) return
  deleting.value = true; deleteError.value = ''
  try {
    await deleteAccount({ password: deletePassword.value, confirmation: deleteConfirmed.value })
    user.value = null
    accountDeleted.value = true
    cancelDeletion()
  } catch (cause) {
    deleteError.value = cause.status === 429 ? 'Pārāk daudz mēģinājumu. Uzgaidi minūti un mēģini vēlreiz.' : cause.message
  } finally { deleting.value = false; deletePassword.value = '' }
}
const initials = computed(() => user.value?.name.trim().split(/\s+/).slice(0, 2).map(part => part[0]).join('').toLocaleUpperCase('lv') || '♡')
const changed = computed(() => name.value.trim() !== user.value?.name || email.value.trim().toLowerCase() !== user.value?.email)
function reset() {
  name.value = user.value?.name || ''
  email.value = user.value?.email || ''
  error.value = ''
  success.value = ''
}
watch(user, reset, { immediate: true, flush: 'sync' })
watch([name, email], () => { success.value = ''; error.value = '' }, { flush: 'sync' })
async function save() {
  if (busy.value || deleting.value || !changed.value) return
  busy.value = true
  error.value = ''; success.value = ''
  try {
    const result = await updateProfile({ name: name.value.trim(), email: email.value.trim() })
    user.value = { ...result.user, verificationDeliveryFailed: result.verificationSent === false }
    success.value = 'Tavs profils ir atjaunināts.'
  } catch (cause) { error.value = cause.message }
  finally { busy.value = false }
}
</script>

<template>
  <main class="profile-page content-width">
    <a href="#/veikals" class="back-link">← Atpakaļ pie šokolādes</a>
    <span class="eyebrow">Tava saldā pasaule</span>
    <h1>Mans profils <span class="pink-text">♡</span></h1>
    <template v-if="user">
      <EmailVerificationNotice />
      <p class="profile-intro">Tava informācija vienuviet. Atjaunini to, kad nepieciešams.</p>
      <div class="profile-layout">
        <aside class="profile-summary" aria-label="Konta informācija">
          <span class="profile-avatar" aria-hidden="true">{{ initials }}</span>
          <h2>{{ user.name }}</h2>
          <p>{{ user.email }}</p>
          <span class="profile-account-label">ShocoStore konts</span>
        </aside>
        <section class="profile-editor" aria-labelledby="profile-details-title">
          <h2 id="profile-details-title">Personīgā informācija</h2>
          <p>Šeit vari mainīt savu vārdu un e-pasta adresi.</p>
          <form @submit.prevent="save">
            <fieldset :disabled="busy || deleting">
              <label for="profile-name">Tavs vārds</label>
              <input id="profile-name" v-model="name" autocomplete="name" required maxlength="100" pattern=".*\S.*" />
              <label for="profile-email">E-pasta adrese</label>
              <input id="profile-email" v-model="email" type="email" autocomplete="email" required maxlength="255" aria-describedby="profile-email-hint" />
              <p id="profile-email-hint" class="profile-hint">Izmanto šo e-pasta adresi, lai pieslēgtos savam kontam.</p>
              <div class="profile-form-actions">
                <button class="button pink-button" type="submit" :disabled="busy || !changed">{{ busy ? 'Saglabā…' : 'Saglabāt izmaiņas ↗' }}</button>
                <button class="button outline-button" type="button" :disabled="busy || !changed" @click="reset">Atcelt</button>
              </div>
            </fieldset>
            <p v-if="error" class="form-message" role="alert">{{ error }}</p>
            <p v-if="success" class="profile-success" role="status">{{ success }}</p>
          </form>
        </section>
      </div>
      <section class="profile-editor profile-danger" aria-labelledby="delete-account-title">
        <h2 id="delete-account-title">Dzēst kontu</h2>
        <p>Konts tiks neatgriezeniski dzēsts, un tu tiksi izrakstīts. Esošie pasūtījumu ieraksti tiks saglabāti bez tava vārda un saites uz kontu.</p>
        <button v-if="!showDeleteForm" class="button delete-account-button" type="button" :disabled="busy" @click="showDeleteForm = true">Dzēst manu kontu</button>
        <form v-else @submit.prevent="removeAccount">
          <fieldset :disabled="deleting || busy">
            <label for="delete-account-password">Pašreizējā parole</label>
            <input id="delete-account-password" v-model="deletePassword" type="password" autocomplete="current-password" required />
            <label class="delete-account-confirmation" for="delete-account-confirmation">
              <input id="delete-account-confirmation" v-model="deleteConfirmed" type="checkbox" required />
              <span>Es saprotu, ka konta dzēšanu nevar atsaukt.</span>
            </label>
            <div class="profile-form-actions">
              <button class="button delete-account-button" type="submit" :disabled="deleting || busy || !deleteConfirmed">{{ deleting ? 'Dzēš kontu…' : 'Neatgriezeniski dzēst kontu' }}</button>
              <button class="button outline-button" type="button" @click="cancelDeletion">Atcelt</button>
            </div>
          </fieldset>
          <p v-if="deleteError" class="form-message" role="alert">{{ deleteError }}</p>
        </form>
      </section>
    </template>
    <section v-else-if="accountDeleted" class="profile-editor profile-guest" role="status">
      <h2>Tavs konts ir dzēsts</h2>
      <p>Tu esi izrakstīts. Paldies, ka biji kopā ar ShocoStore.</p>
      <a class="button pink-button" href="#/veikals">Uz veikalu ↗</a>
    </section>
    <section v-else class="profile-editor profile-guest">
      <h2>Pieslēdzies savam kontam</h2>
      <p>Lai apskatītu un rediģētu savu informāciju, vispirms pieslēdzies.</p>
      <button class="button pink-button" @click="$emit('login')">Pieslēgties ↗</button>
    </section>
  </main>
</template>
