import type {
  AddressRow,
  Contact,
  ContactAddress,
  ContactCustomField,
  ContactForm,
  ContactSummary,
  ContactValue,
  CustomFieldRow,
  DateRow,
  ContactWork,
  SortDirection,
  SortField,
  ValueKind,
  ValueRow,
  WorkRow,
} from '../types/contact'

export const VALUE_KINDS: ValueKind[] = ['phones', 'emails', 'messengers', 'urls', 'relations']

export const LABEL_PRESETS: Record<string, string[]> = {
  phones: ['mobile', 'home', 'work', 'main', 'fax', 'pager', 'other'],
  emails: ['home', 'work', 'school', 'other'],
  messengers: ['telegram', 'whatsapp', 'signal', 'viber', 'skype', 'discord', 'slack', 'other'],
  urls: ['website', 'blog', 'linkedin', 'facebook', 'instagram', 'x', 'github', 'youtube', 'tiktok', 'other'],
  relations: [
    'spouse',
    'partner',
    'child',
    'parent',
    'mother',
    'father',
    'brother',
    'sister',
    'relative',
    'friend',
    'manager',
    'assistant',
    'other',
  ],
  addresses: ['home', 'work', 'other'],
  dates: ['anniversary', 'other'],
}

export const DEFAULT_LABELS: Record<string, string> = {
  phones: 'mobile',
  emails: 'home',
  messengers: 'telegram',
  urls: 'website',
  relations: 'friend',
  addresses: 'home',
  dates: 'anniversary',
}

const AVATAR_COLORS = [
  '#6366f1',
  '#0ea5e9',
  '#10b981',
  '#f59e0b',
  '#ef4444',
  '#8b5cf6',
  '#ec4899',
  '#14b8a6',
  '#f97316',
]

const MS_PER_DAY = 86_400_000

let rowKey = 0

export function nextRowKey(): number {
  rowKey++

  return rowKey
}

export function createValueRow(kind: string, value: Partial<ContactValue> = {}): ValueRow {
  return {
    key: nextRowKey(),
    label: value.label ?? DEFAULT_LABELS[kind] ?? '',
    value: value.value ?? '',
    primary: value.primary ?? false,
    relatedContactId: value.relatedContactId ?? null,
  }
}

export function createCustomFieldRow(field: Partial<ContactCustomField> = {}): CustomFieldRow {
  return {
    key: nextRowKey(),
    name: field.key ?? '',
    value: field.value ?? '',
  }
}

export function createAddressRow(address: Partial<ContactAddress> = {}): AddressRow {
  return {
    key: nextRowKey(),
    label: address.label ?? DEFAULT_LABELS.addresses,
    street: address.street ?? '',
    extended: address.extended ?? '',
    city: address.city ?? '',
    region: address.region ?? '',
    postalCode: address.postalCode ?? '',
    country: address.country ?? '',
  }
}

export function createDateRow(label: string | null = null, date = ''): DateRow {
  return {
    key: nextRowKey(),
    label: label ?? DEFAULT_LABELS.dates,
    date,
  }
}

export function createWorkRow(work: Partial<ContactWork> = {}): WorkRow {
  return {
    key: nextRowKey(),
    company: work.company ?? '',
    jobTitle: work.jobTitle ?? '',
    startDate: work.startDate ?? '',
    endDate: work.endDate ?? '',
    current: work.company !== undefined && (work.endDate ?? '') === '',
  }
}

export function createEmptyForm(): ContactForm {
  return {
    prefix: '',
    firstName: '',
    middleName: '',
    lastName: '',
    suffix: '',
    nickname: '',
    company: '',
    department: '',
    jobTitle: '',
    birthday: '',
    photo: '',
    notes: '',
    gender: '',
    pronouns: '',
    language: '',
    timezone: '',
    customFields: [],
    favorite: false,
    tags: [],
    emails: [createValueRow('emails')],
    phones: [createValueRow('phones')],
    urls: [],
    messengers: [],
    relations: [],
    addresses: [],
    dates: [],
    workHistory: [],
  }
}

export function contactToForm(contact: Contact): ContactForm {
  const mapValues = (kind: ValueKind): ValueRow[] => {
    return contact[kind].map((value: ContactValue) => {
      return createValueRow(kind, value)
    })
  }

  return {
    prefix: contact.prefix ?? '',
    firstName: contact.firstName ?? '',
    middleName: contact.middleName ?? '',
    lastName: contact.lastName ?? '',
    suffix: contact.suffix ?? '',
    nickname: contact.nickname ?? '',
    company: contact.company ?? '',
    department: contact.department ?? '',
    jobTitle: contact.jobTitle ?? '',
    birthday: contact.birthday ?? '',
    photo: contact.photo ?? '',
    notes: contact.notes ?? '',
    gender: contact.gender ?? '',
    pronouns: contact.pronouns ?? '',
    language: contact.language ?? '',
    timezone: contact.timezone ?? '',
    customFields: contact.customFields.map((field: ContactCustomField) => {
      return createCustomFieldRow(field)
    }),
    favorite: contact.favorite,
    tags: [...contact.tags],
    emails: mapValues('emails'),
    phones: mapValues('phones'),
    urls: mapValues('urls'),
    messengers: mapValues('messengers'),
    relations: mapValues('relations'),
    addresses: contact.addresses.map((address: ContactAddress) => {
      return createAddressRow(address)
    }),
    dates: contact.dates.map((date) => {
      return createDateRow(date.label, date.date)
    }),
    workHistory: contact.workHistory.map((work: ContactWork) => {
      return createWorkRow(work)
    }),
  }
}

export function formToPayload(form: ContactForm): Record<string, unknown> {
  const mapValues = (rows: ValueRow[]): ContactValue[] => {
    return rows.map((row) => {
      return {
        label: row.label || null,
        value: row.value,
        primary: row.primary,
        relatedContactId: row.relatedContactId,
      }
    })
  }

  return {
    prefix: form.prefix,
    firstName: form.firstName,
    middleName: form.middleName,
    lastName: form.lastName,
    suffix: form.suffix,
    nickname: form.nickname,
    company: form.company,
    department: form.department,
    jobTitle: form.jobTitle,
    birthday: form.birthday,
    photo: form.photo,
    notes: form.notes,
    gender: form.gender,
    pronouns: form.pronouns,
    language: form.language,
    timezone: form.timezone,
    customFields: form.customFields.map((row: CustomFieldRow) => {
      return { key: row.name, value: row.value }
    }),
    favorite: form.favorite,
    tags: form.tags,
    emails: mapValues(form.emails),
    phones: mapValues(form.phones),
    urls: mapValues(form.urls),
    messengers: mapValues(form.messengers),
    relations: mapValues(form.relations),
    addresses: form.addresses.map((row) => {
      const { key, ...address } = row
      void key

      return address
    }),
    dates: form.dates.map((row) => {
      return { label: row.label || null, date: row.date }
    }),
    workHistory: form.workHistory.map((row: WorkRow) => {
      return {
        company: row.company,
        jobTitle: row.jobTitle,
        startDate: row.startDate,
        endDate: row.current ? '' : row.endDate,
      }
    }),
  }
}

export function getInitials(name: string): string {
  const words = name
    .trim()
    .split(/\s+/)
    .filter((word) => {
      return word !== ''
    })

  if (words.length === 0) {
    return '?'
  }

  const first = words[0].charAt(0)
  const last = words.length > 1 ? words[words.length - 1].charAt(0) : ''

  return (first + last).toUpperCase()
}

export function getAvatarColor(seed: string): string {
  let hash = 0
  for (const char of seed) {
    hash = (hash * 31 + char.charCodeAt(0)) >>> 0
  }

  return AVATAR_COLORS[hash % AVATAR_COLORS.length]
}

export function isTextSort(field: SortField): boolean {
  return field === 'first' || field === 'last' || field === 'company' || field === 'jobTitle'
}

export function getDefaultDirection(field: SortField): SortDirection {
  if (field === 'created' || field === 'updated') {
    return 'desc'
  }

  return 'asc'
}

export function getSortKey(contact: ContactSummary, field: SortField): string {
  if (field === 'last' && contact.lastName) {
    return `${contact.lastName} ${contact.firstName ?? ''}`.toLocaleLowerCase()
  }

  if (field === 'company') {
    return (contact.company ?? '').toLocaleLowerCase()
  }

  if (field === 'jobTitle') {
    return (contact.jobTitle ?? '').toLocaleLowerCase()
  }

  return contact.sortName
}

function getSortNumber(contact: ContactSummary, field: SortField): number | null {
  if (field === 'birthday') {
    const upcoming = getUpcoming(contact.birthday)

    return upcoming === null ? null : upcoming.days
  }

  const value = field === 'created' ? contact.createdAt : contact.updatedAt
  const time = Date.parse(value)

  return Number.isNaN(time) ? null : time
}

export function compareContacts(
  first: ContactSummary,
  second: ContactSummary,
  field: SortField,
  direction: SortDirection,
): number {
  const sign = direction === 'asc' ? 1 : -1
  let result = 0

  if (isTextSort(field)) {
    const firstKey = getSortKey(first, field)
    const secondKey = getSortKey(second, field)

    if (firstKey === '' && secondKey !== '') {
      return 1
    }

    if (firstKey !== '' && secondKey === '') {
      return -1
    }

    result = sign * firstKey.localeCompare(secondKey)
  } else {
    const firstNumber = getSortNumber(first, field)
    const secondNumber = getSortNumber(second, field)

    if (firstNumber === null && secondNumber !== null) {
      return 1
    }

    if (firstNumber !== null && secondNumber === null) {
      return -1
    }

    if (firstNumber !== null && secondNumber !== null) {
      result = sign * (firstNumber - secondNumber)
    }
  }

  if (result !== 0) {
    return result
  }

  return first.sortName.localeCompare(second.sortName)
}

export function getGroupLetter(sortKey: string): string {
  const letter = sortKey.trim().charAt(0).toLocaleUpperCase()

  return /\p{L}/u.test(letter) ? letter : '#'
}

export function normalizeSearch(value: string): string {
  return value.toLocaleLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
}

export function getSearchText(contact: ContactSummary): string {
  const phones = contact.phones.map((phone) => {
    return phone.replace(/[^0-9+]/g, '')
  })
  const parts = [
    contact.displayName,
    contact.nickname,
    contact.company,
    contact.jobTitle,
    ...contact.emails,
    ...contact.phones,
    ...phones,
    ...contact.tags,
  ]

  return normalizeSearch(parts.filter(Boolean).join(' '))
}

export interface PartialDateParts {
  year: number | null
  month: number
  day: number
}

export function parsePartialDate(value: string | null): PartialDateParts | null {
  if (!value) {
    return null
  }

  const full = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
  if (full) {
    return { year: Number(full[1]), month: Number(full[2]), day: Number(full[3]) }
  }

  const yearless = /^--(\d{2})-(\d{2})$/.exec(value)
  if (yearless) {
    return { year: null, month: Number(yearless[1]), day: Number(yearless[2]) }
  }

  return null
}

export function buildPartialDate(year: string, month: string, day: string): string {
  if (!month || !day) {
    return ''
  }

  const paddedMonth = month.padStart(2, '0')
  const paddedDay = day.padStart(2, '0')

  return year ? `${year.padStart(4, '0')}-${paddedMonth}-${paddedDay}` : `--${paddedMonth}-${paddedDay}`
}

export function formatPartialDate(value: string | null, locale: string): string {
  const parts = parsePartialDate(value)
  if (!parts) {
    return value ?? ''
  }

  const date = new Date(Date.UTC(parts.year ?? 2000, parts.month - 1, parts.day))
  const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', timeZone: 'UTC' }
  if (parts.year !== null) {
    options.year = 'numeric'
  }

  return new Intl.DateTimeFormat(locale, options).format(date)
}

export interface UpcomingDate {
  days: number
  age: number | null
}

export function getUpcoming(value: string | null, today: Date = new Date()): UpcomingDate | null {
  const parts = parsePartialDate(value)
  if (!parts) {
    return null
  }

  const start = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate())
  let nextYear = today.getFullYear()
  let next = Date.UTC(nextYear, parts.month - 1, parts.day)

  if (next < start) {
    nextYear++
    next = Date.UTC(nextYear, parts.month - 1, parts.day)
  }

  const days = Math.round((next - start) / MS_PER_DAY)
  const age = parts.year === null ? null : nextYear - parts.year

  return { days, age }
}

export function getAge(value: string | null, today: Date = new Date()): number | null {
  const parts = parsePartialDate(value)
  if (!parts || parts.year === null) {
    return null
  }

  const hadBirthday =
    today.getMonth() + 1 > parts.month || (today.getMonth() + 1 === parts.month && today.getDate() >= parts.day)

  return today.getFullYear() - parts.year - (hadBirthday ? 0 : 1)
}

export function getMessengerUrl(label: string | null, value: string): string | null {
  const service = (label ?? '').toLowerCase()
  const handle = value.trim().replace(/^@/, '')
  const digits = value.replace(/[^0-9]/g, '')

  switch (service) {
    case 'telegram':
      return /^\+?[0-9\s-]+$/.test(value) ? `https://t.me/+${digits}` : `https://t.me/${handle}`
    case 'whatsapp':
      return `https://wa.me/${digits}`
    case 'signal':
      return `https://signal.me/#p/+${digits}`
    case 'viber':
      return `viber://chat?number=%2B${digits}`
    case 'skype':
      return `skype:${handle}?chat`
    default:
      return null
  }
}

export function getUrlHref(value: string): string {
  return /^[a-z][a-z0-9+.-]*:/i.test(value) ? value : `https://${value}`
}

export function getPhoneHref(value: string): string {
  return `tel:${value.replace(/[^0-9+*#,;]/g, '')}`
}

export function formatAddress(address: ContactAddress): string[] {
  const cityLine = [address.postalCode, address.city].filter(Boolean).join(' ')
  const lines = [address.street, address.extended, cityLine, address.region, address.country]

  return lines.filter((line): line is string => {
    return Boolean(line)
  })
}

export function getMapUrl(address: ContactAddress): string {
  const query = formatAddress(address).join(', ')

  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`
}
