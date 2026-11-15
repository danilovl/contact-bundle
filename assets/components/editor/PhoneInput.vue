<template>
  <div class="cb-phone-input" :class="{ 'has-error': error }">
    <select class="cb-select cb-phone-input__code" :value="code" :aria-label="t('field.phoneCode')" @change="onCode">
      <option value="">{{ t('field.phoneNoCode') }}</option>
      <option v-for="option in options" :key="option.code" :value="option.code">{{ option.label }}</option>
    </select>
    <input
      class="cb-input"
      type="tel"
      inputmode="tel"
      :value="number"
      :placeholder="t('field.phone')"
      maxlength="64"
      @input="onNumber"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { locale, t } from '../../i18n'
import { COUNTRIES, buildPhone, flagEmoji, getCountryName, getDialCode, splitPhone } from '../../helpers/countries'
import type { CountryInfo } from '../../helpers/countries'

interface PhoneOption {
  code: string
  label: string
  name: string
}

const props = defineProps<{ modelValue: string; error?: string }>()

const emit = defineEmits<{ (event: 'update:modelValue', value: string): void }>()

const initial = splitPhone(props.modelValue)
const code = ref(initial.code)
const number = ref(initial.number)

const options = computed<PhoneOption[]>(() => {
  const items = COUNTRIES.map((country: CountryInfo) => {
    const name = getCountryName(country.code, locale.value)

    return { code: country.code, name, label: `${flagEmoji(country.code)} +${country.dial} ${name}` }
  })

  return items.sort((first: PhoneOption, second: PhoneOption) => {
    return first.name.localeCompare(second.name, locale.value)
  })
})

watch(
  () => {
    return props.modelValue
  },
  (value) => {
    if (value === buildPhone(code.value, number.value)) {
      return
    }

    const parts = splitPhone(value)
    code.value = parts.code
    number.value = parts.number
  },
)

function publish(): void {
  emit('update:modelValue', buildPhone(code.value, number.value))
}

function onCode(event: Event): void {
  code.value = (event.target as HTMLSelectElement).value
  publish()
}

function onNumber(event: Event): void {
  const value = (event.target as HTMLInputElement).value
  const isInternational = value.trim().startsWith('+')

  if (isInternational) {
    const parts = splitPhone(value)
    if (parts.code !== '' && getDialCode(parts.code) !== '') {
      code.value = parts.code
      number.value = parts.number
      publish()

      return
    }
  }

  number.value = value
  publish()
}
</script>
