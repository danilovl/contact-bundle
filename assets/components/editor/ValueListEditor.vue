<template>
  <section class="cb-card">
    <h2 class="cb-card__title">
      <AppIcon :name="icon" :size="16" />
      {{ title }}
    </h2>

    <div v-for="row in rows" :key="row.key" class="cb-edit-row">
      <LabelSelect v-model="row.label" :presets="presets" />
      <div class="cb-field cb-field--grow">
        <PhoneInput v-if="kind === 'phones'" v-model="row.value" :error="errorFor(row)" />
        <RelationInput
          v-else-if="kind === 'relations'"
          v-model="row.value"
          v-model:related-contact-id="row.relatedContactId"
          :contacts="contacts ?? []"
          :error="errorFor(row)"
          @open-contact="emit('open-contact', $event)"
        />
        <input
          v-else
          v-model="row.value"
          class="cb-input"
          :class="{ 'has-error': errorFor(row) }"
          :type="inputType"
          :inputmode="inputMode"
          :placeholder="placeholder"
          maxlength="255"
        />
        <span v-if="errorFor(row)" class="cb-field__error">{{ errorFor(row) }}</span>
      </div>
      <label v-if="withPrimary" class="cb-check" :title="t('field.primary')">
        <input type="checkbox" :checked="row.primary" @change="setPrimary(row)" />
        <AppIcon name="star" :size="15" :filled="row.primary" />
      </label>
      <button type="button" class="cb-icon-button" :title="t('editor.remove')" @click="remove(row)">
        <AppIcon name="close" :size="16" />
      </button>
    </div>

    <p v-if="kind === 'relations'" class="cb-card__hint">{{ t('relation.sectionHint') }}</p>

    <button type="button" class="cb-link-button" @click="add">
      <AppIcon name="plus" :size="15" />
      {{ t('editor.add') }}
    </button>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import LabelSelect from './LabelSelect.vue'
import PhoneInput from './PhoneInput.vue'
import RelationInput from './RelationInput.vue'
import { t } from '../../i18n'
import { LABEL_PRESETS, createValueRow } from '../../helpers/contact'
import type { FieldErrors, RelationOption, ValueKind, ValueRow } from '../../types/contact'

const props = defineProps<{
  kind: ValueKind
  rows: ValueRow[]
  title: string
  icon: string
  errors: FieldErrors
  contacts?: RelationOption[]
}>()

const emit = defineEmits<{ (event: 'open-contact', id: number): void }>()

const presets = computed(() => {
  return LABEL_PRESETS[props.kind] ?? []
})

const withPrimary = computed(() => {
  return props.kind === 'emails' || props.kind === 'phones'
})

const inputType = computed(() => {
  const types: Record<string, string> = { emails: 'email', phones: 'tel', urls: 'url' }

  return types[props.kind] ?? 'text'
})

const inputMode = computed(() => {
  const modes: Record<string, 'email' | 'tel' | 'url' | 'text'> = { emails: 'email', phones: 'tel', urls: 'url' }

  return modes[props.kind] ?? 'text'
})

const placeholder = computed(() => {
  const keys: Record<string, string> = {
    emails: 'field.email',
    phones: 'field.phone',
    urls: 'field.url',
    messengers: 'field.messenger',
  }

  return t(keys[props.kind] ?? 'field.label')
})

function errorFor(row: ValueRow): string | undefined {
  const index = props.rows.indexOf(row)

  return props.errors[`${props.kind}.${index}.value`]
}

function add(): void {
  props.rows.push(createValueRow(props.kind))
}

function remove(row: ValueRow): void {
  const index = props.rows.indexOf(row)
  props.rows.splice(index, 1)
}

function setPrimary(selected: ValueRow): void {
  const isPrimary = selected.primary
  props.rows.forEach((row: ValueRow) => {
    row.primary = row === selected ? !isPrimary : false
  })
}
</script>
