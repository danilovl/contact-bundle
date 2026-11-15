import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'info'

export interface ToastMessage {
  id: number
  message: string
  type: ToastType
}

const toasts = ref<ToastMessage[]>([])
let toastId = 0

export function useToast() {
  function dismiss(id: number): void {
    toasts.value = toasts.value.filter((toast) => {
      return toast.id !== id
    })
  }

  function show(message: string, type: ToastType = 'info'): void {
    toastId++
    const id = toastId
    toasts.value = [...toasts.value, { id, message, type }]

    window.setTimeout(() => {
      dismiss(id)
    }, 4000)
  }

  return { toasts, show, dismiss }
}
