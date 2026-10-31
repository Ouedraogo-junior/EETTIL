import { keepPreviousData, useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query'
import {
  cancelDocument,
  convertDocument,
  createDocument,
  getDocument,
  getDocumentOptions,
  listDocuments,
  listDossierDocuments,
  previewDocument,
  updateDocument,
  type DocumentListParams,
} from './api'
import type { BillingDocument, DocumentInput, PreviewInput } from './types'

export const documentKeys = {
  lists: ['documents', 'list'] as const,
  list: (p: DocumentListParams) => ['documents', 'list', p] as const,
  ofDossier: (dossierId: number) => ['documents', 'dossier', dossierId] as const,
  detail: (id: string) => ['documents', 'detail', id] as const,
  options: ['documents', 'options'] as const,
  preview: (key: string) => ['documents', 'preview', key] as const,
}

/** Met le document à jour dans le cache et rafraîchit ce qui en dépend (listes, onglet du dossier, statut du dossier). */
function refresh(qc: QueryClient, doc: BillingDocument) {
  qc.setQueryData(documentKeys.detail(String(doc.id)), doc)
  qc.invalidateQueries({ queryKey: documentKeys.lists })
  qc.invalidateQueries({ queryKey: documentKeys.ofDossier(doc.dossier_id) })
  qc.invalidateQueries({ queryKey: ['dossiers'] })
}

export function useDocuments(params: DocumentListParams) {
  return useQuery({
    queryKey: documentKeys.list(params),
    queryFn: () => listDocuments(params),
    placeholderData: keepPreviousData,
  })
}

export function useDossierDocuments(dossierId: number) {
  return useQuery({
    queryKey: documentKeys.ofDossier(dossierId),
    queryFn: () => listDossierDocuments(dossierId),
    enabled: dossierId > 0,
  })
}

export function useDocument(id: string | undefined) {
  return useQuery({
    queryKey: documentKeys.detail(id ?? ''),
    queryFn: () => getDocument(id!),
    enabled: !!id,
  })
}

export function useDocumentOptions() {
  return useQuery({ queryKey: documentKeys.options, queryFn: getDocumentOptions, staleTime: Infinity })
}

/** Calcul des totaux par le serveur. `key` est le JSON de la saisie : il sert aussi de clé de cache. */
export function useDocumentPreview(key: string, enabled: boolean) {
  return useQuery({
    queryKey: documentKeys.preview(key),
    queryFn: () => previewDocument(JSON.parse(key) as PreviewInput),
    enabled,
    placeholderData: keepPreviousData,
    staleTime: 60_000,
    retry: false,
  })
}

export function useCreateDocument(dossierId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: DocumentInput) => createDocument(dossierId, input),
    onSuccess: (doc) => refresh(qc, doc),
  })
}

export function useUpdateDocument(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: DocumentInput) => updateDocument(id, input),
    onSuccess: (doc) => refresh(qc, doc),
  })
}

export function useConvertDocument() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => convertDocument(id),
    onSuccess: (doc, sourceId) => {
      refresh(qc, doc)
      qc.invalidateQueries({ queryKey: documentKeys.detail(String(sourceId)) })
    },
  })
}

export function useCancelDocument(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (reason: string) => cancelDocument(id, reason),
    onSuccess: (doc) => refresh(qc, doc),
  })
}