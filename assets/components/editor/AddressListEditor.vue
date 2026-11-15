<template>
  <section class="cb-card">
    <h2 class="cb-card__title">
      <AppIcon name="map" :size="16" />
      {{ t('section.addresses') }}
    </h2>

    <div v-for="row in rows" :key="row.key" class="cb-address">
      <div class="cb-edit-row">
        <LabelSelect v-model="row.label" :presets="presets" />
        <span class="cb-field--grow"></span>
        <button type="button" class="cb-icon-button" :title="t('editor.remove')" @click="remove(row)">
          <AppIcon name="close" :size="16" />
        </button>
      </div>

      <div class="cb-address-search">
        <input
          class="cb-input"
          type="search"
          autocomplete="off"
          :value="terms[row.key] ?? ''"
          :placeholder="t('address.search')"
          @input="onSearchInput(row, $event)"
        />
        <div v-if="status[row.key]" class="cb-address-results">
          <span v-if="status[row.key] === 'loading'" class="cb-muted cb-address-results__note">
            {{ t('address.searching') }}
          </span>
          <span v-else-if="status[row.key] === 'empty'" class="cb-muted cb-address-results__note">
            {{ t('address.noResults') }}
          </span>
          <span v-else-if="status[row.key] === 'failed'" class="cb-field__error cb-address-results__note">
            {{ t('address.failed') }}
          </span>
          <button
            v-for="place in results[row.key] ?? []"
            :key="place.label"
            type="button"
            class="cb-address-results__item"
            @click="pickPlace(row, place)"
          >
            <AppIcon name="map" :size="14" />
            <span>{{ place.label }}</span>
          </button>
        </div>
      </div>

      <div class="cb-address__grid">
        <input
          v-model="row.street"
          class="cb-input cb-address__wide"
          :class="{ 'has-error': errorFor(row) }"
          :placeholder="t('field.street')"
          maxlength="255"
        />
        <span v-if="errorFor(row)" class="cb-field__error cb-address__wide">{{ errorFor(row) }}</span>
        <input
          v-model="row.extended"
          class="cb-input cb-address__wide"
          :placeholder="t('field.extended')"
          maxlength="255"
        />
        <input v-model="row.postalCode" class="cb-input" :placeholder="t('field.postalCode')" maxlength="32" />
        <input v-model="row.city" class="cb-input" :placeholder="t('field.city')" maxlength="128" />
        <input v-model="row.region" class="cb-input" :placeholder="t('field.region')" maxlength="128" />
        <CountrySelect v-model="row.country" />
      </div>

      <div class="cb-address__map-actions">
        <button type="button" class="cb-link-button" @click="toggleMap(row)">
          <AppIcon name="map" :size="15" />
          {{ maps[row.key] ? t('address.hideMap') : t('address.showMap') }}
        </button>
        <span v-if="missing[row.key]" class="cb-field__error">{{ t('address.mapMissing') }}</span>
        <span v-if="locating[row.key]" class="cb-muted">{{ t('address.locating') }}</span>
        <span v-if="locateEmpty[row.key]" class="cb-field__error">{{ t('address.locateEmpty') }}</span>
      </div>
      <template v-if="maps[row.key]">
        <AddressMap :lat="maps[row.key].lat" :lon="maps[row.key].lon" @pick="onMapPick(row, $event)" />
        <p class="cb-map-hint">{{ t('address.mapHint') }}</p>
      </template>
    </div>

    <button type="button" class="cb-link-button" @click="add">
      <AppIcon name="plus" :size="15" />
      {{ t('editor.add') }}
    </button>
  </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, reactive } from 'vue'
import AppIcon from '../ui/AppIcon.vue'
import AddressMap from './AddressMap.vue'
import CountrySelect from './CountrySelect.vue'
import LabelSelect from './LabelSelect.vue'
import { locale, t } from '../../i18n'
import { LABEL_PRESETS, createAddressRow, formatAddress } from '../../helpers/contact'
import { reversePlace, searchPlaces } from '../../helpers/geocode'
import type { GeoPlace } from '../../helpers/geocode'
import type { AddressRow, FieldErrors } from '../../types/contact'

type SearchStatus = '' | 'loading' | 'ready' | 'empty' | 'failed'

interface MapPoint {
  lat: number | null
  lon: number | null
}

interface PickedPoint {
  lat: number
  lon: number
}

const SEARCH_DELAY = 500
const MIN_QUERY_LENGTH = 3

const props = defineProps<{ rows: AddressRow[]; errors: FieldErrors }>()

const presets = LABEL_PRESETS.addresses

const terms = reactive<Record<number, string>>({})
const results = reactive<Record<number, GeoPlace[]>>({})
const status = reactive<Record<number, SearchStatus>>({})
const maps = reactive<Record<number, MapPoint>>({})
const missing = reactive<Record<number, boolean>>({})
const locating = reactive<Record<number, boolean>>({})
const locateEmpty = reactive<Record<number, boolean>>({})
const timers = new Map<number, number>()

function add(): void {
  props.rows.push(createAddressRow())
}

function remove(row: AddressRow): void {
  const index = props.rows.indexOf(row)
  props.rows.splice(index, 1)
}

function errorFor(row: AddressRow): string | undefined {
  const index = props.rows.indexOf(row)

  return props.errors[`addresses.${index}.street`]
}

async function runSearch(row: AddressRow, query: string): Promise<void> {
  status[row.key] = 'loading'

  try {
    const places = await searchPlaces(query, locale.value)
    results[row.key] = places
    status[row.key] = places.length > 0 ? 'ready' : 'empty'
  } catch {
    results[row.key] = []
    status[row.key] = 'failed'
  }
}

function onSearchInput(row: AddressRow, event: Event): void {
  const value = (event.target as HTMLInputElement).value
  terms[row.key] = value

  const timer = timers.get(row.key)
  if (timer !== undefined) {
    window.clearTimeout(timer)
  }

  const query = value.trim()
  if (query.length < MIN_QUERY_LENGTH) {
    status[row.key] = ''
    results[row.key] = []

    return
  }

  const handle = window.setTimeout(() => {
    void runSearch(row, query)
  }, SEARCH_DELAY)
  timers.set(row.key, handle)
}

function pickPlace(row: AddressRow, place: GeoPlace): void {
  row.street = place.street
  row.city = place.city
  row.region = place.region
  row.postalCode = place.postalCode
  row.country = place.country
  terms[row.key] = ''
  results[row.key] = []
  status[row.key] = ''
  missing[row.key] = false
  locateEmpty[row.key] = false
  maps[row.key] = { lat: place.lat, lon: place.lon }
}

async function onMapPick(row: AddressRow, point: PickedPoint): Promise<void> {
  const { lat, lon } = point
  maps[row.key] = { lat, lon }
  locating[row.key] = true
  locateEmpty[row.key] = false
  missing[row.key] = false

  try {
    const place = await reversePlace(lat, lon, locale.value)
    if (place === null) {
      locateEmpty[row.key] = true

      return
    }

    row.street = place.street
    row.city = place.city
    row.region = place.region
    row.postalCode = place.postalCode
    row.country = place.country
  } catch {
    status[row.key] = 'failed'
  } finally {
    locating[row.key] = false
  }
}

async function toggleMap(row: AddressRow): Promise<void> {
  if (maps[row.key]) {
    delete maps[row.key]

    return
  }

  const query = formatAddress(row).join(', ')
  missing[row.key] = false
  locateEmpty[row.key] = false

  if (query === '') {
    maps[row.key] = { lat: null, lon: null }

    return
  }

  try {
    const places = await searchPlaces(query, locale.value)
    if (places.length === 0) {
      missing[row.key] = true
      maps[row.key] = { lat: null, lon: null }

      return
    }

    maps[row.key] = { lat: places[0].lat, lon: places[0].lon }
  } catch {
    status[row.key] = 'failed'
  }
}

onBeforeUnmount(() => {
  timers.forEach((handle: number) => {
    window.clearTimeout(handle)
  })
})
</script>
