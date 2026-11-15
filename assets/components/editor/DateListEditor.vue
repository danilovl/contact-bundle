<template>
  <section class="cb-card">
    <h2 class="cb-card__title">
      <AppIcon name="calendar" :size="16" />
      {{ t('section.dates') }}
    </h2>

    <div class="cb-edit-row cb-edit-row--date">
      <span class="cb-edit-row__caption">
        <AppIcon name="gift" :size="15" />
        {{ t('section.birthday') }}
      </span>
      <PartialDateInput
        :model-value="birthday"
        :max="today"
        :error="errors.birthday"
        @update:model-value="emit('update:birthday', $event)"
      />
    </div>
    <span v-if="errors.birthday" class="cb-field__error">{{ errors.birthday }}</span>

    <div v-for="row in rows" :key="row.key" class="cb-edit-row cb-edit-row--date">
      <LabelSelect v-model="row.label" :presets="presets" />
      <PartialDateInput v-model="row.date" :error="dateError(row)" />
      <span v-if="dateError(row)" class="cb-field__error cb-edit-row__error">{{ dateError(row) }}</span>
      <button type="button" class="cb-icon-button" :title="t('editor.remove')" @click="remove(row)">
        <AppIcon name="close" :size="16" />
      </button>
    </div>

    <button type="button" class="cb-link-button" @click="add">
      <AppIcon name="plus" :size="15" />
      {{ t('editor.add') }}
    </button>
  </section>
</template>

<script setup lang="ts">
import AppIcon from '../ui/AppIcon.vue'
import LabelSelect from './LabelSelect.vue'
import PartialDateInput from './PartialDateInput.vue'
import { t } from '../../i18n'
import { LABEL_PRESETS, createDateRow } from '../../helpers/contact'
import type { DateRow, FieldErrors } from '../../types/contact'

const props = defineProps<{ rows: DateRow[]; birthday: string; errors: FieldErrors }>()

const emit = defineEmits<{ (event: 'update:birthday', value: string): void }>()

const presets = LABEL_PRESETS.dates

const today = new Date().toISOString().slice(0, 10)

function add(): void {
  props.rows.push(createDateRow())
}

function remove(row: DateRow): void {
  const index = props.rows.indexOf(row)
  props.rows.splice(index, 1)
}

function dateError(row: DateRow): string | undefined {
  const index = props.rows.indexOf(row)

  return props.errors[`dates.${index}.date`]
}
</script>
