<template>
  <svg
    class="cb-icon"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path v-for="(path, index) in paths" :key="index" :d="path" :fill="filled ? 'currentColor' : 'none'" />
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const ICONS: Record<string, string[]> = {
  plus: ['M12 5v14', 'M5 12h14'],
  minus: ['M5 12h14'],
  search: ['M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z', 'M21 21l-4.35-4.35'],
  star: ['M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'],
  edit: ['M12 20h9', 'M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z'],
  trash: ['M3 6h18', 'M8 6V4h8v2', 'M19 6l-1 14H6L5 6'],
  download: ['M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4', 'M7 10l5 5 5-5', 'M12 15V3'],
  upload: ['M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4', 'M17 8l-5-5-5 5', 'M12 3v12'],
  phone: [
    'M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z',
  ],
  mail: ['M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z', 'M22 6l-10 7L2 6'],
  link: [
    'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71',
    'M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71',
  ],
  map: ['M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z', 'M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6z'],
  chat: ['M21 11.5a8.4 8.4 0 0 1-9 8.4 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5z'],
  users: [
    'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2',
    'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
    'M23 21v-2a4 4 0 0 0-3-3.87',
    'M16 3.13a4 4 0 0 1 0 7.75',
  ],
  gift: [
    'M20 12v10H4V12',
    'M2 7h20v5H2z',
    'M12 22V7',
    'M12 7H7.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7z',
    'M12 7h4.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7z',
  ],
  tag: ['M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z', 'M7 7h.01'],
  menu: ['M3 12h18', 'M3 6h18', 'M3 18h18'],
  back: ['M19 12H5', 'M12 19l-7-7 7-7'],
  close: ['M18 6L6 18', 'M6 6l12 12'],
  copy: ['M9 9h11v11H9z', 'M5 15H4V4h11v1'],
  calendar: ['M3 4h18v18H3z', 'M16 2v4', 'M8 2v4', 'M3 10h18'],
  briefcase: ['M2 7h20v14H2z', 'M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16'],
  note: ['M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z', 'M14 2v6h6', 'M8 13h8', 'M8 17h8'],
  user: ['M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2', 'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
  camera: [
    'M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z',
    'M12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
  ],
  crop: ['M6 2v14a2 2 0 0 0 2 2h14', 'M18 22V8a2 2 0 0 0-2-2H2'],
  refresh: ['M23 4v6h-6', 'M1 20v-6h6', 'M3.51 9a9 9 0 0 1 14.85-3.36L23 10', 'M1 14l4.64 4.36A9 9 0 0 0 20.49 15'],
  check: ['M20 6L9 17l-5-5'],
  chevron: ['M6 9l6 6 6-6'],
  globe: [
    'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z',
    'M2 12h20',
    'M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z',
  ],
}

const props = withDefaults(defineProps<{ name: string; size?: number; filled?: boolean }>(), {
  size: 18,
  filled: false,
})

const paths = computed(() => {
  return ICONS[props.name] ?? []
})
</script>
