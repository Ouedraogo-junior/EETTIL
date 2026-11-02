import { useState } from 'react'
import { Link } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { formatDate, formatFcfa } from '@/core/lib/format'
import type { Dossier } from '@/modules/dossiers/types'
import { CancelPaymentDialog } from './CancelPaymentDialog'
import { useDossierInvoices, useDossierPayments } from './hooks'
import { PaymentBadge } from './PaymentBadge'
import { PaymentForm } from './PaymentForm'
import { PaymentsTable } from './PaymentsTable'
import type { InvoiceSummary, Payment } from './types'

/** Onglet « Paiements » de la fiche dossier : situation de la facture, encaissements, annulation. */
export function DossierPaymentsTab({ dossier }: { dossier: Dossier }) {
  const invoices = useDossierInvoices(dossier.id)
  const payments = useDossierPayments(dossier.id)
  const [paying, setPaying] = useState<InvoiceSummary | null>(null)
  const [cancelling, setCancelling] = useState<Payment | null>(null)

  if (invoices.isPending || payments.isPending) return <div className="empty">Chargement…</div>
  if (invoices.isError || payments.isError) {
    return (
      <div className="empty">
        <div className="warn">{apiError(invoices.error ?? payments.error)}</div>
        <button className="btn sm" onClick={() => { invoices.refetch(); payments.refetch() }}>
          Réessayer
        </button>
      </div>
    )
  }

  const list = invoices.data
  const history = payments.data
  const canPay = dossier.status !== 'annulee'

  return (
    <div className="tbody">
      {list.length === 0 ? (
        <div className="empty" style={{ padding: '18px 0' }}>
          <b>Aucune facture émise pour ce dossier</b>
          Un paiement s’enregistre sur une facture. Une quotation ne se paie pas : transformez-la d’abord en facture.
          <div style={{ marginTop: 12 }}>
            <Link to={`/dossiers/${dossier.id}?onglet=documents`} className="btn sm">
              Aller aux documents
            </Link>
          </div>
        </div>
      ) : (
        list.map((inv) => (
          <div key={inv.id} className="summary pay-summary">
            <div>
              <div className="l">Facture</div>
              <div className="v" style={{ fontSize: 17 }}>
                <Link to={`/documents/${inv.id}`}>{inv.number}</Link>
              </div>
              <div className="muted" style={{ fontSize: 13 }}>
                du {formatDate(inv.issued_on)}
              </div>
            </div>
            <div>
              <div className="l">Montant</div>
              <div className="v">{formatFcfa(inv.total)}</div>
            </div>
            <div>
              <div className="l">Encaissé</div>
              <div className="v">{formatFcfa(inv.payment.paid)}</div>
            </div>
            <div className={inv.payment.rest > 0 ? 'rest' : 'ok'}>
              <div className="l">Reste à payer</div>
              <div className="v">{formatFcfa(inv.payment.rest)}</div>
            </div>
            <div>
              <div className="l">État</div>
              <div style={{ marginTop: 6 }}>
                <PaymentBadge payment={inv.payment} />
              </div>
              {inv.payment.rest > 0 && canPay && (
                <button type="button" className="btn primary sm" style={{ marginTop: 8 }} onClick={() => setPaying(inv)}>
                  Enregistrer un paiement
                </button>
              )}
            </div>
          </div>
        ))
      )}

      {history.length > 0 && (
        <div className="panel flush" style={{ marginTop: 16 }}>
          <div className="hd">
            <h3>Paiements reçus</h3>
          </div>
          <PaymentsTable payments={history} onCancel={setCancelling} />
        </div>
      )}
      {list.length > 0 && history.length === 0 && (
        <p className="hint" style={{ marginTop: 14 }}>
          Aucun paiement reçu pour le moment.
        </p>
      )}

      {paying && <PaymentForm invoice={paying} onClose={() => setPaying(null)} />}
      {cancelling && <CancelPaymentDialog payment={cancelling} onClose={() => setCancelling(null)} />}
    </div>
  )
}