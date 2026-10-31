import { Link } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import type { Dossier } from '@/modules/dossiers/types'
import { DocumentsTable } from './DocumentsTable'
import { useDossierDocuments } from './hooks'

export function DocumentsTab({ dossier }: { dossier: Dossier }) {
  const { data, isPending, isError, error, refetch } = useDossierDocuments(dossier.id)

  if (isPending) return <div className="empty">Chargement…</div>
  if (isError) {
    return (
      <div className="empty">
        <b>Chargement impossible</b>
        {apiError(error)}
        <div style={{ marginTop: 12 }}>
          <button className="btn sm" onClick={() => refetch()}>
            Réessayer
          </button>
        </div>
      </div>
    )
  }

  const activeFacture = data.find((d) => d.type === 'facture' && d.state !== 'annule')

  return (
    <>
      <div className="bd" style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
        <Link to={`/dossiers/${dossier.id}/documents/nouveau?type=proforma`} className="btn">
          Nouvelle quotation
        </Link>
        {activeFacture ? (
          <span className="hint">
            Ce dossier a déjà une facture : <Link to={`/documents/${activeFacture.id}`}>{activeFacture.number}</Link>
          </span>
        ) : (
          <Link to={`/dossiers/${dossier.id}/documents/nouveau?type=facture`} className="btn primary">
            Nouvelle facture
          </Link>
        )}
      </div>
      {data.length === 0 ? (
        <div className="empty">
          <b>Aucun document</b>
          Créez une quotation pour proposer un prix au client, ou directement la facture.
        </div>
      ) : (
        <DocumentsTable documents={data} />
      )}
    </>
  )
}