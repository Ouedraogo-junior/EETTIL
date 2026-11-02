import { useState } from 'react'
import { Link } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { formatDate, formatFcfa } from '@/core/lib/format'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { Icon } from '@/core/ui/Icon'
import { Pagination } from '@/core/ui/Pagination'
import { CancelMovementDialog } from './CancelMovementDialog'
import { useAccounts, useMovementOptions, useMovements } from './hooks'
import { MovementForm } from './MovementForm'
import type { Direction, Movement } from './types'

export default function TresoreriePage() {
  const accounts = useAccounts()
  const { data: options } = useMovementOptions()

  const [input, setInput] = useState('')
  const [accountId, setAccountId] = useState('')
  const [direction, setDirection] = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [page, setPage] = useState(1)
  const q = useDebouncedValue(input.trim(), 300)

  const [creating, setCreating] = useState<Direction | null>(null)
  const [cancelling, setCancelling] = useState<Movement | null>(null)

  const movements = useMovements({
    q: q || undefined,
    account_id: accountId ? Number(accountId) : undefined,
    direction: direction || undefined,
    category_id: categoryId ? Number(categoryId) : undefined,
    from: from || undefined,
    to: to || undefined,
    page,
  })

  const reset = <T,>(set: (v: T) => void) => (v: T) => {
    set(v)
    setPage(1)
  }
  const filtered = !!(q || accountId || direction || categoryId || from || to)
  const data = movements.data
  const list = data?.data ?? []
  const allCategories = options ? [...options.categories.in, ...options.categories.out] : []

  return (
    <>
      <div className="ph">
        <div>
          <h1>Trésorerie</h1>
          <div className="sub">Caisse et banque : soldes, entrées et sorties</div>
        </div>
        <div className="grow" />
        <div className="acts">
          <button className="btn" onClick={() => setCreating('in')}>
            <Icon name="plus" />
            Entrée
          </button>
          <button className="btn primary" onClick={() => setCreating('out')}>
            <Icon name="plus" />
            Sortie
          </button>
        </div>
      </div>

      <div className="stack">
        {accounts.isError ? (
          <div className="warn">{apiError(accounts.error)}</div>
        ) : (
          <div className="kpis">
            {(accounts.data?.accounts ?? []).map((a) => (
              <div key={a.id} className="kpi">
                <div className="l">{a.name}</div>
                <div className={'v' + (a.balance < 0 ? ' neg' : '')}>{formatFcfa(a.balance)}</div>
                <div className="d">{a.type_label}</div>
              </div>
            ))}
            {accounts.data && (
              <div className="kpi kpi-total">
                <div className="l">Total disponible</div>
                <div className="v">{formatFcfa(accounts.data.total)}</div>
                <div className="d">Caisse + banque</div>
              </div>
            )}
            {accounts.isPending && <div className="kpi">Chargement…</div>}
          </div>
        )}

        <section className="panel flush">
          <div className="toolbar">
            <input
              type="search"
              className="inp"
              placeholder="Rechercher un libellé ou un dossier"
              aria-label="Rechercher un mouvement"
              value={input}
              onChange={(e) => reset(setInput)(e.target.value)}
            />
            <select className="sel" aria-label="Compte" value={accountId} onChange={(e) => reset(setAccountId)(e.target.value)}>
              <option value="">Tous les comptes</option>
              {options?.accounts.map((a) => (
                <option key={a.id} value={a.id}>
                  {a.name}
                </option>
              ))}
            </select>
            <select className="sel" aria-label="Sens" value={direction} onChange={(e) => reset(setDirection)(e.target.value)}>
              <option value="">Entrées et sorties</option>
              <option value="in">Entrées</option>
              <option value="out">Sorties</option>
            </select>
            <select className="sel" aria-label="Catégorie" value={categoryId} onChange={(e) => reset(setCategoryId)(e.target.value)}>
              <option value="">Toutes les catégories</option>
              {allCategories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
            <label className="datefilter">
              Du
              <input type="date" className="inp" value={from} max={to || undefined} onChange={(e) => reset(setFrom)(e.target.value)} />
            </label>
            <label className="datefilter">
              au
              <input type="date" className="inp" value={to} min={from || undefined} onChange={(e) => reset(setTo)(e.target.value)} />
            </label>
          </div>

          {data && (
            <div className="totals-bar">
              <span>
                Entrées <b className="in">{formatFcfa(data.totals.in)}</b>
              </span>
              <span>
                Sorties <b className="out">{formatFcfa(data.totals.out)}</b>
              </span>
              <span>
                Solde de la sélection <b>{formatFcfa(data.totals.in - data.totals.out)}</b>
              </span>
            </div>
          )}

          {movements.isError ? (
            <div className="empty">
              <div className="warn">{apiError(movements.error)}</div>
              <button className="btn sm" onClick={() => movements.refetch()}>
                Réessayer
              </button>
            </div>
          ) : movements.isPending ? (
            <div className="empty">Chargement…</div>
          ) : list.length === 0 ? (
            <div className="empty">
              {filtered ? (
                <>
                  <b>Aucun mouvement trouvé</b>
                  Aucun mouvement ne correspond à ces filtres.
                </>
              ) : (
                <>
                  <b>Aucun mouvement pour le moment</b>
                  Les paiements reçus et les dépenses saisies apparaîtront ici.
                </>
              )}
            </div>
          ) : (
            <div className="tablewrap">
              <table className="data cards">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Compte</th>
                    <th className="r">Entrée</th>
                    <th className="r">Sortie</th>
                    <th />
                  </tr>
                </thead>
                <tbody>
                  {list.map((m) => (
                    <tr key={m.id}>
                      <td className="main">
                        <span className={m.cancelled ? 'strike' : undefined}>{formatDate(m.moved_on)}</span>
                      </td>
                      <td data-label="Libellé">
                        <span className={m.cancelled ? 'strike' : undefined}>{m.label}</span>
                        <div className="muted" style={{ fontSize: 13 }}>
                          {m.category?.name}
                          {m.is_debours && ' · débours'}
                          {m.dossier && (
                            <>
                              {' · '}
                              <Link to={`/dossiers/${m.dossier.id}`}>{m.dossier.number}</Link>
                            </>
                          )}
                        </div>
                        {m.cancelled && <div className="cancel-note">Annulé : {m.cancel_reason}</div>}
                      </td>
                      <td data-label="Compte">{m.account?.name}</td>
                      <td data-label="Entrée" className={'r num in' + (m.cancelled ? ' strike' : '')}>
                        {m.direction === 'in' ? formatFcfa(m.amount) : <span className="faint">–</span>}
                      </td>
                      <td data-label="Sortie" className={'r num out' + (m.cancelled ? ' strike' : '')}>
                        {m.direction === 'out' ? formatFcfa(m.amount) : <span className="faint">–</span>}
                      </td>
                      <td className="r">
                        {m.source === 'payment' ? (
                          <span className="faint" title="Annulez le paiement depuis l’écran Paiements">
                            Paiement
                          </span>
                        ) : (
                          !m.cancelled && (
                            <button type="button" className="btn sm danger" onClick={() => setCancelling(m)}>
                              Annuler
                            </button>
                          )
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          {data && (
            <Pagination page={data.meta.current_page} lastPage={data.meta.last_page} total={data.meta.total} onChange={setPage} />
          )}
        </section>
      </div>

      {creating && <MovementForm direction={creating} onClose={() => setCreating(null)} />}
      {cancelling && <CancelMovementDialog movement={cancelling} onClose={() => setCancelling(null)} />}
    </>
  )
}