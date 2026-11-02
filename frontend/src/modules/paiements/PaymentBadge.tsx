import type { PaymentState, PaymentSummary } from './types'

const CLASS: Record<PaymentState, string> = {
  unpaid: 'b-red',
  partial: 'b-amber',
  settled: 'b-green',
}

/** État d'encaissement d'une facture : Impayée, Partielle ou Soldée. */
export function PaymentBadge({ payment }: { payment: PaymentSummary }) {
  return <span className={'badge ' + CLASS[payment.state]}>{payment.state_label}</span>
}