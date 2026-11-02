import { keepPreviousData, useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query'
import { cancelMovement, getMovementOptions, listAccounts, listMovements, recordMovement, type MovementListParams } from './api'
import type { MovementInput } from './types'

export const treasuryKeys = {
  all: ['treasury'] as const,
  accounts: ['treasury', 'accounts'] as const,
  movements: (p: MovementListParams) => ['treasury', 'movements', p] as const,
  options: ['treasury', 'options'] as const,
}

/** Après une écriture de trésorerie : soldes, mouvements, listes de saisie des paiements. */
function refresh(qc: QueryClient) {
  qc.invalidateQueries({ queryKey: treasuryKeys.all })
  qc.invalidateQueries({ queryKey: ['payments', 'options'] })
}

export function useAccounts() {
  return useQuery({ queryKey: treasuryKeys.accounts, queryFn: listAccounts })
}

export function useMovements(params: MovementListParams) {
  return useQuery({
    queryKey: treasuryKeys.movements(params),
    queryFn: () => listMovements(params),
    placeholderData: keepPreviousData,
  })
}

export function useMovementOptions() {
  return useQuery({ queryKey: treasuryKeys.options, queryFn: getMovementOptions })
}

export function useRecordMovement() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: MovementInput) => recordMovement(input),
    onSuccess: () => refresh(qc),
  })
}

export function useCancelMovement() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => cancelMovement(id, reason),
    onSuccess: () => refresh(qc),
  })
}