import { ref } from 'vue'

interface ConfirmState {
  message: string
  confirmText: string
  resolve: (value: boolean) => void
}

const state = ref<ConfirmState | null>(null)

export function useConfirm() {
  function confirm(message: string, confirmText: string): Promise<boolean> {
    return new Promise((resolve) => {
      state.value = { message, confirmText, resolve }
    })
  }

  function answer(value: boolean): void {
    state.value?.resolve(value)
    state.value = null
  }

  return { state, confirm, answer }
}
