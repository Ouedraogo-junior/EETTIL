import { Link, useNavigate } from 'react-router-dom'
import { formatDate } from '@/core/lib/format'
import { StatusBadge, TrackingBadge } from './badges'
import type { Dossier } from './types'

export function DossiersTable({ dossiers, showClient = true }: { dossiers: Dossier[]; showClient?: boolean }) {
  const navigate = useNavigate()

  return (
    <div className="tablewrap">
      <table className="data cards">
        <thead>
          <tr>
            <th>Dossier</th>
            {showClient && <th>Client</th>}
            <th>Statut</th>
            <th>Transit</th>
          </tr>
        </thead>
        <tbody>
          {dossiers.map((d) => (
            <tr key={d.id} className="click" onClick={() => navigate(`/dossiers/${d.id}`)}>
              <td className="main">
                <Link to={`/dossiers/${d.id}`} className="name num" onClick={(e) => e.stopPropagation()}>
                  {d.number}
                </Link>
                <div className="muted" style={{ fontWeight: 400, fontSize: 13 }}>
                  {formatDate(d.opened_on)}
                  {d.description ? ` · ${d.description}` : ''}
                </div>
              </td>
              {showClient && <td data-label="Client">{d.client?.name}</td>}
              <td data-label="Statut">
                <StatusBadge status={d.status} label={d.status_label} />
              </td>
              <td data-label="Transit">
                <TrackingBadge tracking={d.tracking} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}