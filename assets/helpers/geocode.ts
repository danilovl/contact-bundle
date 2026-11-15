import { getEnglishCountryName } from './countries'

export interface GeoPlace {
  label: string
  lat: number
  lon: number
  street: string
  city: string
  region: string
  postalCode: string
  country: string
}

interface NominatimAddress {
  road?: string
  pedestrian?: string
  footway?: string
  house_number?: string
  postcode?: string
  city?: string
  town?: string
  village?: string
  municipality?: string
  hamlet?: string
  state?: string
  county?: string
  country?: string
  country_code?: string
}

interface NominatimItem {
  display_name: string
  lat: string
  lon: string
  address?: NominatimAddress
}

interface NominatimReverse extends NominatimItem {
  error?: string
}

const ENDPOINT = 'https://nominatim.openstreetmap.org/search'
const REVERSE_ENDPOINT = 'https://nominatim.openstreetmap.org/reverse'

function toPlace(item: NominatimItem): GeoPlace {
  const address = item.address ?? {}
  const road = address.road ?? address.pedestrian ?? address.footway ?? ''
  const street = [road, address.house_number ?? ''].filter(Boolean).join(' ')
  const city = address.city ?? address.town ?? address.village ?? address.municipality ?? address.hamlet ?? ''
  const countryCode = address.country_code ?? ''
  const country = countryCode === '' ? (address.country ?? '') : getEnglishCountryName(countryCode.toUpperCase())

  return {
    label: item.display_name,
    lat: Number(item.lat),
    lon: Number(item.lon),
    street,
    city,
    region: address.state ?? address.county ?? '',
    postalCode: address.postcode ?? '',
    country,
  }
}

export async function searchPlaces(query: string, language: string, signal?: AbortSignal): Promise<GeoPlace[]> {
  const params = new URLSearchParams({
    format: 'jsonv2',
    addressdetails: '1',
    limit: '6',
    'accept-language': language,
    q: query,
  })

  const response = await fetch(`${ENDPOINT}?${params.toString()}`, { signal, headers: { Accept: 'application/json' } })
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`)
  }

  const items = (await response.json()) as NominatimItem[]

  return items.map(toPlace)
}

export async function reversePlace(lat: number, lon: number, language: string): Promise<GeoPlace | null> {
  const params = new URLSearchParams({
    format: 'jsonv2',
    addressdetails: '1',
    zoom: '18',
    'accept-language': language,
    lat: lat.toFixed(6),
    lon: lon.toFixed(6),
  })

  const response = await fetch(`${REVERSE_ENDPOINT}?${params.toString()}`, { headers: { Accept: 'application/json' } })
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`)
  }

  const item = (await response.json()) as NominatimReverse
  if (item.error !== undefined || item.display_name === undefined) {
    return null
  }

  const place = toPlace(item)

  return { ...place, lat, lon }
}
