import { useState, type ReactNode } from 'react'
import { apiError } from '@/core/api/errors'
import { formatDate, formatNumber } from '@/core/lib/format'
import { useToast } from '@/core/ui/toast-context'
import type { Dossier } from '@/modules/dossiers/types'
import { useActivateTracking } from './hooks'
import { TrackingForm } from './TrackingForm'

function Dash({ children }: { children?: ReactNode }) {
  return children ? <>{children}</> : <span className="faint">–</span>
}

export function TrackingTab({ dossier }: { dossier: Dossier }) {
  const toast = useToast()
  const activate = useActivateTracking(dossier.id)
  const [editing, setEditing] = useState(false)
  const t = dossier.tracking

  const onActivate = async () => {
    try {
      await activate.mutateAsync()
      toast('Suivi activé. Renseignez les informations du dossier.')
      setEditing(true)
    } catch (e) {
      toast(apiError(e), 'bad')
    }
  }

  if (!t) {
    return (
      <div className="empty">
        <b>Ce dossier n’a pas de suivi de transit</b>
        Activez le suivi si le dossier comprend du transit ou du dédouanement.
        <div style={{ marginTop: 14 }}>
          <button className="btn primary" disabled={activate.isPending} onClick={onActivate}>
            Activer le suivi de transit
          </button>
        </div>
      </div>
    )
  }

  const headline =
    t.state === 'delivered'
      ? 'Livré à l’agence'
      : t.current_step
        ? `Étape actuelle : ${t.current_step.label}`
        : 'Suivi pas encore commencé'
  const dpi = t.dpi_number ? `${t.dpi_number}${t.dpi_date ? ' du ' + formatDate(t.dpi_date) : ''}` : ''
  const invoices = (t.supplier_invoices ?? '')
    .split(';')
    .map((s) => s.trim())
    .filter(Boolean)
    .join(' ; ')

  return (
    <div className="tbody">
      <div className="trow">
        <div>
          <b>{headline}</b>
          <div className="hint">
            Chaque étape est facultative et se renseigne avec sa date réelle, dans l’ordre que vous voulez.
          </div>
        </div>
        <div style={{ flex: 1 }} />
        <button className="btn" onClick={() => setEditing(true)}>
          Modifier le suivi
        </button>
      </div>

      <ol className="steps" aria-label="Étapes du transit">
        {t.steps.map((s) => (
          <li key={s.key} className={(s.date ? 'done ' : '') + (t.current_step?.key === s.key ? 'cur' : '')}>
            <span className="dot" />
            <b>{s.label}</b>
            <span className="dt">{s.date ? formatDate(s.date) : 'Pas encore'}</span>
          </li>
        ))}
      </ol>

      <div className="meta tmeta">
        <div>
          <div className="l">Facture(s) fournisseur</div>
          <div className="v num">
            <Dash>{invoices}</Dash>
          </div>
        </div>
        <div>
          <div className="l">N° de DPI</div>
          <div className="v num">
            <Dash>{dpi}</Dash>
          </div>
        </div>
        <div>
          <div className="l">BL / LTA</div>
          <div className="v num">
            <Dash>{t.bl_lta}</Dash>
          </div>
        </div>
        <div>
          <div className="l">N° dossier EETTIL</div>
          <div className="v num">{dossier.number}</div>
        </div>
        <div>
          <div className="l">N° d’ordre de transit</div>
          <div className="v num">
            <Dash>{t.transit_order_number}</Dash>
          </div>
        </div>
        <div>
          <div className="l">Régime douanier</div>
          <div className="v">
            <Dash>{t.regime}</Dash>
          </div>
        </div>
        <div>
          <div className="l">Nature de la marchandise</div>
          <div className="v">
            <Dash>{t.nature}</Dash>
          </div>
        </div>
        <div>
          <div className="l">Colis · poids</div>
          <div className="v num">
            {t.packages !== null ? `${t.packages} colis` : '–'} · {t.weight_kg !== null ? `${formatNumber(t.weight_kg)} kg` : '–'}
          </div>
        </div>
      </div>

      <div className="tcomment">
        <div className="l">Commentaires</div>
        <div>{t.comments || <span className="faint">Aucun commentaire</span>}</div>
      </div>

      {editing && <TrackingForm dossier={dossier} tracking={t} onClose={() => setEditing(false)} />}
    </div>
  )
}