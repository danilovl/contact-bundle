import { t } from '../i18n'
import { getUrlHref, parsePartialDate } from './contact'
import type { ContactForm, FieldErrors, ValueKind } from '../types/contact'

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/
const PHONE_CHARS = /^[0-9+()\-.\s/*#pwx,;]+$/i
const TELEGRAM_HANDLE = /^@?[A-Za-z][A-Za-z0-9_]{4,31}$/
const MESSENGER_NUMBER_SERVICES = ['whatsapp', 'signal', 'viber']

function countDigits(value: string): number {
  return value.replace(/\D/g, '').length
}

export function validateEmail(value: string): string {
  return EMAIL_PATTERN.test(value.trim()) ? '' : t('validation.email')
}

export function validatePhone(value: string): string {
  const trimmed = value.trim()
  if (!PHONE_CHARS.test(trimmed)) {
    return t('validation.phone')
  }

  const digits = countDigits(trimmed)
  const hasCode = trimmed.startsWith('+')

  if (hasCode) {
    return digits >= 7 && digits <= 15 ? '' : t('validation.phone')
  }

  return digits >= 3 && digits <= 20 ? '' : t('validation.phoneLocal')
}

export function validateUrl(value: string): string {
  const trimmed = value.trim()
  if (/\s/.test(trimmed)) {
    return t('validation.url')
  }

  try {
    const url = new URL(getUrlHref(trimmed))
    const isWeb = url.protocol === 'http:' || url.protocol === 'https:'
    if (!isWeb) {
      return ''
    }

    const isHost = url.hostname.includes('.') || url.hostname === 'localhost'

    return isHost ? '' : t('validation.url')
  } catch {
    return t('validation.url')
  }
}

export function validateMessenger(label: string, value: string): string {
  const service = label.toLowerCase()
  const trimmed = value.trim()

  if (MESSENGER_NUMBER_SERVICES.includes(service)) {
    const digits = countDigits(trimmed)

    return PHONE_CHARS.test(trimmed) && digits >= 7 && digits <= 15 ? '' : t('validation.messenger')
  }

  if (service === 'telegram') {
    const isNumber = PHONE_CHARS.test(trimmed) && countDigits(trimmed) >= 7
    const isHandle = TELEGRAM_HANDLE.test(trimmed)

    return isNumber || isHandle ? '' : t('validation.telegram')
  }

  return ''
}

export function validateValue(kind: ValueKind, label: string, value: string): string {
  if (value.trim() === '') {
    return ''
  }

  switch (kind) {
    case 'emails':
      return validateEmail(value)
    case 'phones':
      return validatePhone(value)
    case 'urls':
      return validateUrl(value)
    case 'messengers':
      return validateMessenger(label, value)
    default:
      return ''
  }
}

export function validateBirthday(value: string): string {
  const parts = parsePartialDate(value)
  if (!parts || parts.year === null) {
    return ''
  }

  const date = new Date(Date.UTC(parts.year, parts.month - 1, parts.day))

  return date.getTime() > Date.now() ? t('validation.dateFuture') : ''
}

export function validateForm(form: ContactForm, strict = false): FieldErrors {
  const errors: FieldErrors = {}
  const kinds: ValueKind[] = ['emails', 'phones', 'urls', 'messengers', 'relations']

  for (const kind of kinds) {
    form[kind].forEach((row, index: number) => {
      const isEmpty = row.value.trim() === ''
      if (strict && isEmpty) {
        errors[`${kind}.${index}.value`] = t('validation.required')

        return
      }

      const message = validateValue(kind, row.label, row.value)
      if (message !== '') {
        errors[`${kind}.${index}.value`] = message
      }
    })
  }

  const birthdayMessage = validateBirthday(form.birthday)
  if (birthdayMessage !== '') {
    errors.birthday = birthdayMessage
  }

  form.dates.forEach((row, index: number) => {
    if (strict && row.date === '') {
      errors[`dates.${index}.date`] = t('validation.required')

      return
    }

    const message = validateBirthday(row.date)
    if (message !== '') {
      errors[`dates.${index}.date`] = message
    }
  })

  form.addresses.forEach((row, index: number) => {
    const parts = [row.street, row.extended, row.city, row.region, row.postalCode, row.country]
    const isEmpty = parts.every((part: string) => {
      return part.trim() === ''
    })

    if (strict && isEmpty) {
      errors[`addresses.${index}.street`] = t('validation.required')
    }
  })

  form.workHistory.forEach((row, index: number) => {
    const isEmpty = row.company.trim() === '' && row.jobTitle.trim() === ''
    if (strict && isEmpty) {
      errors[`workHistory.${index}.company`] = t('validation.required')
    }

    const isRangeInvalid = row.startDate !== '' && row.endDate !== '' && !row.current && row.endDate < row.startDate
    if (isRangeInvalid) {
      errors[`workHistory.${index}.endDate`] = t('validation.dateRange')
    }
  })

  return errors
}
