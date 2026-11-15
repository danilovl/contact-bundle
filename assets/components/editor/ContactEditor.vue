<template>
  <form class="cb-detail cb-editor" novalidate @submit.prevent="onSubmit">
    <div class="cb-detail__bar">
      <h1 class="cb-detail__title">{{ isNew ? t('editor.create') : t('editor.edit') }}</h1>
      <div class="cb-detail__actions">
        <button type="button" class="cb-button cb-button--ghost" @click="onCancel">{{ t('editor.cancel') }}</button>
        <button type="submit" class="cb-button cb-button--primary" :disabled="saving">
          <AppIcon name="check" />
          {{ saving ? t('editor.saving') : t('editor.save') }}
        </button>
      </div>
    </div>

    <div class="cb-detail__scroll">
      <div class="cb-detail__grid">
        <section class="cb-card cb-card--wide">
          <h2 class="cb-card__title"><AppIcon name="user" :size="16" />{{ t('section.name') }}</h2>
          <div class="cb-name-editor">
            <PhotoInput v-model="form.photo" :name="previewName" />
            <div class="cb-form-grid">
              <label v-if="showMoreNames" class="cb-field">
                <span class="cb-field__label">{{ t('field.prefix') }}</span>
                <input v-model="form.prefix" class="cb-input" maxlength="32" />
              </label>
              <label class="cb-field">
                <span class="cb-field__label">{{ t('field.firstName') }}</span>
                <input
                  ref="firstInput"
                  v-model="form.firstName"
                  class="cb-input"
                  :class="{ 'has-error': allErrors.firstName }"
                  maxlength="128"
                  autocomplete="off"
                />
              </label>
              <label v-if="showMoreNames" class="cb-field">
                <span class="cb-field__label">{{ t('field.middleName') }}</span>
                <input v-model="form.middleName" class="cb-input" maxlength="128" autocomplete="off" />
              </label>
              <label class="cb-field">
                <span class="cb-field__label">{{ t('field.lastName') }}</span>
                <input v-model="form.lastName" class="cb-input" maxlength="128" autocomplete="off" />
              </label>
              <label v-if="showMoreNames" class="cb-field">
                <span class="cb-field__label">{{ t('field.suffix') }}</span>
                <input v-model="form.suffix" class="cb-input" maxlength="32" />
              </label>
              <label class="cb-field">
                <span class="cb-field__label">{{ t('field.nickname') }}</span>
                <input v-model="form.nickname" class="cb-input" maxlength="128" autocomplete="off" />
              </label>
              <span v-if="allErrors.firstName" class="cb-field__error cb-form-grid__full">{{
                allErrors.firstName
              }}</span>
              <div class="cb-form-grid__full cb-inline">
                <button v-if="!showMoreNames" type="button" class="cb-link-button" @click="showMoreNames = true">
                  <AppIcon name="plus" :size="15" />
                  {{ t('editor.moreNames') }}
                </button>
                <label class="cb-toggle">
                  <input v-model="form.favorite" type="checkbox" />
                  <AppIcon name="star" :size="15" :filled="form.favorite" />
                  {{ t('field.favorite') }}
                </label>
              </div>
              <span v-if="allErrors.photo" class="cb-field__error cb-form-grid__full">{{ allErrors.photo }}</span>
            </div>
          </div>
        </section>

        <section class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="briefcase" :size="16" />{{ t('section.work') }}</h2>
          <div class="cb-form-grid cb-form-grid--single">
            <label class="cb-field">
              <span class="cb-field__label">{{ t('field.company') }}</span>
              <input v-model="form.company" class="cb-input" maxlength="255" />
            </label>
            <label class="cb-field">
              <span class="cb-field__label">{{ t('field.department') }}</span>
              <input v-model="form.department" class="cb-input" maxlength="255" />
            </label>
            <label class="cb-field">
              <span class="cb-field__label">{{ t('field.jobTitle') }}</span>
              <input v-model="form.jobTitle" class="cb-input" maxlength="255" />
            </label>
          </div>
        </section>

        <ProfileEditor
          v-model:gender="form.gender"
          v-model:pronouns="form.pronouns"
          v-model:language="form.language"
          v-model:timezone="form.timezone"
          :errors="allErrors"
        />

        <section class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="tag" :size="16" />{{ t('section.tags') }}</h2>
          <TagInput v-model="form.tags" :suggestions="tagSuggestions" />
        </section>

        <ValueListEditor
          kind="phones"
          :rows="form.phones"
          :title="t('section.phones')"
          icon="phone"
          :errors="allErrors"
        />

        <ValueListEditor
          kind="emails"
          :rows="form.emails"
          :title="t('section.emails')"
          icon="mail"
          :errors="allErrors"
        />

        <ValueListEditor
          kind="messengers"
          :rows="form.messengers"
          :title="t('section.messengers')"
          icon="chat"
          :errors="allErrors"
        />

        <ValueListEditor kind="urls" :rows="form.urls" :title="t('section.urls')" icon="link" :errors="allErrors" />

        <AddressListEditor :rows="form.addresses" :errors="allErrors" />

        <DateListEditor v-model:birthday="form.birthday" :rows="form.dates" :errors="allErrors" />

        <ValueListEditor
          kind="relations"
          :rows="form.relations"
          :title="t('section.relations')"
          icon="users"
          :errors="allErrors"
          :contacts="relationOptions"
          @open-contact="emit('open-contact', $event)"
        />

        <section class="cb-card">
          <h2 class="cb-card__title"><AppIcon name="note" :size="16" />{{ t('section.notes') }}</h2>
          <textarea
            v-model="form.notes"
            class="cb-input cb-textarea"
            rows="5"
            :placeholder="t('field.notes')"
          ></textarea>
        </section>

        <CustomFieldsEditor :rows="form.customFields" :errors="allErrors" />

        <WorkHistoryEditor :rows="form.workHistory" :errors="allErrors" />
      </div>
    </div>

    <div class="cb-float-actions">
      <button type="button" class="cb-button cb-button--ghost" @click="onCancel">{{ t('editor.cancel') }}</button>
      <button type="submit" class="cb-button cb-button--primary" :disabled="saving">
        <AppIcon name="check" />
        {{ saving ? t('editor.saving') : t('editor.save') }}
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, toRaw, watch } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import AddressListEditor from './AddressListEditor.vue'
import CustomFieldsEditor from './CustomFieldsEditor.vue'
import DateListEditor from './DateListEditor.vue'
import PhotoInput from './PhotoInput.vue'
import ProfileEditor from './ProfileEditor.vue'
import TagInput from './TagInput.vue'
import ValueListEditor from './ValueListEditor.vue'
import WorkHistoryEditor from './WorkHistoryEditor.vue'
import { t } from '../../i18n'
import { formToPayload } from '../../helpers/contact'
import { validateForm } from '../../helpers/validation'
import { useToast } from '../../composables/useToast'
import type { ContactForm, FieldErrors, RelationOption } from '../../types/contact'

const props = defineProps<{
  initialForm: ContactForm
  isNew: boolean
  saving: boolean
  savedVersion: number
  errors: FieldErrors
  tagSuggestions: string[]
  relationOptions: RelationOption[]
}>()

const emit = defineEmits<{
  (event: 'save', form: ContactForm): void
  (event: 'cancel', dirty: boolean): void
  (event: 'open-contact', id: number): void
}>()

const form = reactive<ContactForm>(structuredClone(toRaw(props.initialForm)))
let snapshot = JSON.stringify(formToPayload(props.initialForm))
let pendingSnapshot = snapshot
const firstInput = ref<HTMLInputElement | null>(null)

const showMoreNames = ref(Boolean(form.prefix || form.middleName || form.suffix))

const { show } = useToast()
const serverErrors = ref<FieldErrors>({ ...props.errors })

const submitted = ref(false)

const clientErrors = computed(() => {
  return validateForm(form, submitted.value)
})

const allErrors = computed<FieldErrors>(() => {
  return { ...serverErrors.value, ...clientErrors.value }
})

watch(
  () => {
    return props.savedVersion
  },
  () => {
    snapshot = pendingSnapshot
  },
)

watch(
  () => {
    return props.errors
  },
  (value) => {
    serverErrors.value = { ...value }
  },
)

watch(
  () => {
    return JSON.stringify(formToPayload(form))
  },
  () => {
    serverErrors.value = {}
  },
)

const previewName = computed(() => {
  return [form.firstName, form.lastName].filter(Boolean).join(' ') || form.nickname || form.company
})

const isDirty = (): boolean => {
  return JSON.stringify(formToPayload(form)) !== snapshot
}

async function scrollToFirstError(): Promise<void> {
  await nextTick()
  const target = document.querySelector('.cb-editor .has-error, .cb-editor .cb-field__error')
  target?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

function onSubmit(): void {
  submitted.value = true

  if (Object.keys(clientErrors.value).length > 0) {
    show(t('editor.invalid'), 'error')
    void scrollToFirstError()

    return
  }

  pendingSnapshot = JSON.stringify(formToPayload(form))
  emit('save', form)
}

function onCancel(): void {
  emit('cancel', isDirty())
}

onMounted(async () => {
  await nextTick()
  if (props.isNew) {
    firstInput.value?.focus()
  }
})

defineExpose({ isDirty })
</script>
