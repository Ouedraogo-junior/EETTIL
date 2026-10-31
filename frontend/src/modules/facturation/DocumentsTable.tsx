import { Link, useNavigate } from 'react-router-dom'
import { formatDate, formatXOF } from '@/core/lib/format'
import { DocStateBadge } from './badges'
import { docLabel } from './labels'
import type { BillingDocument } from './types'

export function DocumentsTable({ documents, showDossier }: { documents: BillingDocument[]; showDossier?: boolean }) {
  const navigate = useNavigate()

  return (
    <div className="tablewrap">
      <table className="data cards">
        <thead>
          <tr>
            <th>Document</th>
            {showDossier && <th>Dossier</th>}
            <th>Montant</th>
            <th>État</th>
          </tr>
        </thead>
        <tbody>
          {documents.map((d) => (
            <tr key={d.id} className="click" onClick={() => navigate(`/documents/${d.id}`)}>
              <td className="main">
                <Link to={`/documents/${d.id}`} className="name num" onClick={(e) => e.stopPropagation()}>
                  {d.number}
                </Link>
                <div className="muted" style={{ fontWeight: 400, fontSize: 13 }}>
                  {docLabel(d.type)} · {formatDate(d.issued_on)}
                </div>
              </td>
              {showDossier && (
                <td data-label="Dossier">
                  <span className="num">{d.dossier?.number}</span>
                  <div className="muted" style={{ fontSize: 13 }}>
                    {d.dossier?.client?.name}
                  </div>
                </td>
              )}
              <td data-label="Montant">
                <b>{formatXOF(d.totals.total)}</b>
              </td>
              <td data-label="État">
                <DocStateBadge state={d.state} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}