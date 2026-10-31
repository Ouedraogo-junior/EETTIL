import { useState, type ReactNode } from 'react'
import axios from 'axios'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { ConfirmDialog } from '@/core/ui/ConfirmDialog'
import { useToast } from '@/core/ui/toast-context'
import { useMe } from '@/modules/auth/hooks'
import { useClient, useDeleteClient } from './hooks'
import { ClientDossiersPanel } from '@/modules/dossiers/ClientDossiersPanel'

function Info({ label, children }: { label: string; children?: ReactNode }) {
  return (
    <div className="field">
      <span className="lbl">{label}</span>
      <div>{children || <span className="faint">Non renseigné</span>}</div>
    </div>
  )
}

export default function ClientDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const { data: me } = useMe()
  const { data: client, isPending, isError, error } = useClient(id)
  const del = useDeleteClient()
  const [confirming, setConfirming] = useState(false)

  if (isPending) return <div className="empty">Chargement…</div>
  if (isError || !client) {
    const notFound = axios.isAxiosError(error) && error.response?.status === 404
    return (
      <div className="panel">
        <div className="empty">
          <b>{notFound ? 'Client introuvable' : 'Chargement impossible'}</b>
          {notFound ? 'Ce client n’existe pas ou a été supprimé.' : apiError(error)}
          <div style={{ marginTop: 12 }}>
            <Link to="/clients" className="btn sm">
              Retour à la liste
            </Link>
          </div>
        </div>
      </div>
    )
  }

  const onDelete = async () => {
    try {
      await del.mutateAsync(client.id)
      toast('Client supprimé')
      navigate('/clients', { replace: true })
    } catch (e) {
      setConfirming(false)
      toast(apiError(e), 'bad')
    }
  }

  return (
    <>
      <div>
        <Link to="/clients" className="btn link backlink">
          ← Clients
        </Link>
      </div>
      <div className="ph">
        <div>
          <h1>{client.name}</h1>
          {client.address && <div className="sub">{client.address}</div>}
        </div>
        <div className="grow" />
        <div className="acts">
          <Link to={`/clients/${client.id}/modifier`} className="btn">
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
          <div className="hd">
            <h3>Coordonnées</h3>
          </div>
          <div className="bd">
            <div className="infogrid">
              <Info label="Téléphone">
                {client.phone && <a href={`tel:${client.phone.replace(/\s/g, '')}`}>{client.phone}</a>}
              </Info>
              <Info label="E-mail">{client.email && <a href={`mailto:${client.email}`}>{client.email}</a>}</Info>
              <Info label="Adresse">{client.address}</Info>
              <Info label="IFU">{client.ifu}</Info>
              <Info label="RCCM">{client.rccm}</Info>
              <div style={{ gridColumn: '1 / -1' }}>
                <Info label="Notes">{client.notes}</Info>
              </div>
            </div>
          </div>
        </section>

        <ClientDossiersPanel clientId={client.id} />
      </div>

      {confirming && (
        <ConfirmDialog
          title="Supprimer ce client ?"
          message={`Le client « ${client.name} » sera retiré de la liste. Cette action est réservée à l’Admin.`}
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