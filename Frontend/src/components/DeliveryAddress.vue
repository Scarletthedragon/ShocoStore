<script setup>
import { ref, watch } from 'vue'
import { user } from '../api/state.js'
import { saveAddress } from '../api/store.js'
import AddressFields from './AddressFields.vue'

const address = ref({})
const busy = ref(false), error = ref(''), message = ref('')
watch(() => user.value?.address, value => { address.value = { recipient: user.value?.name || '', country: 'Latvija', ...value } }, { immediate: true })
async function save() {
  if (busy.value) return
  busy.value = true; error.value = ''; message.value = ''
  try {
    const result = await saveAddress(address.value)
    user.value = { ...user.value, address: result.address }
    message.value = 'Piegādes adrese saglabāta. Esošo pasūtījumu adreses netiek mainītas.'
  } catch (cause) { error.value = cause.message }
  finally { busy.value = false }
}
</script>
<template>
  <section class="profile-editor delivery-address">
    <h2>Piegādes adrese</h2>
    <p>Saglabā adresi, uz kuru vēlies saņemt pasūtījumus.</p>
    <form @submit.prevent="save">
      <AddressFields v-model="address" :disabled="busy" prefix="profile-delivery" />
      <button class="button pink-button" :disabled="busy">{{ busy ? 'Saglabā…' : 'Saglabāt adresi' }}</button>
      <p v-if="error" class="form-message" role="alert">{{ error }}</p>
      <p v-if="message" class="profile-success" role="status">{{ message }}</p>
    </form>
  </section>
</template>
<style scoped>.delivery-address{margin-top:28px}</style>
