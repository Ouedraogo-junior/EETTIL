import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { apiError } from '@/core/api/errors'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { Icon } from '@/core/ui/Icon'
import { Pagination } from '@/core/ui/Pagination'
import { useClients } from './hooks'

export default function ClientsListPage() {
  const navigate = useNavigate()
  const [input, setInput] = useState('')
  const [page, setPage] = useState(1)
  const q = useDebouncedValue(input.trim(), 300)

  const { data, isPending, isError, error, refetch } = useClients({ q: q || undefined, page })
  const clients = data?.data ?? []
  const meta = data?.meta

  return (
    <>
      <div className="ph">
        <div>
          <h1>Clients</h1>
          <div className="sub">{meta ? `${meta.total} client${meta.total > 1 ? 's' : ''}` : '\u00a0'}</div>
        </div>
        <div className="grow" />
        <div className="acts">
          <Link to="/clients/nouveau" className="btn primary">
            <Icon name="plus" />
            Nouveau client
          </Link>
        </div>
      </div>

      <div className="panel flush">
        <div className="toolbar">
          <input
            type="search"
            className="inp"
            placeholder="Rechercher par nom, téléphone ou IFU"
            aria-label="Rechercher un client"
            value={input}
            onChange={(e) => {
              setInput(e.target.value)
              setPage(1)
            }}
          />
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
        ) : clients.length === 0 ? (
          <div className="empty">
            {q ? (
              <>
                <b>Aucun résultat</b>
                Aucun client ne correspond à « {q} ».
              </>
            ) : (
              <>
                <b>Aucun client pour le moment</b>
                Ajoutez votre premier client avec le bouton « Nouveau client ».
              </>
            )}
          </div>
        ) : (
          <div className="tablewrap">
            <table className="data cards">
              <thead>
                <tr>
                  <th>Client</th>
                  <th>Téléphone</th>
                  <th>IFU</th>
                  <th>RCCM</th>
                </tr>
              </thead>
              <tbody>
                {clients.map((c) => (
                  <tr key={c.id} className="click" onClick={() => navigate(`/clients/${c.id}`)}>
                    <td className="main">
                      <Link to={`/clients/${c.id}`} className="name" onClick={(e) => e.stopPropagation()}>
                        {c.name}
                      </Link>
                      {c.address && (
                        <div className="muted" style={{ fontWeight: 400, fontSize: 13 }}>
                          {c.address}
                        </div>
                      )}
                    </td>
                    <td className="num" data-label="Téléphone">
                      {c.phone}
                    </td>
                    <td className="num" data-label="IFU">
                      {c.ifu ?? <span className="faint">Non renseigné</span>}
                    </td>
                    <td data-label="RCCM">{c.rccm}</td>
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