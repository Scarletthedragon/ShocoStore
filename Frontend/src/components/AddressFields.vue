<script setup>
defineProps({ modelValue: { type: Object, required: true }, prefix: { type: String, default: 'address' }, disabled: Boolean })
const emit = defineEmits(['update:modelValue'])
const fields = [
  ['recipient', 'Saņēmēja vārds, uzvārds', 'name', 100],
  ['phone', 'Tālrunis', 'tel', 40],
  ['street', 'Iela, mājas un dzīvokļa numurs', 'street-address', 255],
  ['city', 'Pilsēta / novads', 'address-level2', 100],
  ['postalCode', 'Pasta indekss', 'postal-code', 20],
  ['country', 'Valsts', 'country-name', 100],
]
</script>

<template>
  <fieldset class="address-fields" :disabled="disabled">
    <div v-for="[key, label, autocomplete, max] in fields" :key="key">
      <label :for="`${prefix}-${key}`">{{ label }}</label>
      <input :id="`${prefix}-${key}`" :value="modelValue[key]" :type="key === 'phone' ? 'tel' : 'text'" :autocomplete="autocomplete" :maxlength="max" pattern=".*\S.*" required @input="emit('update:modelValue', { ...modelValue, [key]: $event.target.value })" />
    </div>
  </fieldset>
</template>

<style scoped>
.address-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;border:0;padding:0;margin:18px 0;min-width:0}
.address-fields label{display:block;font-size:13px;font-weight:600;margin-bottom:7px}
.address-fields input{width:100%;min-width:0;padding:12px;border:1px solid var(--theme-border);border-radius:10px;background:var(--page-bg);color:var(--page-text);font:inherit}
@media(max-width:560px){.address-fields{grid-template-columns:minmax(0,1fr)}}
</style>
