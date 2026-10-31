import { http } from '@/core/api/http'
import type { Paginated } from '@/core/api/types'
import type { BillingDocument, DocumentInput, DocumentOptions, PreviewInput, PreviewResult } from './types'

export interface DocumentListParams {
  q?: string
  type?: string
  state?: string
  page?: number
  per_page?: number
}

export async function listDocuments(params: DocumentListParams): Promise<Paginated<BillingDocument>> {
  const { data } = await http.get<Paginated<BillingDocument>>('/api/v1/documents', { params })
  return data
}

export async function listDossierDocuments(dossierId: number): Promise<BillingDocument[]> {
  const { data } = await http.get<{ data: BillingDocument[] }>(`/api/v1/dossiers/${dossierId}/documents`)
  return data.data
}

export async function getDocument(id: string): Promise<BillingDocument> {
  const { data } = await http.get<{ data: BillingDocument }>(`/api/v1/documents/${id}`)
  return data.data
}

export async function createDocument(dossierId: number, input: DocumentInput): Promise<BillingDocument> {
  const { data } = await http.post<{ data: BillingDocument }>(`/api/v1/dossiers/${dossierId}/documents`, input)
  return data.data
}

export async function updateDocument(id: number, input: DocumentInput): Promise<BillingDocument> {
  const { data } = await http.put<{ data: BillingDocument }>(`/api/v1/documents/${id}`, input)
  return data.data
}

export async function convertDocument(id: number): Promise<BillingDocument> {
  const { data } = await http.post<{ data: BillingDocument }>(`/api/v1/documents/${id}/convert`)
  return data.data
}

export async function cancelDocument(id: number, reason: string): Promise<BillingDocument> {
  const { data } = await http.post<{ data: BillingDocument }>(`/api/v1/documents/${id}/cancel`, { reason })
  return data.data
}

export async function previewDocument(input: PreviewInput): Promise<PreviewResult> {
  const { data } = await http.post<{ data: PreviewResult }>('/api/v1/documents/preview', input)
  return data.data
}

export async function getDocumentOptions(): Promise<DocumentOptions> {
  const { data } = await http.get<{ data: DocumentOptions }>('/api/v1/documents/options')
  return data.data
}