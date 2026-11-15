<template>
  <span class="cb-avatar" :class="`cb-avatar--${size}`" :style="{ background: photoSrc ? undefined : color }">
    <img v-if="photoSrc && !failed" :src="photoSrc" :alt="name" @error="failed = true" />
    <span v-else>{{ initials }}</span>
  </span>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { getAvatarColor, getInitials } from '../../helpers/contact'

const props = withDefaults(
  defineProps<{ name: string; photoSrc?: string | null; size?: 'sm' | 'md' | 'lg' | 'xl' }>(),
  {
    photoSrc: null,
    size: 'md',
  },
)

const failed = ref(false)

const initials = computed(() => {
  return getInitials(props.name)
})

const color = computed(() => {
  return getAvatarColor(props.name)
})

watch(
  () => {
    return props.photoSrc
  },
  () => {
    failed.value = false
  },
)
</script>
