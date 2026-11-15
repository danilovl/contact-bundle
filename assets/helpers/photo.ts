export const PHOTO_OUTPUT_SIZE = 512
export const PHOTO_DEFAULT_MAX_BYTES = 1_048_576

const MIN_OUTPUT_SIZE = 128
const QUALITIES = [0.9, 0.8, 0.7, 0.6, 0.5]

export interface PhotoCrop {
  sourceX: number
  sourceY: number
  side: number
}

export function dataUrlBytes(dataUrl: string): number {
  const commaIndex = dataUrl.indexOf(',')
  const base64Length = dataUrl.length - commaIndex - 1

  return Math.floor((base64Length * 3) / 4)
}

export function formatMegabytes(bytes: number): string {
  const megabytes = bytes / 1_048_576
  const rounded = Number(megabytes.toFixed(2))

  return rounded.toString()
}

function drawCrop(image: HTMLImageElement, crop: PhotoCrop, size: number): HTMLCanvasElement {
  const canvas = document.createElement('canvas')
  canvas.width = size
  canvas.height = size
  const context = canvas.getContext('2d')
  if (!context) {
    throw new Error('Canvas is not supported')
  }

  context.fillStyle = '#ffffff'
  context.fillRect(0, 0, size, size)
  context.drawImage(image, crop.sourceX, crop.sourceY, crop.side, crop.side, 0, 0, size, size)

  return canvas
}

export function renderCrop(image: HTMLImageElement, crop: PhotoCrop, maxBytes: number): string {
  let size = PHOTO_OUTPUT_SIZE
  let result = ''

  while (size >= MIN_OUTPUT_SIZE) {
    const canvas = drawCrop(image, crop, size)

    for (const quality of QUALITIES) {
      result = canvas.toDataURL('image/jpeg', quality)

      if (dataUrlBytes(result) <= maxBytes) {
        return result
      }
    }

    size = Math.floor(size * 0.75)
  }

  return result
}
