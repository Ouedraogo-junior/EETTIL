import { Link } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { Icon } from '@/core/ui/Icon'
import { DossiersTable } from './DossiersTable'
import { useDossiers } from './hooks'

export function ClientDossiersPanel({ clientId }: { clientId: number }) {
  const { data, isPending, isError, error } = useDossiers({ client_id: clientId })
  const dossiers = data?.data ?? []

  return (
    <section className="panel flush">
      <div className="hd">
        <h3>Dossiers{data ? ` (${data.meta.total})` : ''}</h3>
        <div className="grow" />
        <Link to={`/dossiers/nouveau?client=${clientId}`} className="btn sm primary">
          <Icon name="plus" />
          Nouveau dossier
        </Link>
      </div>
      {isError ? (
        <div className="empty">
          <div className="warn">{apiError(error)}</div>
        </div>
      ) : isPending ? (
        <div className="empty">Chargement…</div>
      ) : dossiers.length === 0 ? (
        <div className="empty">
          <b>Aucun dossier pour ce client</b>
          Les factures et paiements du client apparaîtront ici aux étapes 4 et 5.
        </div>
      ) : (
        <DossiersTable dossiers={dossiers} showClient={false} />
      )}
    </section>
  )
}