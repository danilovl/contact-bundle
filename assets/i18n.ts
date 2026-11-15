import { computed, ref } from 'vue'
import { ar } from './locales/ar'
import { cs } from './locales/cs'
import { de } from './locales/de'
import { en } from './locales/en'
import { es } from './locales/es'
import { fr } from './locales/fr'
import { hi } from './locales/hi'
import { id } from './locales/id'
import { it } from './locales/it'
import { ja } from './locales/ja'
import { ko } from './locales/ko'
import { pt } from './locales/pt'
import { ru } from './locales/ru'
import { tr } from './locales/tr'
import { zh } from './locales/zh'

type Dict = Record<string, string>

const dictionaries: Record<string, Dict> = { en, zh, hi, es, fr, ar, pt, ru, de, ja, ko, tr, it, id, cs }

const RTL_LOCALES = ['ar']

export const SUPPORTED_LOCALES = Object.keys(dictionaries)

export const locale = ref('en')

export const isRtl = computed(() => {
  return RTL_LOCALES.includes(locale.value)
})

export const LOCALE_NAMES: Record<string, string> = {
  en: 'English',
  zh: '中文',
  hi: 'हिन्दी',
  es: 'Español',
  fr: 'Français',
  ar: 'العربية',
  pt: 'Português',
  ru: 'Русский',
  de: 'Deutsch',
  ja: '日本語',
  ko: '한국어',
  tr: 'Türkçe',
  it: 'Italiano',
  id: 'Bahasa Indonesia',
  cs: 'Čeština',
}

const STORAGE_KEY = 'danilovl_contact_locale'

export function setLocale(value: string): void {
  const shortValue = value.toLowerCase().slice(0, 2)
  locale.value = SUPPORTED_LOCALES.includes(shortValue) ? shortValue : 'en'
}

export function getStoredLocale(): string | null {
  try {
    return window.localStorage.getItem(STORAGE_KEY)
  } catch {
    return null
  }
}

export function changeLocale(value: string): void {
  setLocale(value)

  try {
    window.localStorage.setItem(STORAGE_KEY, locale.value)
  } catch {
    return
  }
}

export function t(key: string, params: Record<string, string | number> = {}): string {
  const dictionary = dictionaries[locale.value] ?? en
  const template = dictionary[key] ?? en[key] ?? key

  return template.replace(/\{(\w+)\}/g, (match: string, name: string) => {
    return params[name] !== undefined ? String(params[name]) : match
  })
}

export function translateLabel(label: string | null): string {
  if (label === null || label === '') {
    return ''
  }

  const key = `label.${label.toLowerCase()}`
  const translated = t(key)

  return translated === key ? label : translated
}
