<template>
  <div class="cb-toasts" aria-live="polite">
    <TransitionGroup name="cb-toast">
      <div v-for="toast in toasts" :key="toast.id" class="cb-toast" :class="`cb-toast--${toast.type}`">
        <span>{{ toast.message }}</span>
        <button type="button" class="cb-icon-button" @click="dismiss(toast.id)">
          <AppIcon name="close" :size="14" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import AppIcon from './AppIcon.vue'
import { useToast } from '../../composables/useToast'

const { toasts, dismiss } = useToast()
</script>
