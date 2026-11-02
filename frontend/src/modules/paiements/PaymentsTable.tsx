import { Link } from 'react-router-dom'
import { formatDate, formatFcfa } from '@/core/lib/format'
import type { Payment } from './types'

interface Props {
  payments: Payment[]
  /** Affiche les colonnes dossier et client (historique global). */
  showDossier?: boolean
  onCancel: (payment: Payment) => void
}

export function PaymentsTable({ payments, showDossier = false, onCancel }: Props) {
  return (
    <div className="tablewrap">
      <table className="data cards">
        <thead>
          <tr>
            <th>Date</th>
            {showDossier && <th>Dossier</th>}
            <th>Facture</th>
            <th>Mode · compte</th>
            <th className="r">Montant</th>
            <th />
          </tr>
        </thead>
        <tbody>
          {payments.map((p) => (
            <tr key={p.id}>
              <td className="main">
                <span className={p.cancelled ? 'strike' : undefined}>{formatDate(p.paid_on)}</span>
                <div className="muted" style={{ fontWeight: 400, fontSize: 13 }}>
                  {p.reference}
                </div>
                {p.cancelled && (
                  <div className="cancel-note">Annulé : {p.cancel_reason}</div>
                )}
              </td>
              {showDossier && (
                <td data-label="Dossier">
                  {p.dossier ? (
                    <>
                      <Link to={`/dossiers/${p.dossier.id}?onglet=paiements`} className="name num">
                        {p.dossier.number}
                      </Link>
                      <div className="muted" style={{ fontSize: 13 }}>
                        {p.dossier.client?.name}
                      </div>
                    </>
                  ) : (
                    <span className="faint">–</span>
                  )}
                </td>
              )}
              <td data-label="Facture" className="num">
                {p.document?.number ?? <span className="faint">–</span>}
              </td>
              <td data-label="Mode · compte">
                {p.mode_label}
                <div className="muted" style={{ fontSize: 13 }}>
                  {p.account?.name}
                </div>
              </td>
              <td data-label="Montant" className={'r num' + (p.cancelled ? ' strike' : '')}>
                {formatFcfa(p.amount)}
              </td>
              <td className="r">
                {!p.cancelled && (
                  <button type="button" className="btn sm danger" onClick={() => onCancel(p)}>
                    Annuler
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}