import { keepPreviousData, useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query'
import {
  cancelPayment,
  getPaymentOptions,
  getReceivable,
  listDossierInvoices,
  listDossierPayments,
  listPayments,
  recordPayment,
  type PaymentListParams,
} from './api'
import type { PaymentInput } from './types'

export const paymentKeys = {
  list: (p: PaymentListParams) => ['payments', 'list', p] as const,
  dossier: (id: number) => ['payments', 'dossier', id] as const,
  invoices: (id: number) => ['payments', 'invoices', id] as const,
  receivable: ['payments', 'receivable'] as const,
  options: ['payments', 'options'] as const,
}

/**
 * Un paiement touche beaucoup d'écrans : la facture (état Impayée/Partielle/Soldée), le statut du dossier,
 * la trésorerie (soldes, mouvements). On rafraîchit donc tout ce qui est affiché ; le reste sera relu à la prochaine visite.
 */
function refreshEverything(qc: QueryClient) {
  qc.invalidateQueries()
}

export function usePayments(params: PaymentListParams) {
  return useQuery({
    queryKey: paymentKeys.list(params),
    queryFn: () => listPayments(params),
    placeholderData: keepPreviousData,
  })
}

export function useDossierPayments(dossierId: number) {
  return useQuery({ queryKey: paymentKeys.dossier(dossierId), queryFn: () => listDossierPayments(dossierId) })
}

export function useDossierInvoices(dossierId: number) {
  return useQuery({ queryKey: paymentKeys.invoices(dossierId), queryFn: () => listDossierInvoices(dossierId) })
}

export function useReceivable() {
  return useQuery({ queryKey: paymentKeys.receivable, queryFn: getReceivable })
}

export function usePaymentOptions() {
  return useQuery({ queryKey: paymentKeys.options, queryFn: getPaymentOptions })
}

export function useRecordPayment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ documentId, input }: { documentId: number; input: PaymentInput }) => recordPayment(documentId, input),
    onSuccess: () => refreshEverything(qc),
  })
}

export function useCancelPayment() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) => cancelPayment(id, reason),
    onSuccess: () => refreshEverything(qc),
  })
}