import { ref } from 'vue'

interface ConfirmState {
  message: string
  confirmText: string
  cancelText: string | null
  resolve: (value: boolean) => void
}

const state = ref<ConfirmState | null>(null)

export function useConfirm() {
  function confirm(message: string, confirmText: string, cancelText: string | null = null): Promise<boolean> {
    return new Promise((resolve) => {
      state.value = { message, confirmText, cancelText, resolve }
    })
  }

  function answer(value: boolean): void {
    state.value?.resolve(value)
    state.value = null
  }

  return { state, confirm, answer }
}
