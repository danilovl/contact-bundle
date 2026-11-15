<template>
  <div class="cb-photo">
    <button type="button" class="cb-photo__button" :title="avatarTitle" @click="onAvatarClick">
      <ContactAvatar :name="name || '?'" :photo-src="modelValue || null" size="xl" />
      <span class="cb-photo__overlay">
        <AppIcon :name="modelValue ? 'edit' : 'camera'" :size="26" />
      </span>
    </button>
    <input ref="fileInput" type="file" accept="image/*" hidden @change="onFileChange" />
    <div v-if="modelValue" class="cb-photo__actions">
      <button
        type="button"
        class="cb-icon-button cb-photo__action"
        :title="t('editor.photoEdit')"
        :aria-label="t('editor.photoEdit')"
        @click="editCurrent"
      >
        <AppIcon name="crop" :size="18" />
      </button>
      <button
        type="button"
        class="cb-icon-button cb-photo__action"
        :title="t('editor.photoReplace')"
        :aria-label="t('editor.photoReplace')"
        @click="openFile"
      >
        <AppIcon name="refresh" :size="18" />
      </button>
      <button
        type="button"
        class="cb-icon-button cb-photo__action cb-photo__action--danger"
        :title="t('editor.photoRemove')"
        :aria-label="t('editor.photoRemove')"
        @click="removePhoto"
      >
        <AppIcon name="trash" :size="18" />
      </button>
    </div>
    <span class="cb-muted cb-photo__limit">{{ t('editor.photoLimit', { size: limitLabel }) }}</span>
    <PhotoCropper
      v-if="cropSrc"
      :src="cropSrc"
      :max-bytes="maxBytes"
      @apply="onApply"
      @cancel="closeCropper"
      @error="onCropError"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import ContactAvatar from '../ui/ContactAvatar.vue'
import PhotoCropper from './PhotoCropper.vue'
import { t } from '../../i18n'
import { useToast } from '../../composables/useToast'
import { useConfirm } from '../../composables/useConfirm'
import { PHOTO_DEFAULT_MAX_BYTES, formatMegabytes } from '../../helpers/photo'

const props = withDefaults(defineProps<{ modelValue: string; name: string; maxBytes?: number }>(), {
  maxBytes: PHOTO_DEFAULT_MAX_BYTES,
})

const emit = defineEmits<{ (event: 'update:modelValue', value: string): void }>()

const fileInput = ref<HTMLInputElement | null>(null)
const cropSrc = ref<string | null>(null)
const isCropObjectUrl = ref(false)
const { show } = useToast()
const { confirm } = useConfirm()

const avatarTitle = computed(() => {
  if (props.modelValue) {
    return t('editor.photoEdit')
  }

  return t('editor.photoUpload')
})

const limitLabel = computed(() => {
  return formatMegabytes(props.maxBytes)
})

function openFile(): void {
  fileInput.value?.click()
}

function releaseCropSource(): void {
  if (cropSrc.value && isCropObjectUrl.value) {
    URL.revokeObjectURL(cropSrc.value)
  }

  cropSrc.value = null
  isCropObjectUrl.value = false
}

function editCurrent(): void {
  if (!props.modelValue) {
    return
  }

  releaseCropSource()
  cropSrc.value = props.modelValue
}

async function removePhoto(): Promise<void> {
  const approved = await confirm(t('editor.photoRemoveConfirm'), t('confirm.yes'), t('confirm.no'))
  if (!approved) {
    return
  }

  emit('update:modelValue', '')
}

function onAvatarClick(): void {
  if (props.modelValue) {
    editCurrent()

    return
  }

  openFile()
}

function onFileChange(event: Event): void {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''

  if (!file) {
    return
  }

  if (!file.type.startsWith('image/')) {
    show(t('editor.photoError'), 'error')

    return
  }

  releaseCropSource()
  cropSrc.value = URL.createObjectURL(file)
  isCropObjectUrl.value = true
}

function onApply(dataUrl: string): void {
  emit('update:modelValue', dataUrl)
  releaseCropSource()
}

function closeCropper(): void {
  releaseCropSource()
}

function onCropError(): void {
  releaseCropSource()
  show(t('editor.photoError'), 'error')
}

onBeforeUnmount(() => {
  releaseCropSource()
})
</script>
