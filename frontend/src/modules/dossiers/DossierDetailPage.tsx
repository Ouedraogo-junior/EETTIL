import { useState } from 'react'
import axios from 'axios'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { formatDate } from '@/core/lib/format'
import { ConfirmDialog } from '@/core/ui/ConfirmDialog'
import { useToast } from '@/core/ui/toast-context'
import { useMe } from '@/modules/auth/hooks'
import { TrackingTab } from '@/modules/suivi/TrackingTab'
import { StatusBadge } from './badges'
import { useChangeStatus, useDeleteDossier, useDossier, useDossierOptions } from './hooks'
import type { DossierStatus } from './types'
import { DossierPaymentsTab } from '@/modules/paiements/DossierPaymentsTab'
import { DocumentsTab } from '@/modules/facturation/DocumentsTab'

const TABS = [
  ['documents', 'Documents'],
  ['paiements', 'Paiements'],
  ['depenses', 'Dépenses'],
  ['suivi', 'Suivi du transit'],
] as const

const SOON: Record<string, string> = {
  documents: 'Les proformas et factures de ce dossier apparaîtront ici à l’étape 4.',
  paiements: 'Les paiements reçus apparaîtront ici à l’étape 5.',
  depenses: 'Les dépenses et débours rattachés au dossier apparaîtront ici à l’étape 6.',
}

export default function DossierDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const [params, setParams] = useSearchParams()
  const { data: me } = useMe()
  const { data: options } = useDossierOptions()
  const { data: dossier, isPending, isError, error } = useDossier(id)
  const changeStatus = useChangeStatus(dossier?.id ?? 0)
  const del = useDeleteDossier()
  const [confirming, setConfirming] = useState(false)

  if (isPending) return <div className="empty">Chargement…</div>
  if (isError || !dossier) {
    const notFound = axios.isAxiosError(error) && error.response?.status === 404
    return (
      <div className="panel">
        <div className="empty">
          <b>{notFound ? 'Dossier introuvable' : 'Chargement impossible'}</b>
          {notFound ? 'Ce dossier n’existe pas ou a été supprimé.' : apiError(error)}
          <div style={{ marginTop: 12 }}>
            <Link to="/dossiers" className="btn sm">
              Retour à la liste
            </Link>
          </div>
        </div>
      </div>
    )
  }

  const tab = params.get('onglet') ?? 'suivi'

  const onStatus = async (status: DossierStatus) => {
    try {
      await changeStatus.mutateAsync(status)
      toast(`Statut mis à jour : ${options?.statuses.find((s) => s.value === status)?.label ?? status}`)
    } catch (e) {
      toast(apiError(e), 'bad')
    }
  }

  const onDelete = async () => {
    try {
      await del.mutateAsync(dossier.id)
      toast('Dossier supprimé')
      navigate('/dossiers', { replace: true })
    } catch (e) {
      setConfirming(false)
      toast(apiError(e), 'bad')
    }
  }

  return (
    <>
      <div>
        <Link to="/dossiers" className="btn link backlink">
          ← Dossiers
        </Link>
      </div>
      <div className="ph">
        <div>
          <h1 className="num">Dossier {dossier.number}</h1>
          <div className="sub">
            {dossier.client && <Link to={`/clients/${dossier.client.id}`}>{dossier.client.name}</Link>} ·{' '}
            {formatDate(dossier.opened_on)} · <StatusBadge status={dossier.status} label={dossier.status_label} />
          </div>
        </div>
        <div className="grow" />
        <div className="acts">
          <select
            className="sel"
            style={{ width: 160 }}
            aria-label="Statut du dossier"
            value={dossier.status}
            disabled={changeStatus.isPending}
            onChange={(e) => onStatus(e.target.value as DossierStatus)}
          >
            {options?.statuses.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
          <Link to={`/dossiers/${dossier.id}/modifier`} className="btn">
            Modifier
          </Link>
          {me?.role === 'admin' && (
            <button className="btn danger" onClick={() => setConfirming(true)}>
              Supprimer
            </button>
          )}
        </div>
      </div>

      <div className="stack">
        <section className="panel">
          <div className="bd meta">
            <div>
              <div className="l">Client</div>
              <div className="v">{dossier.client?.name}</div>
            </div>
            <div>
              <div className="l">Services</div>
              <div className="v">{dossier.services.map((s) => s.label).join(', ')}</div>
            </div>
            <div>
              <div className="l">Date d’ouverture</div>
              <div className="v">{formatDate(dossier.opened_on)}</div>
            </div>
            <div>
              <div className="l">Description</div>
              <div className="v" style={{ fontWeight: 400 }}>
                {dossier.description || <span className="faint">–</span>}
              </div>
            </div>
          </div>
          {dossier.notes && (
            <div className="bd tcomment" style={{ marginTop: 0 }}>
              <div className="l">Notes</div>
              <div>{dossier.notes}</div>
            </div>
          )}
        </section>

        <section className="panel flush">
          <div className="tabs" role="tablist">
            {TABS.map(([key, label]) => (
              <button
                key={key}
                role="tab"
                aria-selected={tab === key}
                className={'tab' + (tab === key ? ' on' : '')}
                onClick={() => setParams({ onglet: key }, { replace: true })}
              >
                {label}
                {key === 'suivi' && <span className="n">{dossier.tracking ? dossier.tracking.state_label : '–'}</span>}
              </button>
            ))}
          </div>
          {tab === 'paiements' ? (
            <DossierPaymentsTab dossier={dossier} />
          ) : tab === 'suivi' ? (
            <TrackingTab dossier={dossier} />
          ) : tab === 'documents' ? (
            <DocumentsTab dossier={dossier} />
          ) : (
            <div className="empty">
              <b>Bientôt disponible</b>
              {SOON[tab] ?? ''}
            </div>
          )}
        </section>
      </div>

      {confirming && (
        <ConfirmDialog
          title="Supprimer ce dossier ?"
          message={`Le dossier ${dossier.number} sera retiré de la liste. Pour un dossier abandonné, préférez le statut « Annulée ». Cette action est réservée à l’Admin.`}
          confirmLabel="Supprimer"
          danger
          busy={del.isPending}
          onConfirm={onDelete}
          onCancel={() => setConfirming(false)}
        />
      )}
    </>
  )
}