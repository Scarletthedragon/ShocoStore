const maxUploadBytes = 1024 * 1024

export async function prepareProductImage(file) {
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    throw new Error('Izvēlies JPG, PNG vai WebP attēlu.')
  }
  if (file.size > 20 * 1024 * 1024) {
    throw new Error('Izvēlies attēlu līdz 20 MB.')
  }
  let bitmap
  try {
    bitmap = await createImageBitmap(file)
  } catch {
    throw new Error('Attēlu nevar atvērt. Izvēlies citu JPG, PNG vai WebP failu.')
  }
  try {
    const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height))
    const canvas = document.createElement('canvas')
    canvas.width = Math.max(1, Math.round(bitmap.width * scale))
    canvas.height = Math.max(1, Math.round(bitmap.height * scale))
    const context = canvas.getContext('2d')
    if (!context) throw new Error('Attēlu nevar sagatavot šajā pārlūkā.')
    context.fillStyle = '#ffffff'
    context.fillRect(0, 0, canvas.width, canvas.height)
    context.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
    for (const quality of [0.85, 0.7, 0.5]) {
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality))
      if (blob && blob.size <= maxUploadBytes) {
        return new File([blob], 'chocolate.jpg', { type: 'image/jpeg' })
      }
    }
    throw new Error('Attēls ir pārāk sarežģīts. Izvēlies mazāku attēlu.')
  } finally {
    bitmap.close()
  }
}
