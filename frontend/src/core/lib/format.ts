const nf = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 })

/** 1234567 -> « 1 234 567 FCFA » (espaces insécables, pas de décimales). */
export const formatXOF = (n: number) => nf.format(n).replace(/[\u202f\u00a0]/g, '\u00a0') + '\u00a0FCFA'

/** '2026-10-31' ou ISO complet -> « 31/10/2026 » */
export function formatDate(iso: string | null | undefined): string {
  if (!iso) return '—'
  const [y, m, d] = iso.slice(0, 10).split('-')
  return `${d}/${m}/${y}`
}

const nf2 = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 })

/** 24000 -> « 24 000 », 620.5 -> « 620,5 » (espaces insécables). */
export const formatNumber = (n: number) => nf2.format(n).replace(/[\u202f\u00a0]/g, '\u00a0')

/** Date du jour au format AAAA-MM-JJ, à l'heure locale du navigateur. */
export function todayISO(): string {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}