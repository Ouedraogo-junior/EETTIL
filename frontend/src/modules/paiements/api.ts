import { http } from '@/core/api/http'
import type { Paginated } from '@/core/api/types'
import type { InvoiceSummary, Payment, PaymentInput, PaymentOptions, PaymentResult } from './types'

export interface PaymentListParams {
  q?: string
  page?: number
  per_page?: number
}

export async function listPayments(params: PaymentListParams): Promise<Paginated<Payment>> {
  const { data } = await http.get<Paginated<Payment>>('/api/v1/payments', { params })
  return data
}

export async function listDossierPayments(dossierId: number): Promise<Payment[]> {
  const { data } = await http.get<{ data: Payment[] }>(`/api/v1/dossiers/${dossierId}/payments`)
  return data.data
}

export async function listDossierInvoices(dossierId: number): Promise<InvoiceSummary[]> {
  const { data } = await http.get<{ data: InvoiceSummary[] }>(`/api/v1/dossiers/${dossierId}/invoices`)
  return data.data
}

export async function getReceivable(): Promise<{ invoices: InvoiceSummary[]; totalRest: number }> {
  const { data } = await http.get<{ data: InvoiceSummary[]; total_rest: number }>('/api/v1/payments/receivable')
  return { invoices: data.data, totalRest: data.total_rest }
}

export async function getPaymentOptions(): Promise<PaymentOptions> {
  const { data } = await http.get<{ data: PaymentOptions }>('/api/v1/payments/options')
  return data.data
}

export async function recordPayment(documentId: number, input: PaymentInput): Promise<PaymentResult> {
  const { data } = await http.post<{ data: Payment; invoice: PaymentResult['invoice'] }>(
    `/api/v1/documents/${documentId}/payments`,
    { ...input, reference: input.reference.trim() || null },
  )
  return { payment: data.data, invoice: data.invoice }
}

export async function cancelPayment(id: number, reason: string): Promise<PaymentResult> {
  const { data } = await http.post<{ data: Payment; invoice: PaymentResult['invoice'] }>(`/api/v1/payments/${id}/cancel`, { reason })
  return { payment: data.data, invoice: data.invoice }
}