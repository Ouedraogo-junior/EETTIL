import { useState } from 'react'
import axios from 'axios'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { formatDate, formatNumber, formatXOF } from '@/core/lib/format'
import { ConfirmDialog } from '@/core/ui/ConfirmDialog'
import { useToast } from '@/core/ui/toast-context'
import { DocStateBadge } from './badges'
import { CancelDocumentModal } from './CancelDocumentModal'
import { useConvertDocument, useDocument } from './hooks'
import { docLabel } from './labels'
import { TotalsBox } from './TotalsBox'
import type { DocumentLine } from './types'

function BlockTable({ title, lines, discounts }: { title: string; lines: DocumentLine[]; discounts: boolean }) {
  if (!lines.length) return null
  return (
    <section className="panel flush">
      <div className="bd" style={{ paddingBottom: 0 }}>
        <h2 style={{ margin: 0, fontSize: 15 }}>{title}</h2>
      </div>
      <div className="tablewrap">
        <table className="data cards">
          <thead>
            <tr>
              <th>Désignation</th>
              <th>Quantité</th>
              <th>Prix unitaire</th>
              {discounts && <th>Remise</th>}
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            {lines.map((l) => (
              <tr key={l.id}>
                <td className="main">{l.label}</td>
                <td data-label="Quantité">
                  {formatNumber(l.quantity)} {l.unit}
                </td>
                <td data-label="Prix unitaire">{formatXOF(l.unit_price)}</td>
                {discounts && (
                  <td data-label="Remise">
                    {l.discount_kind
                      ? `${l.discount_kind === 'pct' ? formatNumber(l.discount_value) + ' % · ' : ''}− ${formatXOF(l.discount_amount)}`
                      : '–'}
                  </td>
                )}
                <td data-label="Total">
                  <b>{formatXOF(l.total)}</b>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  )
}

export default function DocumentDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const { data: doc, isPending, isError, error } = useDocument(id)
  const convert = useConvertDocument()
  const [converting, setConverting] = useState(false)
  const [cancelling, setCancelling] = useState(false)

  if (isPending) return <div className="empty">Chargement…</div>
  if (isError || !doc) {
    const notFound = axios.isAxiosError(error) && error.response?.status === 404
    return (
      <div className="panel">
        <div className="empty">
          <b>{notFound ? 'Document introuvable' : 'Chargement impossible'}</b>
          {notFound ? 'Ce document n’existe pas.' : apiError(error)}
          <div style={{ marginTop: 12 }}>
            <Link to="/factures" className="btn sm">
              Retour à la liste
            </Link>
          </div>
        </div>
      </div>
    )
  }

  const label = docLabel(doc.type)
  const lines = doc.lines ?? []
  const dossier = doc.dossier
  const client = dossier?.client
  const discountNote =
    doc.global_discount.kind === 'pct' ? `${formatNumber(doc.global_discount.value)} %` : undefined

  const onConvert = async () => {
    try {
      const facture = await convert.mutateAsync(doc.id)
      toast(`Facture ${facture.number} créée`)
      navigate(`/documents/${facture.id}`)
    } catch (e) {
      setConverting(false)
      toast(apiError(e), 'bad')
    }
  }

  return (
    <>
      <div>
        <Link to={`/dossiers/${doc.dossier_id}?onglet=documents`} className="btn link backlink">
          ← Dossier {dossier?.number}
        </Link>
      </div>
      <div className="ph">
        <div>
          <h1 className="num">
            {label} {doc.number}
          </h1>
          <div className="sub">
            {client && <Link to={`/clients/${client.id}`}>{client.name}</Link>} · {formatDate(doc.issued_on)} ·{' '}
            <DocStateBadge state={doc.state} />
          </div>
        </div>
        <div className="grow" />
        <div className="acts">
          {doc.type === 'proforma' && doc.state === 'emis' && (
            <button className="btn primary" onClick={() => setConverting(true)}>
              Transformer en facture
            </button>
          )}
          {doc.is_editable && (
            <Link to={`/documents/${doc.id}/modifier`} className="btn">
              Modifier
            </Link>
          )}
          {doc.state === 'emis' && (
            <button className="btn danger" onClick={() => setCancelling(true)}>
              Annuler
            </button>
          )}
        </div>
      </div>

      <div className="stack">
        {doc.state === 'annule' && (
          <section className="panel cancelled">
            <div className="bd">
              <b>Document annulé</b>
              {doc.cancelled_at && <> le {formatDate(doc.cancelled_at)}</>}
              <div>Motif : {doc.cancel_reason}</div>
            </div>
          </section>
        )}

        <section className="panel">
          <div className="bd meta">
            <div>
              <div className="l">Dossier</div>
              <div className="v num">
                <Link to={`/dossiers/${doc.dossier_id}`}>{dossier?.number}</Link>
              </div>
            </div>
            <div>
              <div className="l">Client</div>
              <div className="v">{client?.name}</div>
            </div>
            <div>
              <div className="l">IFU / RCCM du client</div>
              <div className="v">{[client?.ifu, client?.rccm].filter(Boolean).join(' · ') || '–'}</div>
            </div>
            <div>
              <div className="l">BL / LTA</div>
              <div className="v">{dossier?.bl_lta || '–'}</div>
            </div>
            {doc.caf_value > 0 && (
              <div>
                <div className="l">Valeur CAF</div>
                <div className="v">{formatXOF(doc.caf_value)}</div>
              </div>
            )}
            {(doc.containers_20 > 0 || doc.containers_40 > 0) && (
              <div>
                <div className="l">Conteneurs</div>
                <div className="v">
                  {doc.containers_20 > 0 && `${doc.containers_20} × 20’`}
                  {doc.containers_20 > 0 && doc.containers_40 > 0 && ' · '}
                  {doc.containers_40 > 0 && `${doc.containers_40} × 40’`}
                </div>
              </div>
            )}
            {doc.currency !== 'XOF' && (
              <div>
                <div className="l">Devise de la marchandise</div>
                <div className="v">
                  {doc.currency} (taux {doc.exchange_rate !== null ? formatNumber(doc.exchange_rate) : '–'})
                </div>
              </div>
            )}
            {doc.source_document_id && (
              <div>
                <div className="l">Issue de la quotation</div>
                <div className="v">
                  <Link to={`/documents/${doc.source_document_id}`}>Voir la quotation</Link>
                </div>
              </div>
            )}
          </div>
        </section>

        <BlockTable title="A. Débours" lines={lines.filter((l) => l.block === 'D')} discounts={false} />
        <BlockTable title="B. Prestations" lines={lines.filter((l) => l.block === 'P')} discounts />

        <section className="panel">
          <div className="bd">
            <TotalsBox
              totals={doc.totals}
              vatEnabled={doc.vat_enabled}
              vatRate={doc.vat_rate}
              discountNote={discountNote}
            />
          </div>
        </section>
      </div>

      {converting && (
        <ConfirmDialog
          title="Transformer en facture ?"
          message={`Une facture sera créée avec les mêmes lignes, remises et TVA, et son propre numéro. La quotation ${doc.number} sera marquée « Transformée » et ne sera plus modifiable.`}
          confirmLabel="Créer la facture"
          busy={convert.isPending}
          onConfirm={onConvert}
          onCancel={() => setConverting(false)}
        />
      )}
      {cancelling && <CancelDocumentModal doc={doc} onClose={() => setCancelling(false)} />}
    </>
  )
}