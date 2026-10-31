import { keepPreviousData, useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query'
import {
  changeStatus,
  createDossier,
  deleteDossier,
  getDossier,
  getOptions,
  listDossiers,
  updateDossier,
  type DossierListParams,
} from './api'
import type { Dossier, DossierStatus, UpdateDossierInput } from './types'

export const dossierKeys = {
  lists: ['dossiers', 'list'] as const,
  list: (p: DossierListParams) => ['dossiers', 'list', p] as const,
  detail: (id: string) => ['dossiers', 'detail', id] as const,
  options: ['dossiers', 'options'] as const,
}

/** Met la fiche à jour dans le cache et rafraîchit les listes (dossiers et suivi). */
export function cacheDossier(qc: QueryClient, dossier: Dossier) {
  qc.setQueryData(dossierKeys.detail(String(dossier.id)), dossier)
  qc.invalidateQueries({ queryKey: dossierKeys.lists })
  qc.invalidateQueries({ queryKey: ['suivi'] })
}

export function useDossiers(params: DossierListParams) {
  return useQuery({
    queryKey: dossierKeys.list(params),
    queryFn: () => listDossiers(params),
    placeholderData: keepPreviousData,
  })
}

export function useDossier(id: string | undefined) {
  return useQuery({
    queryKey: dossierKeys.detail(id ?? ''),
    queryFn: () => getDossier(id!),
    enabled: !!id,
  })
}

export function useDossierOptions() {
  return useQuery({ queryKey: dossierKeys.options, queryFn: getOptions, staleTime: Infinity })
}

export function useCreateDossier() {
  const qc = useQueryClient()
  return useMutation({ mutationFn: createDossier, onSuccess: (d) => cacheDossier(qc, d) })
}

export function useUpdateDossier(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: UpdateDossierInput) => updateDossier(id, input),
    onSuccess: (d) => cacheDossier(qc, d),
  })
}

export function useChangeStatus(id: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (status: DossierStatus) => changeStatus(id, status),
    onSuccess: (d) => cacheDossier(qc, d),
  })
}

export function useDeleteDossier() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: deleteDossier,
    onSuccess: (_void, id) => {
      qc.removeQueries({ queryKey: dossierKeys.detail(String(id)) })
      qc.invalidateQueries({ queryKey: dossierKeys.lists })
      qc.invalidateQueries({ queryKey: ['suivi'] })
    },
  })
}