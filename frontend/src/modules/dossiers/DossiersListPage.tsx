import { useState } from 'react'
import { Link } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { Icon } from '@/core/ui/Icon'
import { Pagination } from '@/core/ui/Pagination'
import { DossiersTable } from './DossiersTable'
import { useDossierOptions, useDossiers } from './hooks'

export default function DossiersListPage() {
  const [input, setInput] = useState('')
  const [status, setStatus] = useState('')
  const [service, setService] = useState('')
  const [page, setPage] = useState(1)
  const q = useDebouncedValue(input.trim(), 300)

  const { data: options } = useDossierOptions()
  const { data, isPending, isError, error, refetch } = useDossiers({
    q: q || undefined,
    status: status || undefined,
    service: service || undefined,
    page,
  })
  const dossiers = data?.data ?? []
  const meta = data?.meta
  const filtered = !!(q || status || service)

  return (
    <>
      <div className="ph">
        <div>
          <h1>Dossiers</h1>
          <div className="sub">{meta ? `${meta.total} dossier${meta.total > 1 ? 's' : ''}` : '\u00a0'}</div>
        </div>
        <div className="grow" />
        <div className="acts">
          <Link to="/dossiers/nouveau" className="btn primary">
            <Icon name="plus" />
            Nouveau dossier
          </Link>
        </div>
      </div>

      <div className="panel flush">
        <div className="toolbar">
          <input
            type="search"
            className="inp"
            placeholder="Rechercher par client, numéro ou BL/LTA"
            aria-label="Rechercher un dossier"
            value={input}
            onChange={(e) => {
              setInput(e.target.value)
              setPage(1)
            }}
          />
          <select
            className="sel"
            aria-label="Filtrer par statut"
            value={status}
            onChange={(e) => {
              setStatus(e.target.value)
              setPage(1)
            }}
          >
            <option value="">Tous les statuts</option>
            {options?.statuses.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
          <select
            className="sel"
            aria-label="Filtrer par service"
            value={service}
            onChange={(e) => {
              setService(e.target.value)
              setPage(1)
            }}
          >
            <option value="">Tous les services</option>
            {options?.services.map((s) => (
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
        ) : dossiers.length === 0 ? (
          <div className="empty">
            {filtered ? (
              <>
                <b>Aucun dossier trouvé</b>
                Aucun dossier ne correspond à ces filtres.
              </>
            ) : (
              <>
                <b>Aucun dossier pour le moment</b>
                Créez votre premier dossier avec le bouton « Nouveau dossier ».
              </>
            )}
          </div>
        ) : (
          <DossiersTable dossiers={dossiers} />
        )}

        {meta && <Pagination page={meta.current_page} lastPage={meta.last_page} total={meta.total} onChange={setPage} />}
      </div>
    </>
  )
}