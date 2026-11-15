<template>
  <div class="cb-modal" role="dialog" aria-modal="true" @click.self="emit('cancel')">
    <div class="cb-modal__box cb-cropper">
      <h3 class="cb-cropper__title">{{ t('editor.photoCropTitle') }}</h3>
      <div
        class="cb-cropper__stage"
        :class="{ 'is-dragging': dragging }"
        :style="stageStyle"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="onPointerUp"
        @pointercancel="onPointerUp"
        @wheel="onWheel"
      >
        <img
          ref="imageRef"
          class="cb-cropper__image"
          :src="src"
          :style="imageStyle"
          draggable="false"
          alt=""
          @load="onImageLoad"
          @error="emit('error')"
        />
        <span class="cb-cropper__mask"></span>
      </div>
      <label class="cb-cropper__zoom">
        <span>{{ t('editor.photoZoom') }}</span>
        <input type="range" :min="MIN_ZOOM" :max="MAX_ZOOM" step="0.01" :value="zoom" @input="onZoomInput" />
      </label>
      <p class="cb-muted cb-cropper__hint">{{ t('editor.photoCropHint') }}</p>
      <div class="cb-modal__actions">
        <button type="button" class="cb-button" @click="emit('cancel')">{{ t('editor.cancel') }}</button>
        <button type="button" class="cb-button cb-button--primary" @click="apply">{{ t('editor.photoApply') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { t } from '../../i18n'
import { renderCrop } from '../../helpers/photo'

const STAGE_SIZE = 300
const MIN_ZOOM = 1
const MAX_ZOOM = 4

const props = defineProps<{ src: string; maxBytes: number }>()

const emit = defineEmits<{
  (event: 'apply', value: string): void
  (event: 'cancel'): void
  (event: 'error'): void
}>()

const imageRef = ref<HTMLImageElement | null>(null)
const naturalWidth = ref(0)
const naturalHeight = ref(0)
const zoom = ref(MIN_ZOOM)
const offsetX = ref(0)
const offsetY = ref(0)
const dragging = ref(false)

let dragStartX = 0
let dragStartY = 0

const stageStyle = computed(() => {
  return { width: `${STAGE_SIZE}px`, height: `${STAGE_SIZE}px` }
})

const scale = computed(() => {
  const shortSide = Math.min(naturalWidth.value, naturalHeight.value)
  if (shortSide === 0) {
    return 1
  }

  return (STAGE_SIZE / shortSide) * zoom.value
})

const imageStyle = computed(() => {
  const width = naturalWidth.value * scale.value
  const height = naturalHeight.value * scale.value

  return {
    width: `${width}px`,
    height: `${height}px`,
    transform: `translate(${offsetX.value}px, ${offsetY.value}px)`,
  }
})

function clampValue(value: number, min: number, max: number): number {
  return Math.min(max, Math.max(min, value))
}

function clampOffsets(): void {
  const displayWidth = naturalWidth.value * scale.value
  const displayHeight = naturalHeight.value * scale.value

  offsetX.value = clampValue(offsetX.value, STAGE_SIZE - displayWidth, 0)
  offsetY.value = clampValue(offsetY.value, STAGE_SIZE - displayHeight, 0)
}

function onImageLoad(): void {
  const image = imageRef.value
  if (!image) {
    return
  }

  naturalWidth.value = image.naturalWidth
  naturalHeight.value = image.naturalHeight
  zoom.value = MIN_ZOOM

  const displayWidth = naturalWidth.value * scale.value
  const displayHeight = naturalHeight.value * scale.value
  offsetX.value = (STAGE_SIZE - displayWidth) / 2
  offsetY.value = (STAGE_SIZE - displayHeight) / 2
}

function setZoom(next: number): void {
  const half = STAGE_SIZE / 2
  const oldScale = scale.value
  const centerX = (half - offsetX.value) / oldScale
  const centerY = (half - offsetY.value) / oldScale

  zoom.value = clampValue(next, MIN_ZOOM, MAX_ZOOM)

  const newScale = scale.value
  offsetX.value = half - centerX * newScale
  offsetY.value = half - centerY * newScale
  clampOffsets()
}

function onZoomInput(event: Event): void {
  const input = event.target as HTMLInputElement
  setZoom(Number(input.value))
}

function onWheel(event: WheelEvent): void {
  event.preventDefault()

  const step = event.deltaY < 0 ? 0.1 : -0.1
  setZoom(zoom.value + step)
}

function onPointerDown(event: PointerEvent): void {
  const target = event.currentTarget as HTMLElement
  target.setPointerCapture(event.pointerId)
  dragging.value = true
  dragStartX = event.clientX - offsetX.value
  dragStartY = event.clientY - offsetY.value
}

function onPointerMove(event: PointerEvent): void {
  if (!dragging.value) {
    return
  }

  offsetX.value = event.clientX - dragStartX
  offsetY.value = event.clientY - dragStartY
  clampOffsets()
}

function onPointerUp(): void {
  dragging.value = false
}

function apply(): void {
  const image = imageRef.value
  if (!image || naturalWidth.value === 0) {
    return
  }

  const currentScale = scale.value
  const side = STAGE_SIZE / currentScale
  const maxX = Math.max(0, naturalWidth.value - side)
  const maxY = Math.max(0, naturalHeight.value - side)
  const sourceX = clampValue(-offsetX.value / currentScale, 0, maxX)
  const sourceY = clampValue(-offsetY.value / currentScale, 0, maxY)
  const dataUrl = renderCrop(image, { sourceX, sourceY, side }, props.maxBytes)

  emit('apply', dataUrl)
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape') {
    emit('cancel')
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
})
</script>
