import type { Account, AccountType } from '@/modules/tresorerie/types'

export type PaymentModeValue = 'especes' | 'virement' | 'mobile_money'
export type PaymentState = 'unpaid' | 'partial' | 'settled'

export interface PaymentSummary {
  paid: number
  rest: number
  state: PaymentState
  state_label: string
}

/** Facture vue sous l'angle de l'encaissement. */
export interface InvoiceSummary {
  id: number
  number: string
  issued_on: string
  total: number
  payment: PaymentSummary
  dossier: {
    id: number
    number: string
    status: string
    client: { id: number; name: string } | null
  } | null
}

export interface Payment {
  id: number
  paid_on: string
  amount: number
  mode: PaymentModeValue
  mode_label: string
  reference: string | null
  account: { id: number; name: string } | null
  document: { id: number; number: string } | null
  dossier: { id: number; number: string; client: { id: number; name: string } | null } | null
  cancelled: boolean
  cancelled_at: string | null
  cancel_reason: string | null
}

export interface PaymentOptions {
  modes: { value: PaymentModeValue; label: string; account_type: AccountType }[]
  accounts: Account[]
}

export interface PaymentInput {
  paid_on: string
  amount: number
  mode: PaymentModeValue
  account_id: number
  reference: string
}

export interface PaymentResult {
  payment: Payment
  invoice: PaymentSummary
}