<template>
  <div class="cb-date-input" :class="{ 'has-error': error }">
    <input
      class="cb-input"
      type="date"
      min="1000-01-01"
      :max="max"
      :value="dateValue"
      :aria-label="t('section.dates')"
      @input="onDate"
    />
    <label class="cb-checkbox">
      <input type="checkbox" :checked="yearless" @change="onYearless" />
      {{ t('date.noYear') }}
    </label>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { t } from '../../i18n'
import { parsePartialDate } from '../../helpers/contact'

const YEARLESS_YEAR = 2000

const props = defineProps<{ modelValue: string; error?: string; max?: string }>()

const emit = defineEmits<{ (event: 'update:modelValue', value: string): void }>()

const yearless = ref(false)

watch(
  () => {
    return props.modelValue
  },
  (value) => {
    const parsed = parsePartialDate(value)
    if (parsed) {
      yearless.value = parsed.year === null
    }
  },
  { immediate: true },
)

const dateValue = computed(() => {
  const parsed = parsePartialDate(props.modelValue)
  if (!parsed) {
    return ''
  }

  const year = String(parsed.year ?? YEARLESS_YEAR).padStart(4, '0')
  const month = String(parsed.month).padStart(2, '0')
  const day = String(parsed.day).padStart(2, '0')

  return `${year}-${month}-${day}`
})

function publish(date: string): void {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
    emit('update:modelValue', '')

    return
  }

  emit('update:modelValue', yearless.value ? `--${date.slice(5)}` : date)
}

function onDate(event: Event): void {
  publish((event.target as HTMLInputElement).value)
}

function onYearless(event: Event): void {
  yearless.value = (event.target as HTMLInputElement).checked

  const date = dateValue.value
  publish(yearless.value && date !== '' ? `${YEARLESS_YEAR}${date.slice(4)}` : date)
}
</script>
