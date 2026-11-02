import { useState } from 'react'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { useDossier, useDossiers } from './hooks'

interface Props {
  value: string // identifiant du dossier choisi, '' si aucun
  onChange: (id: string) => void
  invalid?: boolean
}

/** Recherche d'un dossier (par client, numéro ou BL/LTA). Les dossiers annulés ne sont pas proposés. */
export function DossierPicker({ value, onChange, invalid }: Props) {
  const selected = useDossier(value || undefined)

  if (value) {
    const d = selected.data
    return (
      <div className="picked">
        <b>{d ? `${d.number} · ${d.client?.name ?? ''}` : 'Chargement…'}</b>
        <button type="button" className="btn sm" onClick={() => onChange('')}>
          Changer
        </button>
      </div>
    )
  }
  return <DossierSearch onPick={(id) => onChange(String(id))} invalid={invalid} />
}

function DossierSearch({ onPick, invalid }: { onPick: (id: number) => void; invalid?: boolean }) {
  const [term, setTerm] = useState('')
  const q = useDebouncedValue(term.trim(), 250)
  const { data, isPending } = useDossiers({ q: q || undefined, page: 1, per_page: 8 })
  const dossiers = (data?.data ?? []).filter((d) => d.status !== 'annulee')

  return (
    <>
      <input
        type="search"
        className={'inp' + (invalid ? ' err' : '')}
        placeholder="Rechercher un dossier par client, numéro ou BL/LTA"
        aria-label="Rechercher un dossier"
        autoComplete="off"
        value={term}
        onChange={(e) => setTerm(e.target.value)}
      />
      <ul className="picker">
        {isPending ? (
          <li>Chargement…</li>
        ) : dossiers.length === 0 ? (
          <li>Aucun dossier trouvé.</li>
        ) : (
          dossiers.map((d) => (
            <li key={d.id}>
              <button type="button" onClick={() => onPick(d.id)}>
                <b className="num">{d.number}</b>
                <span className="muted"> · {d.client?.name}</span>
              </button>
            </li>
          ))
        )}
      </ul>
    </>
  )
}