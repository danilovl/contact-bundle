import type { ContactWork, WorkRow } from '../types/contact'

const MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec']

export function parseCsv(text: string): string[][] {
  const rows: string[][] = []
  let row: string[] = []
  let field = ''
  let quoted = false

  for (let index = 0; index < text.length; index++) {
    const char = text.charAt(index)

    if (quoted) {
      if (char === '"' && text.charAt(index + 1) === '"') {
        field += '"'
        index++
      } else if (char === '"') {
        quoted = false
      } else {
        field += char
      }

      continue
    }

    if (char === '"') {
      quoted = true
    } else if (char === ',') {
      row.push(field)
      field = ''
    } else if (char === '\n' || char === '\r') {
      if (char === '\r' && text.charAt(index + 1) === '\n') {
        index++
      }

      row.push(field)
      rows.push(row)
      row = []
      field = ''
    } else {
      field += char
    }
  }

  if (field !== '' || row.length > 0) {
    row.push(field)
    rows.push(row)
  }

  return rows.filter((items: string[]) => {
    return items.some((item: string) => {
      return item.trim() !== ''
    })
  })
}

export function parseLinkedInMonth(value: string): string {
  const trimmed = value.trim()
  if (trimmed === '') {
    return ''
  }

  const withMonth = /^([A-Za-z]{3})[A-Za-z]*\s+(\d{4})$/.exec(trimmed)
  if (withMonth) {
    const month = MONTHS.indexOf(withMonth[1].toLowerCase())
    if (month >= 0) {
      return `${withMonth[2]}-${String(month + 1).padStart(2, '0')}`
    }
  }

  const yearOnly = /^(\d{4})$/.exec(trimmed)
  if (yearOnly) {
    return `${yearOnly[1]}-01`
  }

  const iso = /^(\d{4})-(\d{2})/.exec(trimmed)

  return iso ? `${iso[1]}-${iso[2]}` : ''
}

export interface ImportedPosition {
  company: string
  jobTitle: string
  startDate: string
  endDate: string
}

export function parseLinkedInPositions(text: string): ImportedPosition[] {
  const rows = parseCsv(text.replace(/^\uFEFF/, ''))
  if (rows.length < 2) {
    return []
  }

  const header = rows[0].map((item: string) => {
    return item.trim().toLowerCase()
  })
  const companyIndex = header.indexOf('company name')
  const titleIndex = header.indexOf('title')
  const startIndex = header.indexOf('started on')
  const endIndex = header.indexOf('finished on')

  if (companyIndex < 0 && titleIndex < 0) {
    return []
  }

  const cell = (items: string[], index: number): string => {
    return index >= 0 ? (items[index] ?? '').trim() : ''
  }

  return rows.slice(1).map((items: string[]) => {
    return {
      company: cell(items, companyIndex),
      jobTitle: cell(items, titleIndex),
      startDate: parseLinkedInMonth(cell(items, startIndex)),
      endDate: parseLinkedInMonth(cell(items, endIndex)),
    }
  })
}

export function formatMonth(value: string | null, locale: string): string {
  const match = value ? /^(\d{4})-(\d{2})$/.exec(value) : null
  if (!match) {
    return value ?? ''
  }

  const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, 1))

  return new Intl.DateTimeFormat(locale, { month: 'short', year: 'numeric', timeZone: 'UTC' }).format(date)
}

export function sortWorkHistory(items: ContactWork[]): ContactWork[] {
  return [...items].sort((first: ContactWork, second: ContactWork) => {
    const firstKey = first.endDate === null ? '9999-99' : first.endDate
    const secondKey = second.endDate === null ? '9999-99' : second.endDate
    if (firstKey !== secondKey) {
      return secondKey.localeCompare(firstKey)
    }

    return (second.startDate ?? '').localeCompare(first.startDate ?? '')
  })
}

export function isWorkRowEmpty(row: WorkRow): boolean {
  return row.company.trim() === '' && row.jobTitle.trim() === ''
}
