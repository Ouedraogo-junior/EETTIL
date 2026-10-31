import { useState } from 'react'
import { apiError } from '@/core/api/errors'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { Pagination } from '@/core/ui/Pagination'
import { DocumentsTable } from './DocumentsTable'
import { useDocuments } from './hooks'

export default function DocumentsListPage() {
  const [input, setInput] = useState('')
  const [type, setType] = useState('')
  const [state, setState] = useState('')
  const [page, setPage] = useState(1)
  const q = useDebouncedValue(input.trim())

  const { data, isPending, isError, error, refetch } = useDocuments({
    q: q || undefined,
    type: type || undefined,
    state: state || undefined,
    page,
  })

  return (
    <>
      <div className="ph">
        <div>
          <h1>Factures et quotations</h1>
          <div className="sub">Pour créer un document, ouvrez le dossier concerné puis l’onglet « Documents ».</div>
        </div>
      </div>

      <div className="panel flush">
        <div className="toolbar">
          <input
            type="search"
            className="inp"
            placeholder="Rechercher par numéro, dossier ou client"
            aria-label="Rechercher un document"
            value={input}
            onChange={(e) => {
              setInput(e.target.value)
              setPage(1)
            }}
          />
          <select
            className="sel"
            aria-label="Filtrer par type"
            value={type}
            onChange={(e) => {
              setType(e.target.value)
              setPage(1)
            }}
          >
            <option value="">Tous les types</option>
            <option value="facture">Factures</option>
            <option value="proforma">Quotations</option>
          </select>
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
            <option value="emis">Émises</option>
            <option value="annule">Annulées</option>
            <option value="transforme">Transformées</option>
          </select>
        </div>

        {isPending ? (
          <div className="empty">Chargement…</div>
        ) : isError ? (
          <div className="empty">
            <b>Chargement impossible</b>
            {apiError(error)}
            <div style={{ marginTop: 12 }}>
              <button className="btn sm" onClick={() => refetch()}>
                Réessayer
              </button>
            </div>
          </div>
        ) : data.data.length === 0 ? (
          <div className="empty">
            <b>Aucun document</b>
            {q || type || state ? 'Aucun résultat pour ces critères.' : 'Les documents créés apparaîtront ici.'}
          </div>
        ) : (
          <>
            <DocumentsTable documents={data.data} showDossier />
            <Pagination page={data.meta.current_page} lastPage={data.meta.last_page} total={data.meta.total} onChange={setPage} />
          </>
        )}
      </div>
    </>
  )
}