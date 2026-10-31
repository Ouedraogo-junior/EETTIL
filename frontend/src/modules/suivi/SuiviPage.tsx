import { useState, type ReactNode } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { formatDate, formatNumber } from '@/core/lib/format'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { Pagination } from '@/core/ui/Pagination'
import { useToast } from '@/core/ui/toast-context'
import { TrackingBadge } from '@/modules/dossiers/badges'
import { useDossierOptions } from '@/modules/dossiers/hooks'
import { exportSuivi } from './api'
import { useSuivi } from './hooks'
import type { SuiviRow } from './types'

const dash = (v: ReactNode) => (v === null || v === undefined || v === '' ? <span className="faint">–</span> : v)

const dpiText = (t: SuiviRow) =>
  t.dpi_number ? `${t.dpi_number}${t.dpi_date ? ' du ' + formatDate(t.dpi_date) : ''}` : ''

const invoicesText = (t: SuiviRow) =>
  (t.supplier_invoices ?? '')
    .split(';')
    .map((s) => s.trim())
    .filter(Boolean)
    .join(' ; ')

export default function SuiviPage() {
  const navigate = useNavigate()
  const toast = useToast()
  const [input, setInput] = useState('')
  const [state, setState] = useState('')
  const [page, setPage] = useState(1)
  const [exporting, setExporting] = useState(false)
  const q = useDebouncedValue(input.trim(), 300)

  const filters = { q: q || undefined, state: state || undefined }
  const { data: options } = useDossierOptions()
  const { data, isPending, isError, error, refetch } = useSuivi({ ...filters, page })
  const rows = data?.data ?? []
  const meta = data?.meta

  const onExport = async () => {
    setExporting(true)
    try {
      await exportSuivi(filters)
    } catch (e) {
      toast(apiError(e, 'L’export a échoué. Réessayez.'), 'bad')
    } finally {
      setExporting(false)
    }
  }

  return (
    <>
      <div className="ph">
        <div>
          <h1>Suivi des dossiers</h1>
          <div className="sub">
            {meta ? `${meta.total} dossier${meta.total > 1 ? 's' : ''} suivi${meta.total > 1 ? 's' : ''}` : '\u00a0'}
          </div>
        </div>
        <div className="grow" />
        <div className="acts">
          <button className="btn" onClick={onExport} disabled={exporting || !meta || meta.total === 0}>
            {exporting ? 'Export en cours…' : 'Exporter vers Excel'}
          </button>
        </div>
      </div>

      <div className="panel flush">
        <div className="toolbar">
          <input
            type="search"
            className="inp"
            placeholder="Client, dossier, BL/LTA, DPI, facture, nature…"
            aria-label="Rechercher dans le suivi"
            value={input}
            onChange={(e) => {
              setInput(e.target.value)
              setPage(1)
            }}
          />
          <select
            className="sel"
            aria-label="Filtrer par état"
            value={state}
            onChange={(e) => {
              setState(e.target.value)
              setPage(1)
            }}
          >
            <option value="">Tous les états</option>
            {options?.tracking_states.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
        </div>

        {isError ? (
          <div className="empty">
            <div className="warn">{apiError(error)}</div>
            <button className="btn sm" onClick={() => refetch()}>
              Réessayer
            </button>
          </div>
        ) : isPending ? (
          <div className="empty">Chargement…</div>
        ) : rows.length === 0 ? (
          <div className="empty">
            <b>Aucun dossier trouvé</b>
            {q || state
              ? 'Aucun dossier suivi ne correspond à ces filtres.'
              : 'Les dossiers dont le suivi du transit est activé apparaîtront ici.'}
          </div>
        ) : (
          <div className="tablewrap">
            <table className="data tight cards">
              <thead>
                <tr>
                  <th>Dossier</th>
                  <th>Étape actuelle</th>
                  <th>Facture(s) fournisseur</th>
                  <th>N° DPI</th>
                  <th>BL / LTA</th>
                  <th>N° OT</th>
                  <th>Régime</th>
                  <th>Nature</th>
                  <th className="r">Colis</th>
                  <th className="r">Poids (kg)</th>
                  {rows[0].steps.map((s) => (
                    <th key={s.key}>{s.label}</th>
                  ))}
                  <th>Commentaires</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((t) => (
                  <tr key={t.id} className="click" onClick={() => navigate(`/dossiers/${t.dossier.id}?onglet=suivi`)}>
                    <td className="main">
                      <Link
                        to={`/dossiers/${t.dossier.id}?onglet=suivi`}
                        className="name num"
                        onClick={(e) => e.stopPropagation()}
                      >
                        {t.dossier.number}
                      </Link>
                      <div className="muted" style={{ fontWeight: 400, fontSize: 13 }}>
                        {t.dossier.client?.name}
                      </div>
                    </td>
                    <td data-label="Étape actuelle">
                      <TrackingBadge tracking={t} />
                    </td>
                    <td className="num hide-sm" data-label="Facture(s) fournisseur">
                      {dash(invoicesText(t))}
                    </td>
                    <td className="num" data-label="N° DPI">
                      {dash(dpiText(t))}
                    </td>
                    <td className="num" data-label="BL / LTA">
                      {dash(t.bl_lta)}
                    </td>
                    <td className="num hide-sm" data-label="N° OT">
                      {dash(t.transit_order_number)}
                    </td>
                    <td className="hide-sm" data-label="Régime">
                      {dash(t.regime)}
                    </td>
                    <td data-label="Nature">{dash(t.nature)}</td>
                    <td className="r num" data-label="Colis">
                      {dash(t.packages)}
                    </td>
                    <td className="r num" data-label="Poids (kg)">
                      {dash(t.weight_kg !== null ? formatNumber(t.weight_kg) : null)}
                    </td>
                    {t.steps.map((s) => (
                      <td key={s.key} className="num hide-sm" data-label={s.label}>
                        {dash(s.date ? formatDate(s.date) : null)}
                      </td>
                    ))}
                    <td className="hide-sm" data-label="Commentaires" style={{ minWidth: 200, whiteSpace: 'normal' }}>
                      {dash(t.comments)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta && <Pagination page={meta.current_page} lastPage={meta.last_page} total={meta.total} onChange={setPage} />}
      </div>
    </>
  )
}