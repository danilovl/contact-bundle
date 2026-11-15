/// <reference path="./shims-vue.d.ts" />
import { createApp } from 'vue'
import App from './App.vue'
import { getStoredLocale, setLocale } from './i18n'
import './styles/contact.css'

function mountContactApp(element: HTMLElement): void {
  const apiPrefix = element.dataset.apiPrefix ?? '/danilovl/contact/api'
  const title = element.dataset.title ?? 'Contacts'
  const locale = getStoredLocale() ?? element.dataset.locale ?? navigator.language

  setLocale(locale)
  createApp(App, { apiPrefix, title }).mount(element)
}

document.addEventListener('DOMContentLoaded', () => {
  const elements = document.querySelectorAll<HTMLElement>('[data-contact-app]')
  elements.forEach((element) => {
    mountContactApp(element)
  })
})
