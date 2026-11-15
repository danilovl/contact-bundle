import { computed, ref, watch } from 'vue'
import type { ContactApi } from '../api/contactApi'
import type { Contact, ContactFilter, ContactSummary, SortDirection, SortField } from '../types/contact'
import {
  compareContacts,
  getDefaultDirection,
  getGroupLetter,
  getSearchText,
  getSortKey,
  getUpcoming,
  isTextSort,
  normalizeSearch,
} from '../helpers/contact'

export const BIRTHDAY_WINDOW_DAYS = 30

export interface ContactGroup {
  letter: string
  items: ContactSummary[]
}

export interface TagCount {
  tag: string
  count: number
}

export function toSummary(contact: Contact): ContactSummary {
  return {
    id: contact.id,
    owner: contact.owner,
    displayName: contact.displayName,
    sortName: contact.sortName,
    firstName: contact.firstName,
    lastName: contact.lastName,
    nickname: contact.nickname,
    company: contact.company,
    jobTitle: contact.jobTitle,
    birthday: contact.birthday,
    favorite: contact.favorite,
    tags: contact.tags,
    emails: contact.emails.map((email) => {
      return email.value
    }),
    phones: contact.phones.map((phone) => {
      return phone.value
    }),
    hasPhoto: contact.hasPhoto,
    photoVersion: contact.photoVersion,
    createdAt: contact.createdAt,
    updatedAt: contact.updatedAt,
  }
}

export function useContacts(api: ContactApi) {
  const items = ref<ContactSummary[]>([])
  const loading = ref(false)
  const search = ref('')
  const filter = ref<ContactFilter>({ type: 'all' })
  const sortBy = ref<SortField>('first')
  const sortDir = ref<SortDirection>('asc')

  watch(sortBy, (field: SortField) => {
    sortDir.value = getDefaultDirection(field)
  })

  const searchIndex = computed(() => {
    const index = new Map<number, string>()
    for (const item of items.value) {
      index.set(item.id, getSearchText(item))
    }

    return index
  })

  const isUpcomingBirthday = (item: ContactSummary): boolean => {
    const upcoming = getUpcoming(item.birthday)

    return upcoming !== null && upcoming.days <= BIRTHDAY_WINDOW_DAYS
  }

  const tags = computed<TagCount[]>(() => {
    const counts = new Map<string, number>()
    for (const item of items.value) {
      for (const tag of item.tags) {
        counts.set(tag, (counts.get(tag) ?? 0) + 1)
      }
    }

    return [...counts.entries()]
      .map(([tag, count]) => {
        return { tag, count }
      })
      .sort((a, b) => {
        return a.tag.localeCompare(b.tag)
      })
  })

  const counts = computed(() => {
    return {
      all: items.value.length,
      favorites: items.value.filter((item) => {
        return item.favorite
      }).length,
      birthdays: items.value.filter(isUpcomingBirthday).length,
    }
  })

  const matchesFilter = (item: ContactSummary): boolean => {
    const current = filter.value

    switch (current.type) {
      case 'favorites':
        return item.favorite
      case 'birthdays':
        return isUpcomingBirthday(item)
      case 'tag':
        return item.tags.includes(current.tag)
      default:
        return true
    }
  }

  const filtered = computed<ContactSummary[]>(() => {
    const words = normalizeSearch(search.value).split(/\s+/).filter(Boolean)

    return items.value.filter((item) => {
      if (!matchesFilter(item)) {
        return false
      }

      const text = searchIndex.value.get(item.id) ?? ''

      return words.every((word) => {
        return text.includes(word)
      })
    })
  })

  const groups = computed<ContactGroup[]>(() => {
    if (filter.value.type === 'birthdays') {
      const sorted = [...filtered.value].sort((a, b) => {
        return (getUpcoming(a.birthday)?.days ?? 0) - (getUpcoming(b.birthday)?.days ?? 0)
      })

      return sorted.length > 0 ? [{ letter: '', items: sorted }] : []
    }

    const sorted = [...filtered.value].sort((a: ContactSummary, b: ContactSummary) => {
      return compareContacts(a, b, sortBy.value, sortDir.value)
    })

    if (!isTextSort(sortBy.value)) {
      return sorted.length > 0 ? [{ letter: '', items: sorted }] : []
    }

    const result: ContactGroup[] = []
    for (const item of sorted) {
      const letter = getGroupLetter(getSortKey(item, sortBy.value))
      const last = result[result.length - 1]

      if (last && last.letter === letter) {
        last.items.push(item)
      } else {
        result.push({ letter, items: [item] })
      }
    }

    return result
  })

  async function load(): Promise<void> {
    loading.value = true
    try {
      items.value = await api.list()
    } finally {
      loading.value = false
    }
  }

  function upsert(summary: ContactSummary): void {
    const index = items.value.findIndex((item) => {
      return item.id === summary.id
    })

    if (index === -1) {
      items.value = [...items.value, summary]

      return
    }

    const copy = [...items.value]
    copy[index] = summary
    items.value = copy
  }

  function removeLocal(id: number): void {
    items.value = items.value.filter((item) => {
      return item.id !== id
    })
  }

  return {
    items,
    loading,
    search,
    filter,
    sortBy,
    sortDir,
    tags,
    counts,
    filtered,
    groups,
    load,
    upsert,
    removeLocal,
  }
}
