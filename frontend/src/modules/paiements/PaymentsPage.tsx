import { useState } from 'react'
import { Link } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { formatDate, formatFcfa } from '@/core/lib/format'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { Pagination } from '@/core/ui/Pagination'
import { CancelPaymentDialog } from './CancelPaymentDialog'
import { usePayments, useReceivable } from './hooks'
import { PaymentBadge } from './PaymentBadge'
import { PaymentForm } from './PaymentForm'
import { PaymentsTable } from './PaymentsTable'
import type { InvoiceSummary, Payment } from './types'

export default function PaymentsPage() {
  const receivable = useReceivable()
  const [input, setInput] = useState('')
  const [page, setPage] = useState(1)
  const q = useDebouncedValue(input.trim(), 300)
  const history = usePayments({ q: q || undefined, page })
  const [paying, setPaying] = useState<InvoiceSummary | null>(null)
  const [cancelling, setCancelling] = useState<Payment | null>(null)

  const invoices = receivable.data?.invoices ?? []
  const meta = history.data?.meta

  return (
    <>
      <div className="ph">
        <div>
          <h1>Paiements</h1>
          <div className="sub">Factures à encaisser et historique des paiements reçus</div>
        </div>
      </div>

      <div className="stack">
        <section className="panel flush">
          <div className="hd">
            <h3>Factures à encaisser</h3>
            <div className="grow" />
            {receivable.data && (
              <span className="muted">
                {invoices.length} facture{invoices.length > 1 ? 's' : ''} · reste <b>{formatFcfa(receivable.data.totalRest)}</b>
              </span>
            )}
          </div>
          {receivable.isError ? (
            <div className="empty">
              <div className="warn">{apiError(receivable.error)}</div>
              <button className="btn sm" onClick={() => receivable.refetch()}>
                Réessayer
              </button>
            </div>
          ) : receivable.isPending ? (
            <div className="empty">Chargement…</div>
          ) : invoices.length === 0 ? (
            <div className="empty">
              <b>Rien à encaisser</b>
              Toutes les factures émises sont soldées.
            </div>
          ) : (
            <div className="tablewrap">
              <table className="data cards">
                <thead>
                  <tr>
                    <th>Facture</th>
                    <th>Client</th>
                    <th className="r">Montant</th>
                    <th className="r">Encaissé</th>
                    <th className="r">Reste</th>
                    <th>État</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {invoices.map((inv) => (
                    <tr key={inv.id}>
                      <td className="main">
                        <Link to={`/documents/${inv.id}`} className="name num">
                          {inv.number}
                        </Link>
                        <div className="muted" style={{ fontWeight: 400, fontSize: 13 }}>
                          {formatDate(inv.issued_on)}
                          {inv.dossier && (
                            <>
                              {' · '}
                              <Link to={`/dossiers/${inv.dossier.id}?onglet=paiements`}>{inv.dossier.number}</Link>
                            </>
                          )}
                        </div>
                      </td>
                      <td data-label="Client">{inv.dossier?.client?.name ?? <span className="faint">–</span>}</td>
                      <td data-label="Montant" className="r num">
                        {formatFcfa(inv.total)}
                      </td>
                      <td data-label="Encaissé" className="r num">
                        {formatFcfa(inv.payment.paid)}
                      </td>
                      <td data-label="Reste" className="r num">
                        <b>{formatFcfa(inv.payment.rest)}</b>
                      </td>
                      <td data-label="État">
                        <PaymentBadge payment={inv.payment} />
                      </td>
                      <td className="r">
                        <button type="button" className="btn primary sm" onClick={() => setPaying(inv)}>
                          Encaisser
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </section>

        <section className="panel flush">
          <div className="hd">
            <h3>Historique des paiements</h3>
          </div>
          <div className="toolbar">
            <input
              type="search"
              className="inp"
              placeholder="Rechercher par client, facture ou référence"
              aria-label="Rechercher un paiement"
              value={input}
              onChange={(e) => {
                setInput(e.target.value)
                setPage(1)
              }}
            />
          </div>
          {history.isError ? (
            <div className="empty">
              <div className="warn">{apiError(history.error)}</div>
              <button className="btn sm" onClick={() => history.refetch()}>
                Réessayer
              </button>
            </div>
          ) : history.isPending ? (
            <div className="empty">Chargement…</div>
          ) : history.data.data.length === 0 ? (
            <div className="empty">
              {q ? (
                <>
                  <b>Aucun paiement trouvé</b>
                  Aucun paiement ne correspond à cette recherche.
                </>
              ) : (
                <>
                  <b>Aucun paiement pour le moment</b>
                  Les paiements apparaissent ici dès qu’ils sont enregistrés sur une facture.
                </>
              )}
            </div>
          ) : (
            <PaymentsTable payments={history.data.data} showDossier onCancel={setCancelling} />
          )}
          {meta && <Pagination page={meta.current_page} lastPage={meta.last_page} total={meta.total} onChange={setPage} />}
        </section>
      </div>

      {paying && <PaymentForm invoice={paying} onClose={() => setPaying(null)} />}
      {cancelling && <CancelPaymentDialog payment={cancelling} onClose={() => setCancelling(null)} />}
    </>
  )
}