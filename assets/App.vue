<template>
  <div
    class="cb-app"
    :dir="isRtl ? 'rtl' : 'ltr'"
    :class="{ 'is-detail-open': mode !== 'empty', 'is-menu-open': menuOpen }"
  >
    <div class="cb-backdrop cb-only-mobile" @click="menuOpen = false"></div>

    <ContactSidebar
      :title="title"
      :open="menuOpen"
      :filter="filter"
      :counts="counts"
      :tags="tags"
      :importing="importing"
      :export-url="api.exportUrl()"
      @select-filter="selectFilter"
      @create="startCreate"
      @import="importFile"
      @close="menuOpen = false"
    />

    <ContactList
      v-model:search="search"
      v-model:sort-by="sortBy"
      v-model:sort-dir="sortDir"
      :groups="groups"
      :loading="loading"
      :selected-id="selectedId"
      :filter-type="filter.type"
      :total="filtered.length"
      :photo-url="summaryPhotoUrl"
      @select="selectContact"
      @toggle-favorite="toggleFavorite"
      @open-menu="menuOpen = true"
    />

    <main class="cb-main">
      <div v-if="detailLoading" class="cb-state">{{ t('list.loading') }}</div>

      <ContactEditor
        v-else-if="(mode === 'edit' || mode === 'create') && editorForm"
        :key="editorKey"
        ref="editorRef"
        :initial-form="editorForm"
        :is-new="mode === 'create'"
        :saving="saving"
        :saved-version="savedVersion"
        :errors="errors"
        :tag-suggestions="tagNames"
        :relation-options="relationOptions"
        :photo-max-size="photoMaxSize"
        @save="save"
        @cancel="cancelEdit"
        @open-contact="selectContact"
      />

      <ContactDetail
        v-else-if="mode === 'view' && current"
        :contact="current"
        :photo-src="current.hasPhoto ? api.photoUrl(current.id, current.photoVersion) : null"
        :v-card-url="api.vCardUrl(current.id)"
        :show-owner="showOwner"
        :relation-options="relationOptions"
        @open-contact="selectContact"
        @edit="startEdit"
        @delete="removeCurrent"
        @toggle-favorite="toggleCurrentFavorite"
        @back="closeDetail"
      />

      <div v-else class="cb-state cb-state--large">
        <AppIcon name="user" :size="56" />
        <strong>{{ t('detail.empty') }}</strong>
        <span class="cb-muted">{{ t('detail.emptyHint') }}</span>
        <button type="button" class="cb-button cb-button--primary" @click="startCreate">
          <AppIcon name="plus" />
          {{ t('app.newContact') }}
        </button>
      </div>
    </main>

    <AppToast />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import AppIcon from './components/ui/AppIcon.vue'
import AppToast from './components/ui/AppToast.vue'
import ConfirmDialog from './components/ui/ConfirmDialog.vue'
import ContactSidebar from './components/sidebar/ContactSidebar.vue'
import ContactList from './components/list/ContactList.vue'
import ContactDetail from './components/detail/ContactDetail.vue'
import ContactEditor from './components/editor/ContactEditor.vue'
import { ApiError, createContactApi } from './api/contactApi'
import { isRtl, locale, t } from './i18n'
import { contactToForm, createEmptyForm, formToPayload } from './helpers/contact'
import { PHOTO_DEFAULT_MAX_BYTES } from './helpers/photo'
import { toSummary, useContacts } from './composables/useContacts'
import { useToast } from './composables/useToast'
import { useConfirm } from './composables/useConfirm'
import type {
  AppConfig,
  Contact,
  ContactFilter,
  ContactForm,
  ContactSummary,
  FieldErrors,
  RelationOption,
} from './types/contact'

type Mode = 'empty' | 'view' | 'edit' | 'create'

const props = defineProps<{ apiPrefix: string; title: string }>()

const api = createContactApi(props.apiPrefix)
const { items, search, filter, sortBy, sortDir, tags, counts, filtered, groups, loading, load, upsert, removeLocal } =
  useContacts(api)
const { show } = useToast()
const { confirm } = useConfirm()

const mode = ref<Mode>('empty')
const selectedId = ref<number | null>(null)
const current = ref<Contact | null>(null)
const editorForm = ref<ContactForm | null>(null)
const editorKey = ref(0)
const errors = ref<FieldErrors>({})
const saving = ref(false)
const importing = ref(false)
const detailLoading = ref(false)
const menuOpen = ref(false)
const appConfig = ref<AppConfig | null>(null)

const photoMaxSize = computed(() => {
  const config = appConfig.value
  if (config === null || !config.photoMaxSize) {
    return PHOTO_DEFAULT_MAX_BYTES
  }

  return config.photoMaxSize
})

const showOwner = computed(() => {
  const config = appConfig.value

  return config !== null && (config.isAdmin || config.accessMode === 'shared')
})

const relationOptions = computed<RelationOption[]>(() => {
  const others = items.value.filter((item: ContactSummary) => {
    return item.id !== selectedId.value
  })

  const options = others.map((item: ContactSummary) => {
    const subtitleParts = [item.jobTitle, item.company].filter((part: string | null) => {
      return part !== null && part !== ''
    })
    const photoSrc = item.hasPhoto ? summaryPhotoUrl(item) : null

    return { id: item.id, name: item.displayName, subtitle: subtitleParts.join(' · '), photoSrc }
  })

  return options.sort((first: RelationOption, second: RelationOption) => {
    return first.name.localeCompare(second.name, locale.value)
  })
})

const tagNames = computed(() => {
  return tags.value.map((item) => {
    return item.tag
  })
})

function summaryPhotoUrl(item: ContactSummary): string {
  return api.photoUrl(item.id, item.photoVersion)
}

function setHash(value: string): void {
  const hash = value === '' ? '' : `#/${value}`
  if (window.location.hash !== hash) {
    history.pushState(null, '', hash === '' ? window.location.pathname + window.location.search : hash)
  }
}

const editorRef = ref<{ isDirty: () => boolean } | null>(null)

async function confirmDiscard(): Promise<boolean> {
  if (mode.value !== 'edit' && mode.value !== 'create') {
    return true
  }

  const editor = editorRef.value
  if (editor !== null && !editor.isDirty()) {
    return true
  }

  return confirm(t('editor.discard'), t('confirm.ok'))
}

function showError(error: unknown): void {
  const message = error instanceof ApiError ? error.message : t('error.generic')
  show(message, 'error')
}

async function openContact(id: number): Promise<void> {
  selectedId.value = id
  mode.value = 'view'
  detailLoading.value = true

  try {
    current.value = await api.get(id)
  } catch (error) {
    showError(error)
    mode.value = 'empty'
    selectedId.value = null
  } finally {
    detailLoading.value = false
  }
}

async function selectContact(id: number): Promise<void> {
  if (id === selectedId.value && mode.value === 'view') {
    return
  }

  const canLeave = await confirmDiscard()
  if (!canLeave) {
    return
  }

  setHash(`contact/${id}`)
  await openContact(id)
}

function openEditor(form: ContactForm, nextMode: Mode): void {
  errors.value = {}
  editorForm.value = form
  editorKey.value++
  mode.value = nextMode
}

async function startCreate(): Promise<void> {
  const canLeave = await confirmDiscard()
  if (!canLeave) {
    return
  }

  const form = createEmptyForm()
  const activeFilter = filter.value
  if (activeFilter.type === 'tag') {
    form.tags = [activeFilter.tag]
  }

  form.favorite = activeFilter.type === 'favorites'
  menuOpen.value = false
  selectedId.value = null
  current.value = null
  setHash('new')
  openEditor(form, 'create')
}

function startEdit(): void {
  if (!current.value) {
    return
  }

  setHash(`contact/${current.value.id}/edit`)
  openEditor(contactToForm(current.value), 'edit')
}

async function cancelEdit(dirty: boolean): Promise<void> {
  if (dirty) {
    const discard = await confirm(t('editor.discard'), t('confirm.ok'))
    if (!discard) {
      return
    }
  }

  errors.value = {}
  if (current.value) {
    mode.value = 'view'
    setHash(`contact/${current.value.id}`)

    return
  }

  closeDetail()
}

const savedVersion = ref(0)

async function save(form: ContactForm): Promise<void> {
  saving.value = true
  errors.value = {}
  const payload = formToPayload(form)

  try {
    const saved =
      current.value && mode.value === 'edit' ? await api.update(current.value.id, payload) : await api.create(payload)
    current.value = saved
    selectedId.value = saved.id
    upsert(toSummary(saved))
    mode.value = 'edit'
    setHash(`contact/${saved.id}/edit`)
    savedVersion.value++
    show(t('editor.saved'), 'success')
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      errors.value = error.errors
      show(t('editor.invalid'), 'error')

      return
    }

    showError(error)
  } finally {
    saving.value = false
  }
}

async function removeCurrent(): Promise<void> {
  const contact = current.value
  if (!contact) {
    return
  }

  const approved = await confirm(t('detail.deleteConfirm', { name: contact.displayName }), t('confirm.ok'))
  if (!approved) {
    return
  }

  try {
    await api.remove(contact.id)
    removeLocal(contact.id)
    closeDetail()
    show(t('detail.deleted'), 'success')
  } catch (error) {
    showError(error)
  }
}

async function toggleFavorite(item: ContactSummary): Promise<void> {
  try {
    const summary = await api.setFavorite(item.id, !item.favorite)
    upsert(summary)
    if (current.value && current.value.id === summary.id) {
      current.value = { ...current.value, favorite: summary.favorite, photoVersion: summary.photoVersion }
    }
  } catch (error) {
    showError(error)
  }
}

async function toggleCurrentFavorite(): Promise<void> {
  if (current.value) {
    await toggleFavorite(toSummary(current.value))
  }
}

function closeDetail(): void {
  mode.value = 'empty'
  selectedId.value = null
  current.value = null
  setHash('')
}

function selectFilter(value: ContactFilter): void {
  filter.value = value
  menuOpen.value = false
}

async function importFile(file: File): Promise<void> {
  importing.value = true

  try {
    const result = await api.importFile(file)
    await load()
    show(t('io.imported', { ...result }), 'success')
  } catch (error) {
    showError(error)
  } finally {
    importing.value = false
    menuOpen.value = false
  }
}

async function applyHash(): Promise<void> {
  const hash = window.location.hash.replace(/^#\/?/, '')
  const match = /^contact\/(\d+)(\/edit)?$/.exec(hash)

  if (hash === 'new') {
    if (mode.value !== 'create') {
      openEditor(createEmptyForm(), 'create')
    }

    return
  }

  if (!match) {
    mode.value = 'empty'
    selectedId.value = null
    current.value = null

    return
  }

  const id = Number(match[1])
  if (current.value?.id !== id) {
    await openContact(id)
  }

  if (match[2] && current.value) {
    openEditor(contactToForm(current.value), 'edit')
  } else if (current.value) {
    mode.value = 'view'
  }
}

function onPopState(): void {
  void applyHash()
}

onMounted(async () => {
  window.addEventListener('popstate', onPopState)

  try {
    appConfig.value = await api.config()
  } catch {
    appConfig.value = null
  }

  try {
    await load()
  } catch (error) {
    showError(error)
  }

  await applyHash()
})

onBeforeUnmount(() => {
  window.removeEventListener('popstate', onPopState)
})
</script>
