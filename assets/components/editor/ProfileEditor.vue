<template>
  <section class="cb-card">
    <h2 class="cb-card__title"><AppIcon name="user" :size="16" />{{ t('section.profile') }}</h2>
    <div class="cb-form-grid cb-form-grid--single">
      <label class="cb-field">
        <span class="cb-field__label">{{ t('field.gender') }}</span>
        <select class="cb-select" :class="{ 'has-error': errors.gender }" :value="gender" @change="onGenderChange">
          <option value="">—</option>
          <option v-for="option in genderOptions" :key="option" :value="option">{{ genderLabel(option) }}</option>
        </select>
        <span v-if="errors.gender" class="cb-field__error">{{ errors.gender }}</span>
      </label>
      <label class="cb-field">
        <span class="cb-field__label">{{ t('field.pronouns') }}</span>
        <input
          class="cb-input"
          :value="pronouns"
          maxlength="64"
          autocomplete="off"
          :placeholder="t('field.pronounsHint')"
          @input="onPronounsInput"
        />
      </label>
      <label class="cb-field">
        <span class="cb-field__label">{{ t('field.language') }}</span>
        <select
          class="cb-select"
          :class="{ 'has-error': errors.language }"
          :value="language"
          @change="onLanguageChange"
        >
          <option value="">—</option>
          <option v-for="option in languageOptions" :key="option.code" :value="option.code">
            {{ option.label }}
          </option>
        </select>
        <span v-if="errors.language" class="cb-field__error">{{ errors.language }}</span>
      </label>
      <label class="cb-field">
        <span class="cb-field__label">{{ t('field.timezone') }}</span>
        <select
          class="cb-select"
          :class="{ 'has-error': errors.timezone }"
          :value="timezone"
          @change="onTimezoneChange"
        >
          <option value="">—</option>
          <option v-for="option in timezoneOptions" :key="option" :value="option">{{ option }}</option>
        </select>
        <span v-if="errors.timezone" class="cb-field__error">{{ errors.timezone }}</span>
      </label>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import { locale, t } from '../../i18n'
import type { FieldErrors } from '../../types/contact'

interface LanguageOption {
  code: string
  label: string
}

const LANGUAGE_CODES = [
  'en',
  'ru',
  'cs',
  'uk',
  'sk',
  'pl',
  'de',
  'fr',
  'es',
  'it',
  'pt',
  'nl',
  'sv',
  'tr',
  'ar',
  'zh',
  'ja',
  'ko',
]

const props = defineProps<{
  gender: string
  pronouns: string
  language: string
  timezone: string
  errors: FieldErrors
}>()

const emit = defineEmits<{
  (event: 'update:gender', value: string): void
  (event: 'update:pronouns', value: string): void
  (event: 'update:language', value: string): void
  (event: 'update:timezone', value: string): void
}>()

const genderOptions = ['M', 'F', 'O', 'N', 'U']

const languageOptions = computed<LanguageOption[]>(() => {
  const codes = [...LANGUAGE_CODES]
  if (props.language !== '' && !codes.includes(props.language)) {
    codes.push(props.language)
  }

  const names = new Intl.DisplayNames([locale.value], { type: 'language' })

  return codes.map((code: string) => {
    let name = code
    try {
      name = names.of(code) ?? code
    } catch {
      name = code
    }

    return { code, label: `${name} (${code})` }
  })
})

const timezoneOptions = computed<string[]>(() => {
  const intl = Intl as unknown as { supportedValuesOf?: (key: string) => string[] }
  const zones = intl.supportedValuesOf?.('timeZone') ?? []
  const result = [...zones]

  if (props.timezone !== '' && !result.includes(props.timezone)) {
    result.unshift(props.timezone)
  }

  return result
})

function genderLabel(option: string): string {
  return t(`gender.${option}`)
}

function onGenderChange(event: Event): void {
  emit('update:gender', (event.target as HTMLSelectElement).value)
}

function onPronounsInput(event: Event): void {
  emit('update:pronouns', (event.target as HTMLInputElement).value)
}

function onLanguageChange(event: Event): void {
  emit('update:language', (event.target as HTMLSelectElement).value)
}

function onTimezoneChange(event: Event): void {
  emit('update:timezone', (event.target as HTMLSelectElement).value)
}
</script>
