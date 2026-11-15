<template>
  <div class="cb-modal cb-viewer" role="dialog" aria-modal="true" @click.self="emit('close')" @wheel.prevent="onWheel">
    <div class="cb-viewer__toolbar">
      <button
        type="button"
        class="cb-viewer__button"
        :title="t('detail.photoZoomOut')"
        :disabled="zoom <= MIN_ZOOM"
        @click="zoomOut"
      >
        <AppIcon name="minus" :size="18" />
      </button>
      <span class="cb-viewer__zoom">{{ zoomLabel }}</span>
      <button
        type="button"
        class="cb-viewer__button"
        :title="t('detail.photoZoomIn')"
        :disabled="zoom >= MAX_ZOOM"
        @click="zoomIn"
      >
        <AppIcon name="plus" :size="18" />
      </button>
      <button type="button" class="cb-viewer__button" :title="t('detail.photoClose')" @click="emit('close')">
        <AppIcon name="close" :size="18" />
      </button>
    </div>
    <div class="cb-viewer__stage" @click.self="emit('close')">
      <img
        class="cb-viewer__image"
        :src="src"
        :alt="name"
        :style="imageStyle"
        draggable="false"
        @dblclick="toggleZoom"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import { t } from '../../i18n'

const MIN_ZOOM = 1
const MAX_ZOOM = 4
const ZOOM_STEP = 0.5

defineProps<{ src: string; name: string }>()

const emit = defineEmits<{ (event: 'close'): void }>()

const zoom = ref(MIN_ZOOM)

const zoomLabel = computed(() => {
  const percent = Math.round(zoom.value * 100)

  return `${percent}%`
})

const imageStyle = computed(() => {
  return { '--cb-zoom': zoom.value.toString() }
})

function clampZoom(value: number): number {
  return Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, value))
}

function zoomIn(): void {
  zoom.value = clampZoom(zoom.value + ZOOM_STEP)
}

function zoomOut(): void {
  zoom.value = clampZoom(zoom.value - ZOOM_STEP)
}

function toggleZoom(): void {
  zoom.value = zoom.value > MIN_ZOOM ? MIN_ZOOM : 2
}

function onWheel(event: Event): void {
  const wheelEvent = event as WheelEvent

  if (wheelEvent.deltaY < 0) {
    zoomIn()

    return
  }

  zoomOut()
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') {
    emit('close')
  }
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
})
</script>
