<template>
  <section class="cb-list">
    <div class="cb-list__toolbar">
      <button type="button" class="cb-icon-button cb-only-mobile" :title="t('app.menu')" @click="emit('open-menu')">
        <AppIcon name="menu" />
      </button>
      <label class="cb-search">
        <AppIcon name="search" :size="16" />
        <input
          :value="search"
          type="search"
          :placeholder="t('list.search')"
          @input="emit('update:search', ($event.target as HTMLInputElement).value)"
        />
      </label>
    </div>

    <div class="cb-list__meta">
      <span>{{ t('list.count', { count: total }) }}</span>
      <div v-if="filterType !== 'birthdays'" class="cb-sort">
        <select
          class="cb-select cb-select--small"
          :value="sortBy"
          :title="t('list.sortBy')"
          @change="emit('update:sortBy', ($event.target as HTMLSelectElement).value as SortField)"
        >
          <option v-for="field in sortFields" :key="field" :value="field">{{ t(`list.sort.${field}`) }}</option>
        </select>
        <button
          type="button"
          class="cb-icon-button cb-sort__direction"
          :class="{ 'is-desc': sortDir === 'desc' }"
          :title="sortDir === 'asc' ? t('list.sortAsc') : t('list.sortDesc')"
          @click="emit('update:sortDir', sortDir === 'asc' ? 'desc' : 'asc')"
        >
          <AppIcon name="chevron" :size="16" />
        </button>
      </div>
    </div>

    <div class="cb-list__body">
      <div v-if="loading" class="cb-state">{{ t('list.loading') }}</div>
      <div v-else-if="groups.length === 0" class="cb-state">
        <AppIcon name="users" :size="40" />
        <strong>{{ isSearching ? t('list.notFound') : t('list.empty') }}</strong>
        <span v-if="!isSearching" class="cb-muted">{{ t('list.emptyHint') }}</span>
      </div>

      <template v-for="group in groups" :key="group.letter">
        <div v-if="group.letter" class="cb-list__letter">{{ group.letter }}</div>
        <button
          v-for="item in group.items"
          :key="item.id"
          type="button"
          class="cb-list__item"
          :class="{ 'is-active': item.id === selectedId }"
          @click="emit('select', item.id)"
        >
          <ContactAvatar :name="item.displayName" :photo-src="item.hasPhoto ? photoUrl(item) : null" size="sm" />
          <span class="cb-list__text">
            <span class="cb-list__name">{{ item.displayName }}</span>
            <span class="cb-list__sub">{{ subtitle(item) }}</span>
          </span>
          <span v-if="birthdayBadge(item)" class="cb-badge cb-badge--accent">
            <AppIcon name="gift" :size="12" />
            {{ birthdayBadge(item) }}
          </span>
          <span
            class="cb-star"
            :class="{ 'is-active': item.favorite }"
            role="button"
            :title="item.favorite ? t('detail.unfavorite') : t('detail.favorite')"
            @click.stop="emit('toggle-favorite', item)"
          >
            <AppIcon name="star" :size="16" :filled="item.favorite" />
          </span>
        </button>
      </template>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import ContactAvatar from '../ui/ContactAvatar.vue'
import { t } from '../../i18n'
import { getUpcoming } from '../../helpers/contact'
import { BIRTHDAY_WINDOW_DAYS, type ContactGroup } from '../../composables/useContacts'
import type { ContactSummary, SortDirection, SortField } from '../../types/contact'

const sortFields: SortField[] = ['first', 'last', 'company', 'jobTitle', 'birthday', 'created', 'updated']

const props = defineProps<{
  groups: ContactGroup[]
  loading: boolean
  search: string
  sortBy: SortField
  sortDir: SortDirection
  selectedId: number | null
  filterType: string
  total: number
  photoUrl: (item: ContactSummary) => string
}>()

const emit = defineEmits<{
  (event: 'update:search', value: string): void
  (event: 'update:sortBy', value: SortField): void
  (event: 'update:sortDir', value: SortDirection): void
  (event: 'select', id: number): void
  (event: 'toggle-favorite', item: ContactSummary): void
  (event: 'open-menu'): void
}>()

const isSearching = computed(() => {
  return props.search.trim() !== '' || props.filterType !== 'all'
})

function subtitle(item: ContactSummary): string {
  const work = [item.jobTitle, item.company].filter(Boolean).join(' · ')

  return work || item.phones[0] || item.emails[0] || item.nickname || ''
}

function birthdayBadge(item: ContactSummary): string {
  const upcoming = getUpcoming(item.birthday)
  if (!upcoming || upcoming.days > BIRTHDAY_WINDOW_DAYS) {
    return ''
  }

  if (upcoming.days === 0) {
    return t('birthday.today')
  }

  if (upcoming.days === 1) {
    return t('birthday.tomorrow')
  }

  return t('birthday.inDays', { days: upcoming.days })
}
</script>
