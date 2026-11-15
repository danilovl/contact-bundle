<template>
  <div ref="root" class="cb-relation">
    <div class="cb-relation__box">
      <input
        :value="modelValue"
        class="cb-input"
        :class="{ 'has-error': error, 'is-linked': linkedOption }"
        type="text"
        :placeholder="t('field.relationPick')"
        maxlength="255"
        autocomplete="off"
        @input="onInput"
      />
      <button
        type="button"
        class="cb-relation__pick"
        :class="{ 'is-open': isOpen }"
        :aria-expanded="isOpen"
        @click="toggleMenu"
      >
        <AppIcon name="users" :size="15" />
        <span>{{ t('relation.pick') }}</span>
        <AppIcon name="chevron" :size="14" class="cb-relation__chevron" />
      </button>
    </div>

    <button
      v-if="linkedOption"
      type="button"
      class="cb-relation__linked"
      :title="t('relation.open')"
      @click="openLinked(linkedOption)"
    >
      <AppIcon name="check" :size="13" />
      {{ t('relation.linked') }} · {{ t('relation.open') }}
    </button>

    <div v-if="isOpen" class="cb-relation__menu" @keydown.esc="closeMenu">
      <div class="cb-relation__search">
        <AppIcon name="search" :size="15" />
        <input
          ref="searchInput"
          v-model="query"
          class="cb-relation__search-input"
          type="text"
          :placeholder="t('relation.search')"
          autocomplete="off"
        />
      </div>
      <div class="cb-relation__list">
        <button
          v-for="option in visibleOptions"
          :key="option.id"
          type="button"
          class="cb-relation__option"
          @click="select(option)"
        >
          <ContactAvatar :name="option.name" :photo-src="option.photoSrc" size="sm" />
          <span class="cb-relation__option-main">
            <span class="cb-relation__option-name">{{ option.name }}</span>
            <span v-if="option.subtitle" class="cb-relation__option-sub">{{ option.subtitle }}</span>
          </span>
        </button>
        <div v-if="visibleOptions.length === 0" class="cb-relation__empty">
          {{ contacts.length === 0 ? t('relation.noContacts') : t('relation.noMatch') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import ContactAvatar from '../ui/ContactAvatar.vue'
import { t } from '../../i18n'
import type { RelationOption } from '../../types/contact'

const MAX_OPTIONS = 50

const props = defineProps<{
  modelValue: string
  relatedContactId: number | null
  contacts: RelationOption[]
  error?: string
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
  (event: 'update:relatedContactId', value: number | null): void
  (event: 'open-contact', id: number): void
}>()

const root = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)
const isOpen = ref(false)
const query = ref('')

function findByName(name: string): RelationOption | null {
  const value = name.trim().toLowerCase()
  if (value === '') {
    return null
  }

  const found = props.contacts.find((option: RelationOption) => {
    return option.name.toLowerCase() === value
  })

  return found ?? null
}

const linkedOption = computed(() => {
  if (props.relatedContactId !== null) {
    const linked = props.contacts.find((option: RelationOption) => {
      return option.id === props.relatedContactId
    })

    return linked ?? null
  }

  return findByName(props.modelValue)
})

const visibleOptions = computed(() => {
  const search = query.value.trim().toLowerCase()

  const matched = props.contacts.filter((option: RelationOption) => {
    const haystack = `${option.name} ${option.subtitle}`.toLowerCase()

    return search === '' || haystack.includes(search)
  })

  return matched.slice(0, MAX_OPTIONS)
})

function openMenu(): void {
  query.value = ''
  isOpen.value = true
  nextTick(() => {
    searchInput.value?.focus()
  })
}

function closeMenu(): void {
  isOpen.value = false
}

function toggleMenu(): void {
  if (isOpen.value) {
    closeMenu()

    return
  }

  openMenu()
}

function onInput(event: Event): void {
  const value = (event.target as HTMLInputElement).value
  const matched = findByName(value)

  emit('update:modelValue', value)
  emit('update:relatedContactId', matched === null ? null : matched.id)
}

function openLinked(option: RelationOption): void {
  emit('open-contact', option.id)
}

function select(option: RelationOption): void {
  emit('update:modelValue', option.name)
  emit('update:relatedContactId', option.id)
  closeMenu()
}

function onDocumentClick(event: MouseEvent): void {
  const element = root.value
  if (element !== null && !element.contains(event.target as Node)) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('mousedown', onDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentClick)
})
</script>
