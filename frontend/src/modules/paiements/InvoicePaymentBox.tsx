import { useState } from 'react'
import { formatFcfa } from '@/core/lib/format'
import { PaymentBadge } from './PaymentBadge'
import { PaymentForm } from './PaymentForm'
import type { PaymentSummary } from './types'

interface Props {
  documentId: number
  number: string
  /** Bloc `payment` renvoyé par l'API : null pour une quotation. */
  payment: PaymentSummary | null
  /** Vrai seulement pour une facture émise (pas annulée). */
  payable: boolean
}

/** Situation d'encaissement affichée sur la fiche d'une facture, avec le bouton pour enregistrer un paiement. */
export function InvoicePaymentBox({ documentId, number, payment, payable }: Props) {
  const [paying, setPaying] = useState(false)
  if (!payment) return null

  const total = payment.paid + payment.rest

  return (
    <div className="invoice-pay">
      <PaymentBadge payment={payment} />
      <span>
        Encaissé <b>{formatFcfa(payment.paid)}</b> sur {formatFcfa(total)}
        {payment.rest > 0 && (
          <>
            {' '}
            · reste <b>{formatFcfa(payment.rest)}</b>
          </>
        )}
      </span>
      {payable && payment.rest > 0 && (
        <button type="button" className="btn primary sm" onClick={() => setPaying(true)}>
          Enregistrer un paiement
        </button>
      )}
      {paying && (
        <PaymentForm
          invoice={{ id: documentId, number, issued_on: '', total, payment, dossier: null }}
          onClose={() => setPaying(false)}
        />
      )}
    </div>
  )
}