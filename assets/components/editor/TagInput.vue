<template>
  <div class="cb-tags" @click="input?.focus()">
    <span v-for="tag in modelValue" :key="tag" class="cb-chip cb-chip--removable">
      {{ tag }}
      <button type="button" :title="t('editor.remove')" @click.stop="remove(tag)">
        <AppIcon name="close" :size="12" />
      </button>
    </span>
    <input
      ref="input"
      v-model="draft"
      class="cb-tags__input"
      :placeholder="modelValue.length ? '' : t('field.tags')"
      :list="listId"
      maxlength="64"
      @keydown.enter.prevent="commit"
      @keydown.,.prevent="commit"
      @keydown.backspace="onBackspace"
      @blur="commit"
    />
    <datalist :id="listId">
      <option v-for="suggestion in availableSuggestions" :key="suggestion" :value="suggestion" />
    </datalist>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import { t } from '../../i18n'

const props = defineProps<{ modelValue: string[]; suggestions: string[] }>()

const emit = defineEmits<{ (event: 'update:modelValue', value: string[]): void }>()

const draft = ref('')
const input = ref<HTMLInputElement | null>(null)
const listId = `cb-tags-${Math.random().toString(36).slice(2)}`

const availableSuggestions = computed(() => {
  return props.suggestions.filter((suggestion: string) => {
    return !props.modelValue.includes(suggestion)
  })
})

function commit(): void {
  const value = draft.value.trim()
  draft.value = ''

  if (value === '') {
    return
  }

  const exists = props.modelValue.some((tag: string) => {
    return tag.toLowerCase() === value.toLowerCase()
  })

  if (!exists) {
    emit('update:modelValue', [...props.modelValue, value])
  }
}

function remove(tag: string): void {
  const copy = props.modelValue.filter((item: string) => {
    return item !== tag
  })
  emit('update:modelValue', copy)
}

function onBackspace(): void {
  if (draft.value === '' && props.modelValue.length > 0) {
    const lastTag = props.modelValue[props.modelValue.length - 1]
    remove(lastTag)
  }
}
</script>
