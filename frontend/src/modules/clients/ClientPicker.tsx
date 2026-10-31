import { useState } from 'react'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { useClient, useClients } from './hooks'

interface Props {
  value: string // identifiant du client choisi, '' si aucun
  onChange: (id: string) => void
  invalid?: boolean
}

export function ClientPicker({ value, onChange, invalid }: Props) {
  const selected = useClient(value || undefined)

  if (value) {
    return (
      <div className="picked">
        <b>{selected.data?.name ?? 'Chargement…'}</b>
        <button type="button" className="btn sm" onClick={() => onChange('')}>
          Changer
        </button>
      </div>
    )
  }
  return <ClientSearch onPick={(id) => onChange(String(id))} invalid={invalid} />
}

function ClientSearch({ onPick, invalid }: { onPick: (id: number) => void; invalid?: boolean }) {
  const [term, setTerm] = useState('')
  const q = useDebouncedValue(term.trim(), 250)
  const { data, isPending } = useClients({ q: q || undefined, page: 1, per_page: 8 })
  const clients = data?.data ?? []

  return (
    <>
      <input
        type="search"
        className={'inp' + (invalid ? ' err' : '')}
        placeholder="Rechercher un client par nom, téléphone ou IFU"
        aria-label="Rechercher un client"
        autoComplete="off"
        value={term}
        onChange={(e) => setTerm(e.target.value)}
      />
      <ul className="picker">
        {isPending ? (
          <li>Chargement…</li>
        ) : clients.length === 0 ? (
          <li>Aucun client trouvé.</li>
        ) : (
          clients.map((c) => (
            <li key={c.id}>
              <button type="button" onClick={() => onPick(c.id)}>
                <b>{c.name}</b>
                {c.ifu && <span className="muted"> · IFU {c.ifu}</span>}
              </button>
            </li>
          ))
        )}
      </ul>
    </>
  )
}