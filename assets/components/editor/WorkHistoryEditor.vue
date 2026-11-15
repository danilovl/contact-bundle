<template>
  <section class="cb-card cb-card--wide">
    <h2 class="cb-card__title cb-card__title--toggle">
      <button
        type="button"
        class="cb-card__toggle"
        :aria-expanded="isVisible"
        :title="isVisible ? t('work.collapse') : t('work.expand')"
        @click="toggle"
      >
        <AppIcon name="briefcase" :size="16" />
        {{ t('section.workHistory') }}
        <span class="cb-card__count">{{ rows.length }}</span>
        <AppIcon name="chevron" :size="16" class="cb-card__chevron" :class="{ 'is-open': isVisible }" />
      </button>
    </h2>

    <div v-if="isVisible" class="cb-card__body">
      <div v-for="row in rows" :key="row.key" class="cb-work-row">
        <div class="cb-work-row__main">
          <input
            v-model="row.company"
            class="cb-input"
            :class="{ 'has-error': emptyError(row) }"
            :placeholder="t('work.company')"
            maxlength="255"
          />
          <input
            v-model="row.jobTitle"
            class="cb-input"
            :class="{ 'has-error': emptyError(row) }"
            :placeholder="t('work.title')"
            maxlength="255"
          />
        </div>
        <span v-if="emptyError(row)" class="cb-field__error">{{ emptyError(row) }}</span>
        <div class="cb-work-row__period">
          <label class="cb-field">
            <span class="cb-field__label">{{ t('work.start') }}</span>
            <input v-model="row.startDate" class="cb-input" type="month" />
          </label>
          <label class="cb-field">
            <span class="cb-field__label">{{ t('work.end') }}</span>
            <input
              v-model="row.endDate"
              class="cb-input"
              :class="{ 'has-error': errorFor(row) }"
              type="month"
              :disabled="row.current"
            />
          </label>
          <label class="cb-checkbox">
            <input v-model="row.current" type="checkbox" />
            {{ t('work.current') }}
          </label>
          <button type="button" class="cb-icon-button" :title="t('editor.remove')" @click="remove(row)">
            <AppIcon name="close" :size="16" />
          </button>
        </div>
        <span v-if="errorFor(row)" class="cb-field__error">{{ errorFor(row) }}</span>
      </div>

      <div class="cb-inline">
        <button type="button" class="cb-link-button" @click="add">
          <AppIcon name="plus" :size="15" />
          {{ t('editor.add') }}
        </button>
        <input ref="fileInput" type="file" accept=".csv,text/csv" hidden @change="onFileChange" />
        <button type="button" class="cb-link-button" :title="t('work.importHint')" @click="openFile">
          <AppIcon name="upload" :size="15" />
          {{ t('work.importLinkedIn') }}
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import { t } from '../../i18n'
import { createWorkRow } from '../../helpers/contact'
import { parseLinkedInPositions } from '../../helpers/workHistory'
import type { ImportedPosition } from '../../helpers/workHistory'
import { useToast } from '../../composables/useToast'
import type { FieldErrors, WorkRow } from '../../types/contact'

const props = defineProps<{ rows: WorkRow[]; errors: FieldErrors }>()

const COLLAPSE_THRESHOLD = 3

const { show } = useToast()
const fileInput = ref<HTMLInputElement | null>(null)
const isOpen = ref(props.rows.length <= COLLAPSE_THRESHOLD)

const hasErrors = computed(() => {
  return Object.keys(props.errors).some((key: string) => {
    return key.startsWith('workHistory.')
  })
})

const isVisible = computed(() => {
  return isOpen.value || hasErrors.value
})

function toggle(): void {
  isOpen.value = !isVisible.value
}

function add(): void {
  isOpen.value = true
  props.rows.push(createWorkRow({ company: '' }))
}

function remove(row: WorkRow): void {
  const index = props.rows.indexOf(row)
  props.rows.splice(index, 1)
}

function emptyError(row: WorkRow): string | undefined {
  const index = props.rows.indexOf(row)

  return props.errors[`workHistory.${index}.company`]
}

function errorFor(row: WorkRow): string | undefined {
  const index = props.rows.indexOf(row)

  return props.errors[`workHistory.${index}.endDate`]
}

function openFile(): void {
  fileInput.value?.click()
}

async function onFileChange(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) {
    return
  }

  const text = await file.text()
  const positions = parseLinkedInPositions(text)
  if (positions.length === 0) {
    show(t('work.importEmpty'), 'error')

    return
  }

  positions.forEach((position: ImportedPosition) => {
    props.rows.push(createWorkRow(position))
  })
  isOpen.value = true
  show(t('work.imported', { count: positions.length }), 'success')
}
</script>
