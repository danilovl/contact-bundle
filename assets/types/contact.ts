export type ValueKind = 'emails' | 'phones' | 'urls' | 'messengers' | 'relations'

export interface ContactValue {
  label: string | null
  value: string
  primary: boolean
  relatedContactId?: number | null
}

export interface ContactCustomField {
  key: string
  value: string
}

export type ContactGender = '' | 'M' | 'F' | 'O' | 'N' | 'U'

export interface ContactAddress {
  label: string | null
  street: string | null
  extended: string | null
  city: string | null
  region: string | null
  postalCode: string | null
  country: string | null
}

export interface ContactDate {
  label: string | null
  date: string
}

export interface ContactWork {
  company: string | null
  jobTitle: string | null
  startDate: string | null
  endDate: string | null
}

export interface AppConfig {
  owner: string
  isAdmin: boolean
  accessMode: string
  photoMaxSize: number
}

export interface RelationOption {
  id: number
  name: string
  subtitle: string
  photoSrc: string | null
}

export interface ContactSummary {
  id: number
  owner: string
  displayName: string
  sortName: string
  firstName: string | null
  lastName: string | null
  nickname: string | null
  company: string | null
  jobTitle: string | null
  birthday: string | null
  favorite: boolean
  tags: string[]
  emails: string[]
  phones: string[]
  hasPhoto: boolean
  photoVersion: number
  createdAt: string
  updatedAt: string
}

export type SortField = 'first' | 'last' | 'company' | 'jobTitle' | 'birthday' | 'created' | 'updated'

export type SortDirection = 'asc' | 'desc'

export interface Contact extends Omit<ContactSummary, 'emails' | 'phones'> {
  prefix: string | null
  middleName: string | null
  suffix: string | null
  department: string | null
  notes: string | null
  photo: string | null
  gender: string | null
  pronouns: string | null
  language: string | null
  timezone: string | null
  customFields: ContactCustomField[]
  emails: ContactValue[]
  phones: ContactValue[]
  urls: ContactValue[]
  messengers: ContactValue[]
  relations: ContactValue[]
  addresses: ContactAddress[]
  dates: ContactDate[]
  workHistory: ContactWork[]
}

export interface ValueRow {
  key: number
  label: string
  value: string
  primary: boolean
  relatedContactId: number | null
}

export interface CustomFieldRow {
  key: number
  name: string
  value: string
}

export interface AddressRow {
  key: number
  label: string
  street: string
  extended: string
  city: string
  region: string
  postalCode: string
  country: string
}

export interface DateRow {
  key: number
  label: string
  date: string
}

export interface WorkRow {
  key: number
  company: string
  jobTitle: string
  startDate: string
  endDate: string
  current: boolean
}

export interface ContactForm {
  prefix: string
  firstName: string
  middleName: string
  lastName: string
  suffix: string
  nickname: string
  company: string
  department: string
  jobTitle: string
  birthday: string
  photo: string
  notes: string
  gender: string
  pronouns: string
  language: string
  timezone: string
  customFields: CustomFieldRow[]
  favorite: boolean
  tags: string[]
  emails: ValueRow[]
  phones: ValueRow[]
  urls: ValueRow[]
  messengers: ValueRow[]
  relations: ValueRow[]
  addresses: AddressRow[]
  dates: DateRow[]
  workHistory: WorkRow[]
}

export type ContactFilter =
  { type: 'all' } | { type: 'favorites' } | { type: 'birthdays' } | { type: 'tag'; tag: string }

export interface ImportResult {
  created: number
  updated: number
  skipped: number
  invalid: number
}

export type FieldErrors = Record<string, string>
