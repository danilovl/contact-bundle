<template>
  <Transition name="cb-fade">
    <div v-if="state" class="cb-modal" role="dialog" aria-modal="true" @click.self="answer(false)">
      <div class="cb-modal__box">
        <p class="cb-modal__message">{{ state?.message }}</p>
        <div class="cb-modal__actions">
          <button type="button" class="cb-button" @click="answer(false)">
            {{ state?.cancelText ?? t('confirm.cancel') }}
          </button>
          <button type="button" class="cb-button cb-button--danger" @click="answer(true)">
            {{ state?.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { t } from '../../i18n'
import { useConfirm } from '../../composables/useConfirm'

const { state, answer } = useConfirm()
</script>
