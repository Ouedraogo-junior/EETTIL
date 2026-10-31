import { formatXOF, formatNumber } from '@/core/lib/format'
import type { DocumentTotals } from './types'

interface Props {
  totals?: DocumentTotals
  vatEnabled: boolean
  vatRate: number
  /** Précision affichée à côté de « Remise globale », ex. « 10 % ». */
  discountNote?: string
}

export function TotalsBox({ totals, vatEnabled, vatRate, discountNote }: Props) {
  const m = (n?: number) => (n === undefined ? '…' : formatXOF(n))
  return (
    <div className="dsum">
      <div className="r">
        <span>A. Débours</span>
        <b>{m(totals?.debours)}</b>
      </div>
      <div className="r">
        <span>B. Prestations (après remises de ligne)</span>
        <b>{m(totals?.prestations_subtotal)}</b>
      </div>
      {!!totals?.global_discount && (
        <div className="r">
          <span>Remise globale{discountNote ? ` (${discountNote})` : ''}</span>
          <b>− {m(totals.global_discount)}</b>
        </div>
      )}
      <div className="r">
        <span>Prestations HT</span>
        <b>{m(totals?.prestations_ht)}</b>
      </div>
      {vatEnabled && (
        <div className="r">
          <span>TVA ({formatNumber(vatRate)} %)</span>
          <b>{m(totals?.vat)}</b>
        </div>
      )}
      <div className="r">
        <span>Total B{vatEnabled ? ' (TTC)' : ''}</span>
        <b>{m(totals?.prestations_total)}</b>
      </div>
      <div className="r big">
        <span>TOTAL (A + B)</span>
        <span className="num">{m(totals?.total)}</span>
      </div>
      {totals && <div className="words">{totals.total_in_words}</div>}
    </div>
  )
}