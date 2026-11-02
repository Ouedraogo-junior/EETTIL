export type DocumentType = 'proforma' | 'facture'
export type DocumentState = 'emis' | 'annule' | 'transforme'
export type Block = 'D' | 'P'
export type DiscountKind = 'pct' | 'amt'
import type { PaymentSummary } from '@/modules/paiements/types'

export interface DocumentLine {
  id: number
  block: Block
  label: string
  unit: string
  quantity: number
  unit_price: number
  discount_kind: DiscountKind | null
  discount_value: number
  discount_amount: number
  total: number
}

export interface DocumentTotals {
  debours: number
  prestations_subtotal: number
  global_discount: number
  prestations_ht: number
  vat: number
  prestations_total: number
  total: number
  total_in_words: string
}

export interface DocumentDossier {
  id: number
  number: string
  status: string
  bl_lta: string | null
  client: { id: number; name: string; address: string | null; ifu: string | null; rccm: string | null } | null
}

export interface BillingDocument {
  id: number
  dossier_id: number
  type: DocumentType
  number: string
  state: DocumentState
  is_editable: boolean
  issued_on: string
  currency: string
  exchange_rate: number | null
  caf_value: number
  containers_20: number
  containers_40: number
  vat_enabled: boolean
  vat_rate: number
  global_discount: { kind: DiscountKind | null; value: number }
  totals: DocumentTotals
  source_document_id: number | null
  cancelled_at: string | null
  cancel_reason: string | null
  lines?: DocumentLine[]
  dossier?: DocumentDossier
  payment: PaymentSummary | null // renvoyé par l'API pour une facture, null pour une quotation
}

export interface LineInput {
  block: Block
  label: string
  unit: string
  quantity: number
  unit_price: number
  discount_kind: DiscountKind | null
  discount_value: number
}

export interface DocumentInput {
  type?: DocumentType
  issued_on: string
  currency: string
  exchange_rate: number | null
  caf_value: number
  containers_20: number
  containers_40: number
  vat_enabled: boolean
  vat_rate: number
  global_discount_kind: DiscountKind | null
  global_discount_value: number
  lines: LineInput[]
}

export type PreviewInput = Pick<
  DocumentInput,
  'vat_enabled' | 'vat_rate' | 'global_discount_kind' | 'global_discount_value' | 'lines'
>

export interface PreviewLine {
  index: number
  gross: number
  discount: number
  over_limit: boolean
  total: number
}

export interface PreviewResult extends DocumentTotals {
  lines: PreviewLine[]
  global_over_limit: boolean
}

export interface DocumentOptions {
  vat_rate: number
  currencies: string[]
  units: string[]
  templates: Record<Block, string[]>
}