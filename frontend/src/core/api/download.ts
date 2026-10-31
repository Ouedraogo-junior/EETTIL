import { http } from './http'

/** Télécharge un fichier servi par l'API (la session par cookie empêche un simple lien). */
export async function downloadFile(url: string, params: Record<string, unknown>, filename: string): Promise<void> {
  const res = await http.get<Blob>(url, { params, responseType: 'blob' })
  const href = URL.createObjectURL(res.data)
  const a = document.createElement('a')
  a.href = href
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  URL.revokeObjectURL(href)
}