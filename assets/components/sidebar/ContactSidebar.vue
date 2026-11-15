<template>
  <aside class="cb-sidebar" :class="{ 'is-open': open }">
    <div class="cb-sidebar__head">
      <span class="cb-sidebar__title">
        <AppIcon name="users" :size="20" />
        {{ title }}
      </span>
      <button type="button" class="cb-icon-button cb-only-mobile" :title="t('app.back')" @click="emit('close')">
        <AppIcon name="close" />
      </button>
    </div>

    <button type="button" class="cb-button cb-button--primary cb-button--block" @click="emit('create')">
      <AppIcon name="plus" />
      {{ t('app.newContact') }}
    </button>

    <nav class="cb-nav">
      <button
        v-for="item in mainFilters"
        :key="item.type"
        type="button"
        class="cb-nav__item"
        :class="{ 'is-active': filter.type === item.type }"
        @click="emit('select-filter', { type: item.type } as ContactFilter)"
      >
        <AppIcon :name="item.icon" :filled="item.type === 'favorites' && filter.type === 'favorites'" />
        <span class="cb-nav__label">{{ t(item.label) }}</span>
        <span class="cb-nav__count">{{ item.count }}</span>
      </button>
    </nav>

    <div class="cb-sidebar__section">{{ t('filter.tags') }}</div>
    <nav class="cb-nav cb-nav--scroll">
      <p v-if="tags.length === 0" class="cb-muted cb-sidebar__hint">{{ t('filter.noTags') }}</p>
      <button
        v-for="item in tags"
        :key="item.tag"
        type="button"
        class="cb-nav__item"
        :class="{ 'is-active': filter.type === 'tag' && filter.tag === item.tag }"
        @click="emit('select-filter', { type: 'tag', tag: item.tag })"
      >
        <AppIcon name="tag" />
        <span class="cb-nav__label">{{ item.tag }}</span>
        <span class="cb-nav__count">{{ item.count }}</span>
      </button>
    </nav>

    <div class="cb-sidebar__footer">
      <label class="cb-language">
        <AppIcon name="globe" :size="16" />
        <select class="cb-select" :value="locale" :aria-label="t('app.language')" @change="onLocaleChange">
          <option v-for="code in localeCodes" :key="code" :value="code">{{ LOCALE_NAMES[code] }}</option>
        </select>
      </label>
      <input ref="fileInput" type="file" accept=".vcf,text/vcard,text/x-vcard" hidden @change="onFileChange" />
      <button type="button" class="cb-button cb-button--ghost cb-button--block" :disabled="importing" @click="openFile">
        <AppIcon name="upload" />
        {{ importing ? t('io.importing') : t('io.import') }}
      </button>
      <a class="cb-button cb-button--ghost cb-button--block" :href="exportUrl">
        <AppIcon name="download" />
        {{ t('io.export') }}
      </a>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import { LOCALE_NAMES, SUPPORTED_LOCALES, changeLocale, locale, t } from '../../i18n'
import type { ContactFilter } from '../../types/contact'
import type { TagCount } from '../../composables/useContacts'

const props = defineProps<{
  title: string
  open: boolean
  filter: ContactFilter
  counts: { all: number; favorites: number; birthdays: number }
  tags: TagCount[]
  importing: boolean
  exportUrl: string
}>()

const emit = defineEmits<{
  (event: 'select-filter', filter: ContactFilter): void
  (event: 'create'): void
  (event: 'import', file: File): void
  (event: 'close'): void
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const localeCodes = SUPPORTED_LOCALES

const mainFilters = computed(() => {
  return [
    { type: 'all', icon: 'users', label: 'filter.all', count: props.counts.all },
    { type: 'favorites', icon: 'star', label: 'filter.favorites', count: props.counts.favorites },
    { type: 'birthdays', icon: 'gift', label: 'filter.birthdays', count: props.counts.birthdays },
  ]
})

function onLocaleChange(event: Event): void {
  changeLocale((event.target as HTMLSelectElement).value)
}

function openFile(): void {
  fileInput.value?.click()
}

function onFileChange(event: Event): void {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) {
    emit('import', file)
  }

  input.value = ''
}
</script>
