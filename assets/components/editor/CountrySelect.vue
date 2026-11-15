<template>
  <select class="cb-select" :value="modelValue" :aria-label="t('field.country')" @change="onChange">
    <option value="">{{ t('field.countrySelect') }}</option>
    <option v-if="isUnknown" :value="modelValue">{{ modelValue }}</option>
    <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
  </select>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { locale, t } from '../../i18n'
import { COUNTRIES, flagEmoji, getCountryName, getEnglishCountryName } from '../../helpers/countries'
import type { CountryInfo } from '../../helpers/countries'

interface CountryOption {
  value: string
  name: string
  label: string
}

const props = defineProps<{ modelValue: string }>()

const emit = defineEmits<{ (event: 'update:modelValue', value: string): void }>()

const options = computed<CountryOption[]>(() => {
  const items = COUNTRIES.map((country: CountryInfo) => {
    const name = getCountryName(country.code, locale.value)

    return { value: getEnglishCountryName(country.code), name, label: `${flagEmoji(country.code)} ${name}` }
  })

  return items.sort((first: CountryOption, second: CountryOption) => {
    return first.name.localeCompare(second.name, locale.value)
  })
})

const isUnknown = computed(() => {
  if (props.modelValue === '') {
    return false
  }

  return !options.value.some((option: CountryOption) => {
    return option.value === props.modelValue
  })
})

function onChange(event: Event): void {
  emit('update:modelValue', (event.target as HTMLSelectElement).value)
}
</script>
