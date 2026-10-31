interface Props {
  page: number
  lastPage: number
  total: number
  onChange: (page: number) => void
}

export function Pagination({ page, lastPage, total, onChange }: Props) {
  if (lastPage <= 1) return null
  return (
    <div className="pager">
      <span>
        {total} résultats · page {page} sur {lastPage}
      </span>
      <div className="btns">
        <button className="btn sm" disabled={page <= 1} onClick={() => onChange(page - 1)}>
          Précédent
        </button>
        <button className="btn sm" disabled={page >= lastPage} onClick={() => onChange(page + 1)}>
          Suivant
        </button>
      </div>
    </div>
  )
}