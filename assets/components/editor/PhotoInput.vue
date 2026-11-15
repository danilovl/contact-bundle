<template>
  <div class="cb-photo">
    <button type="button" class="cb-photo__button" :title="t('editor.photoUpload')" @click="openFile">
      <ContactAvatar :name="name || '?'" :photo-src="modelValue || null" size="xl" />
      <span class="cb-photo__overlay">
        <AppIcon name="camera" :size="22" />
      </span>
    </button>
    <input ref="fileInput" type="file" accept="image/*" hidden @change="onFileChange" />
    <button v-if="modelValue" type="button" class="cb-link-button" @click="emit('update:modelValue', '')">
      {{ t('editor.photoRemove') }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import ContactAvatar from '../ui/ContactAvatar.vue'
import { t } from '../../i18n'
import { useToast } from '../../composables/useToast'

const PHOTO_SIZE = 256

defineProps<{ modelValue: string; name: string }>()

const emit = defineEmits<{ (event: 'update:modelValue', value: string): void }>()

const fileInput = ref<HTMLInputElement | null>(null)
const { show } = useToast()

function openFile(): void {
  fileInput.value?.click()
}

function loadImage(file: File): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file)
    const image = new Image()
    image.onload = () => {
      URL.revokeObjectURL(url)
      resolve(image)
    }
    image.onerror = () => {
      URL.revokeObjectURL(url)
      reject(new Error('Invalid image'))
    }
    image.src = url
  })
}

async function onFileChange(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''

  if (!file) {
    return
  }

  try {
    const image = await loadImage(file)
    const side = Math.min(image.width, image.height)
    const sourceX = (image.width - side) / 2
    const sourceY = (image.height - side) / 2

    const canvas = document.createElement('canvas')
    canvas.width = PHOTO_SIZE
    canvas.height = PHOTO_SIZE
    const context = canvas.getContext('2d')
    if (!context) {
      throw new Error('Canvas is not supported')
    }

    context.drawImage(image, sourceX, sourceY, side, side, 0, 0, PHOTO_SIZE, PHOTO_SIZE)
    emit('update:modelValue', canvas.toDataURL('image/jpeg', 0.85))
  } catch {
    show(t('editor.photoError'), 'error')
  }
}
</script>
