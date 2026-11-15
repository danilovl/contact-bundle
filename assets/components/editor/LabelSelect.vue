<template>
  <div class="cb-label-select">
    <select v-if="!custom" class="cb-select" :value="modelValue" :aria-label="t('field.label')" @change="onSelect">
      <option v-for="preset in presets" :key="preset" :value="preset">{{ translateLabel(preset) }}</option>
      <option value="__custom">{{ t('label.custom') }}</option>
    </select>
    <input
      v-else
      ref="customInput"
      class="cb-input"
      :value="modelValue"
      :placeholder="t('field.customLabel')"
      maxlength="64"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      @blur="onBlur"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import { t, translateLabel } from '../../i18n'

const props = defineProps<{ modelValue: string; presets: string[] }>()

const emit = defineEmits<{ (event: 'update:modelValue', value: string): void }>()

const forceCustom = ref(false)
const customInput = ref<HTMLInputElement | null>(null)

const custom = computed(() => {
  return forceCustom.value || (props.modelValue !== '' && !props.presets.includes(props.modelValue))
})

async function onSelect(event: Event): Promise<void> {
  const value = (event.target as HTMLSelectElement).value
  if (value !== '__custom') {
    emit('update:modelValue', value)

    return
  }

  forceCustom.value = true
  emit('update:modelValue', '')
  await nextTick()
  customInput.value?.focus()
}

function onBlur(): void {
  if (props.modelValue.trim() === '') {
    forceCustom.value = false
    emit('update:modelValue', props.presets[0] ?? '')
  }
}
</script>
