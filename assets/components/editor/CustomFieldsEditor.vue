<template>
  <section class="cb-card">
    <h2 class="cb-card__title"><AppIcon name="note" :size="16" />{{ t('section.custom') }}</h2>

    <div v-for="row in rows" :key="row.key" class="cb-edit-row">
      <div class="cb-field">
        <input
          v-model="row.name"
          class="cb-input"
          :class="{ 'has-error': errorFor(row, 'key') }"
          maxlength="64"
          autocomplete="off"
          :placeholder="t('field.customKey')"
        />
        <span v-if="errorFor(row, 'key')" class="cb-field__error">{{ errorFor(row, 'key') }}</span>
      </div>
      <div class="cb-field cb-field--grow">
        <input
          v-model="row.value"
          class="cb-input"
          :class="{ 'has-error': errorFor(row, 'value') }"
          maxlength="1000"
          autocomplete="off"
          :placeholder="t('field.customValue')"
        />
        <span v-if="errorFor(row, 'value')" class="cb-field__error">{{ errorFor(row, 'value') }}</span>
      </div>
      <button type="button" class="cb-icon-button" :title="t('editor.remove')" @click="remove(row)">
        <AppIcon name="close" :size="16" />
      </button>
    </div>

    <p class="cb-card__hint">{{ t('custom.hint') }}</p>

    <button type="button" class="cb-link-button" @click="add">
      <AppIcon name="plus" :size="15" />
      {{ t('editor.add') }}
    </button>
  </section>
</template>

<script setup lang="ts">
import AppIcon from '../ui/AppIcon.vue'
import { t } from '../../i18n'
import { createCustomFieldRow } from '../../helpers/contact'
import type { CustomFieldRow, FieldErrors } from '../../types/contact'

const props = defineProps<{ rows: CustomFieldRow[]; errors: FieldErrors }>()

function errorFor(row: CustomFieldRow, field: string): string | undefined {
  const index = props.rows.indexOf(row)

  return props.errors[`customFields.${index}.${field}`]
}

function add(): void {
  props.rows.push(createCustomFieldRow())
}

function remove(row: CustomFieldRow): void {
  const index = props.rows.indexOf(row)
  props.rows.splice(index, 1)
}
</script>
