import type { AppConfig, Contact, ContactSummary, ImportResult, FieldErrors } from '../types/contact'

export class ApiError extends Error {
  status: number
  errors: FieldErrors

  constructor(message: string, status: number, errors: FieldErrors) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

interface RequestOptions {
  method?: string
  body?: unknown
  formData?: FormData
}

export interface ContactApi {
  config(): Promise<AppConfig>
  list(): Promise<ContactSummary[]>
  get(id: number): Promise<Contact>
  create(payload: unknown): Promise<Contact>
  update(id: number, payload: unknown): Promise<Contact>
  remove(id: number): Promise<void>
  setFavorite(id: number, favorite: boolean): Promise<ContactSummary>
  importFile(file: File): Promise<ImportResult>
  photoUrl(id: number, version: number): string
  vCardUrl(id: number): string
  exportUrl(): string
}

export function createContactApi(apiPrefix: string): ContactApi {
  const base = apiPrefix.replace(/\/+$/, '')

  async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
    const headers: Record<string, string> = { Accept: 'application/json' }
    let body: BodyInit | undefined

    if (options.formData) {
      body = options.formData
    } else if (options.body !== undefined) {
      headers['Content-Type'] = 'application/json'
      body = JSON.stringify(options.body)
    }

    const response = await fetch(base + path, {
      method: options.method ?? 'GET',
      headers,
      body,
      credentials: 'same-origin',
    })

    if (response.status === 204) {
      return undefined as T
    }

    const text = await response.text()
    const data = text === '' ? null : JSON.parse(text)

    if (!response.ok) {
      const message = data?.error ?? `HTTP ${response.status}`
      const errors = data?.errors ?? {}

      throw new ApiError(message, response.status, errors)
    }

    return data as T
  }

  return {
    config() {
      return request<AppConfig>('/config')
    },
    async list() {
      const data = await request<{ items: ContactSummary[] }>('/contacts')

      return data.items
    },
    get(id) {
      return request<Contact>(`/contacts/${id}`)
    },
    create(payload) {
      return request<Contact>('/contacts', { method: 'POST', body: payload })
    },
    update(id, payload) {
      return request<Contact>(`/contacts/${id}`, { method: 'PUT', body: payload })
    },
    async remove(id) {
      await request<void>(`/contacts/${id}`, { method: 'DELETE' })
    },
    setFavorite(id, favorite) {
      return request<ContactSummary>(`/contacts/${id}/favorite`, { method: 'PATCH', body: { favorite } })
    },
    importFile(file) {
      const formData = new FormData()
      formData.append('file', file)

      return request<ImportResult>('/import', { method: 'POST', formData })
    },
    photoUrl(id, version) {
      return `${base}/contacts/${id}/photo?v=${version}`
    },
    vCardUrl(id) {
      return `${base}/contacts/${id}/vcard`
    },
    exportUrl() {
      return `${base}/export`
    },
  }
}
