<template>
  <article class="cb-detail">
    <div class="cb-detail__bar">
      <button type="button" class="cb-icon-button cb-only-mobile" :title="t('app.back')" @click="emit('back')">
        <AppIcon name="back" />
      </button>
      <div class="cb-detail__actions">
        <button
          type="button"
          class="cb-icon-button cb-star"
          :class="{ 'is-active': contact.favorite }"
          :title="contact.favorite ? t('detail.unfavorite') : t('detail.favorite')"
          @click="emit('toggle-favorite')"
        >
          <AppIcon name="star" :filled="contact.favorite" />
        </button>
        <a class="cb-button cb-button--ghost" :href="vCardUrl" :title="t('detail.vcard')">
          <AppIcon name="download" />
          <span class="cb-hide-small">{{ t('detail.vcard') }}</span>
        </a>
        <button type="button" class="cb-button cb-button--ghost cb-button--danger-text" @click="emit('delete')">
          <AppIcon name="trash" />
          <span class="cb-hide-small">{{ t('detail.delete') }}</span>
        </button>
        <button type="button" class="cb-button cb-button--primary" @click="emit('edit')">
          <AppIcon name="edit" />
          {{ t('detail.edit') }}
        </button>
      </div>
    </div>

    <div class="cb-detail__scroll">
      <header class="cb-hero">
        <ContactAvatar :name="contact.displayName" :photo-src="photoSrc" size="xl" />
        <div class="cb-hero__text">
          <h1 class="cb-hero__name">{{ contact.displayName }}</h1>
          <p v-if="contact.nickname" class="cb-hero__nickname">“{{ contact.nickname }}”</p>
          <p v-if="workLine" class="cb-hero__work">
            <AppIcon name="briefcase" :size="15" />
            {{ workLine }}
          </p>
          <div v-if="contact.tags.length" class="cb-chips">
            <span v-for="tag in contact.tags" :key="tag" class="cb-chip">{{ tag }}</span>
          </div>
        </div>
      </header>

      <div v-if="primaryPhone || primaryEmail" class="cb-quick">
        <a v-if="primaryPhone" class="cb-quick__item" :href="getPhoneHref(primaryPhone)">
          <AppIcon name="phone" />
          <span>{{ t('detail.call') }}</span>
        </a>
        <a v-if="primaryEmail" class="cb-quick__item" :href="`mailto:${primaryEmail}`">
          <AppIcon name="mail" />
          <span>{{ t('detail.write') }}</span>
        </a>
      </div>

      <div class="cb-detail__grid cb-sheet">
        <section v-if="contact.phones.length" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="phone" :size="16" />{{ t('section.phones') }}</h2>
          <div v-for="(item, index) in contact.phones" :key="index" class="cb-row">
            <div class="cb-row__main">
              <a class="cb-row__value" :href="getPhoneHref(item.value)">{{ item.value }}</a>
              <span class="cb-row__label">{{ translateLabel(item.label) }}<span v-if="item.primary"> · ★</span></span>
            </div>
            <button type="button" class="cb-icon-button" :title="t('detail.copy')" @click="copy(item.value)">
              <AppIcon name="copy" :size="15" />
            </button>
          </div>
        </section>

        <section v-if="contact.emails.length" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="mail" :size="16" />{{ t('section.emails') }}</h2>
          <div v-for="(item, index) in contact.emails" :key="index" class="cb-row">
            <div class="cb-row__main">
              <a class="cb-row__value" :href="`mailto:${item.value}`">{{ item.value }}</a>
              <span class="cb-row__label">{{ translateLabel(item.label) }}<span v-if="item.primary"> · ★</span></span>
            </div>
            <button type="button" class="cb-icon-button" :title="t('detail.copy')" @click="copy(item.value)">
              <AppIcon name="copy" :size="15" />
            </button>
          </div>
        </section>

        <section v-if="contact.messengers.length" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="chat" :size="16" />{{ t('section.messengers') }}</h2>
          <div v-for="(item, index) in contact.messengers" :key="index" class="cb-row">
            <div class="cb-row__main">
              <a
                v-if="getMessengerUrl(item.label, item.value)"
                class="cb-row__value"
                :href="getMessengerUrl(item.label, item.value) ?? undefined"
                target="_blank"
                rel="noopener noreferrer"
              >
                {{ item.value }}
              </a>
              <span v-else class="cb-row__value">{{ item.value }}</span>
              <span class="cb-row__label">{{ translateLabel(item.label) }}</span>
            </div>
            <button type="button" class="cb-icon-button" :title="t('detail.copy')" @click="copy(item.value)">
              <AppIcon name="copy" :size="15" />
            </button>
          </div>
        </section>

        <section v-if="contact.urls.length" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="link" :size="16" />{{ t('section.urls') }}</h2>
          <div v-for="(item, index) in contact.urls" :key="index" class="cb-row">
            <div class="cb-row__main">
              <a class="cb-row__value" :href="getUrlHref(item.value)" target="_blank" rel="noopener noreferrer">
                {{ item.value }}
              </a>
              <span class="cb-row__label">{{ translateLabel(item.label) }}</span>
            </div>
          </div>
        </section>

        <section v-if="contact.addresses.length" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="map" :size="16" />{{ t('section.addresses') }}</h2>
          <div v-for="(item, index) in contact.addresses" :key="index" class="cb-row">
            <div class="cb-row__main">
              <span class="cb-row__value cb-row__value--multiline">
                <span v-for="(line, lineIndex) in formatAddress(item)" :key="lineIndex">{{ line }}</span>
              </span>
              <span class="cb-row__label">{{ translateLabel(item.label) }}</span>
            </div>
            <a
              class="cb-icon-button"
              :href="getMapUrl(item)"
              target="_blank"
              rel="noopener noreferrer"
              :title="t('detail.map')"
            >
              <AppIcon name="map" :size="15" />
            </a>
          </div>
        </section>

        <section v-if="hasDates" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="calendar" :size="16" />{{ t('section.dates') }}</h2>
          <div v-if="contact.birthday" class="cb-row">
            <div class="cb-row__main">
              <span class="cb-row__value">
                <AppIcon name="gift" :size="15" />
                {{ formatPartialDate(contact.birthday, locale) }}
              </span>
              <span class="cb-row__label"
                >{{ t('section.birthday') }}<span v-if="birthdayInfo"> · {{ birthdayInfo }}</span></span
              >
            </div>
          </div>
          <div v-for="(item, index) in contact.dates" :key="index" class="cb-row">
            <div class="cb-row__main">
              <span class="cb-row__value">{{ formatPartialDate(item.date, locale) }}</span>
              <span class="cb-row__label"
                >{{ translateLabel(item.label)
                }}<span v-if="dateInfo(item.date)"> · {{ dateInfo(item.date) }}</span></span
              >
            </div>
          </div>
        </section>

        <section v-if="contact.relations.length" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="users" :size="16" />{{ t('section.relations') }}</h2>
          <div v-for="(item, index) in contact.relations" :key="index" class="cb-row">
            <div class="cb-row__main">
              <button
                v-if="findRelated(item)"
                type="button"
                class="cb-row__value cb-row__value--link"
                :title="t('relation.open')"
                @click="openRelated(item)"
              >
                {{ item.value }}
                <AppIcon name="chevron" :size="14" class="cb-row__arrow" />
              </button>
              <span v-else class="cb-row__value">{{ item.value }}</span>
              <span class="cb-row__label">{{ translateLabel(item.label) }}</span>
            </div>
          </div>
        </section>

        <section v-if="profileRows.length" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="user" :size="16" />{{ t('section.profile') }}</h2>
          <div v-for="item in profileRows" :key="item.label" class="cb-row">
            <div class="cb-row__main">
              <span class="cb-row__value">{{ item.value }}</span>
              <span class="cb-row__label">{{ item.label }}</span>
            </div>
          </div>
        </section>

        <section v-if="contact.notes" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="note" :size="16" />{{ t('section.notes') }}</h2>
          <p class="cb-notes">{{ contact.notes }}</p>
        </section>

        <section v-if="contact.customFields.length" class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="note" :size="16" />{{ t('section.custom') }}</h2>
          <div v-for="item in contact.customFields" :key="item.key" class="cb-row">
            <div class="cb-row__main">
              <span class="cb-row__value">{{ item.value }}</span>
              <span class="cb-row__label">{{ item.key }}</span>
            </div>
          </div>
        </section>

        <section v-if="workHistory.length" class="cb-card">
          <h2 class="cb-card__title cb-card__title--toggle">
            <button
              type="button"
              class="cb-card__toggle"
              :aria-expanded="isWorkOpen"
              :title="isWorkOpen ? t('work.collapse') : t('work.expand')"
              @click="toggleWork"
            >
              <AppIcon name="briefcase" :size="16" />
              {{ t('section.workHistory') }}
              <span class="cb-card__count">{{ workHistory.length }}</span>
              <AppIcon name="chevron" :size="16" class="cb-card__chevron" :class="{ 'is-open': isWorkOpen }" />
            </button>
          </h2>
          <div v-if="isWorkOpen" class="cb-card__body">
            <div v-for="(item, index) in workHistory" :key="index" class="cb-row">
              <div class="cb-row__main">
                <span class="cb-row__value">{{ item.jobTitle || item.company }}</span>
                <span v-if="item.jobTitle && item.company" class="cb-row__label">{{ item.company }}</span>
                <span class="cb-row__label">{{ formatWorkPeriod(item) }}</span>
              </div>
            </div>
          </div>
        </section>
      </div>

      <footer class="cb-detail__meta">
        {{ t('detail.created') }}: {{ formatDateTime(contact.createdAt) }} · {{ t('detail.updated') }}:
        {{ formatDateTime(contact.updatedAt) }}
        <span v-if="showOwner"> · {{ t('detail.owner') }}: {{ contact.owner }}</span>
      </footer>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import ContactAvatar from '../ui/ContactAvatar.vue'
import { locale, t, translateLabel } from '../../i18n'
import {
  formatAddress,
  formatPartialDate,
  getAge,
  getMapUrl,
  getMessengerUrl,
  getPhoneHref,
  getUpcoming,
  getUrlHref,
} from '../../helpers/contact'
import { copyText } from '../../helpers/clipboard'
import { formatMonth, sortWorkHistory } from '../../helpers/workHistory'
import { useToast } from '../../composables/useToast'
import type { Contact, ContactValue, ContactWork, RelationOption } from '../../types/contact'

const props = defineProps<{
  contact: Contact
  photoSrc: string | null
  vCardUrl: string
  showOwner: boolean
  relationOptions: RelationOption[]
}>()

const emit = defineEmits<{
  (event: 'edit'): void
  (event: 'delete'): void
  (event: 'toggle-favorite'): void
  (event: 'back'): void
  (event: 'open-contact', id: number): void
}>()

const { show } = useToast()

const findPrimary = (values: ContactValue[]): string | null => {
  const primary = values.find((value) => {
    return value.primary
  })

  return (primary ?? values[0])?.value ?? null
}

const primaryPhone = computed(() => {
  return findPrimary(props.contact.phones)
})

const primaryEmail = computed(() => {
  return findPrimary(props.contact.emails)
})

const hasDates = computed(() => {
  return Boolean(props.contact.birthday) || props.contact.dates.length > 0
})

const profileRows = computed(() => {
  const rows: { label: string; value: string }[] = []
  const contact = props.contact

  if (contact.gender) {
    rows.push({ label: t('field.gender'), value: t(`gender.${contact.gender}`) })
  }

  if (contact.pronouns) {
    rows.push({ label: t('field.pronouns'), value: contact.pronouns })
  }

  if (contact.language) {
    rows.push({ label: t('field.language'), value: contact.language })
  }

  if (contact.timezone) {
    rows.push({ label: t('field.timezone'), value: contact.timezone })
  }

  return rows
})

function findRelated(item: ContactValue): RelationOption | null {
  if (item.relatedContactId) {
    const linked = props.relationOptions.find((option: RelationOption) => {
      return option.id === item.relatedContactId
    })

    return linked ?? null
  }

  const name = item.value.trim().toLowerCase()
  if (name === '') {
    return null
  }

  const found = props.relationOptions.find((option: RelationOption) => {
    return option.name.toLowerCase() === name
  })

  return found ?? null
}

function openRelated(item: ContactValue): void {
  const related = findRelated(item)
  if (related !== null) {
    emit('open-contact', related.id)
  }
}

const workHistory = computed(() => {
  return sortWorkHistory(props.contact.workHistory)
})

const WORK_COLLAPSE_THRESHOLD = 2
const isWorkOpen = ref(false)

watch(
  () => {
    return props.contact.id
  },
  () => {
    isWorkOpen.value = workHistory.value.length <= WORK_COLLAPSE_THRESHOLD
  },
  { immediate: true },
)

function toggleWork(): void {
  isWorkOpen.value = !isWorkOpen.value
}

function formatWorkPeriod(item: ContactWork): string {
  const start = formatMonth(item.startDate, locale.value)
  const end = item.endDate ? formatMonth(item.endDate, locale.value) : t('detail.present')
  if (!item.startDate && !item.endDate) {
    return ''
  }

  return start === '' ? end : `${start} – ${end}`
}

const workLine = computed(() => {
  const company = [props.contact.company, props.contact.department].filter(Boolean).join(', ')

  return [props.contact.jobTitle, company].filter(Boolean).join(' · ')
})

const birthdayInfo = computed(() => {
  const parts: string[] = []
  const age = getAge(props.contact.birthday)
  if (age !== null) {
    parts.push(t('birthday.age', { age }))
  }

  const info = dateInfo(props.contact.birthday)
  if (info) {
    parts.push(info)
  }

  return parts.join(' · ')
})

function dateInfo(value: string | null): string {
  const upcoming = getUpcoming(value)
  if (!upcoming) {
    return ''
  }

  let when = t('birthday.inDays', { days: upcoming.days })
  if (upcoming.days === 0) {
    when = t('birthday.today')
  }

  if (upcoming.days === 1) {
    when = t('birthday.tomorrow')
  }

  const showAge = upcoming.age !== null && upcoming.days <= 60
  if (showAge) {
    const turns = t('birthday.turns', { age: upcoming.age ?? 0 })

    return `${when}, ${turns}`
  }

  return when
}

function formatDateTime(value: string): string {
  return new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}

async function copy(value: string): Promise<void> {
  const copied = await copyText(value)
  if (copied) {
    show(t('detail.copied'), 'success')

    return
  }

  show(t('error.copy'), 'error')
}
</script>
