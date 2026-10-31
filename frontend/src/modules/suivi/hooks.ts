import { useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query'
import { dossierKeys } from '@/modules/dossiers/hooks'
import type { Dossier } from '@/modules/dossiers/types'
import { activateTracking, saveTracking } from './api'
import type { Tracking, TrackingInput } from './types'

function cacheTracking(qc: QueryClient, dossierId: number, tracking: Tracking) {
  qc.setQueryData<Dossier>(dossierKeys.detail(String(dossierId)), (d) => (d ? { ...d, tracking } : d))
  qc.invalidateQueries({ queryKey: dossierKeys.lists })
  qc.invalidateQueries({ queryKey: ['suivi'] })
}

export function useActivateTracking(dossierId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: () => activateTracking(dossierId),
    onSuccess: (t) => cacheTracking(qc, dossierId, t),
  })
}

export function useSaveTracking(dossierId: number) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: TrackingInput) => saveTracking(dossierId, input),
    onSuccess: (t) => cacheTracking(qc, dossierId, t),
  })
}