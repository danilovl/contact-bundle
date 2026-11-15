<template>
  <div ref="container" class="cb-map" :title="t('address.mapHint')"></div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { divIcon, map as createMap, marker as createMarker, tileLayer } from 'leaflet'
import type { LeafletMouseEvent, Map as LeafletMap, Marker } from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { t } from '../../i18n'

const POINT_ZOOM = 16
const WORLD_ZOOM = 2
const WORLD_CENTER: [number, number] = [30, 10]

const props = defineProps<{ lat: number | null; lon: number | null }>()

const emit = defineEmits<{
  (event: 'pick', point: { lat: number; lon: number }): void
}>()

const container = ref<HTMLElement | null>(null)

let mapInstance: LeafletMap | null = null
let pin: Marker | null = null

const pinIcon = divIcon({ className: 'cb-map-pin', iconSize: [22, 22], iconAnchor: [11, 11] })

function hasPoint(): boolean {
  return props.lat !== null && props.lon !== null
}

function placePin(lat: number, lon: number): void {
  if (mapInstance === null) {
    return
  }

  if (pin === null) {
    pin = createMarker([lat, lon], { icon: pinIcon, draggable: true }).addTo(mapInstance)
    pin.on('dragend', () => {
      const position = pin?.getLatLng()
      if (position) {
        emit('pick', { lat: position.lat, lon: position.lng })
      }
    })

    return
  }

  pin.setLatLng([lat, lon])
}

function onMapClick(event: LeafletMouseEvent): void {
  emit('pick', { lat: event.latlng.lat, lon: event.latlng.lng })
}

onMounted(() => {
  if (container.value === null) {
    return
  }

  const center: [number, number] = hasPoint() ? [props.lat as number, props.lon as number] : WORLD_CENTER
  const zoom = hasPoint() ? POINT_ZOOM : WORLD_ZOOM

  mapInstance = createMap(container.value, { center, zoom, scrollWheelZoom: true })
  tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap',
  }).addTo(mapInstance)
  mapInstance.on('click', onMapClick)

  if (hasPoint()) {
    placePin(props.lat as number, props.lon as number)
  }
})

watch(
  () => {
    return [props.lat, props.lon]
  },
  () => {
    if (mapInstance === null || props.lat === null || props.lon === null) {
      return
    }

    placePin(props.lat, props.lon)
    mapInstance.panTo([props.lat, props.lon])
  },
)

onBeforeUnmount(() => {
  mapInstance?.remove()
  mapInstance = null
  pin = null
})
</script>
