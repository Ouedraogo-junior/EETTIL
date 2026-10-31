import { z } from 'zod'
import { todayISO } from '@/core/lib/format'
import type { Block, BillingDocument, DocumentInput, DocumentOptions, PreviewInput } from './types'

const clean = (s: string) => s.replace(/[\s\u00a0\u202f]/g, '').replace(',', '.')
export const parse = (s: string): number => {
  const n = Number(clean(s))
  return Number.isFinite(n) ? n : 0
}
const isNumber = (s: string) => clean(s) !== '' && Number.isFinite(Number(clean(s)))
const isEmpty = (s: string) => clean(s) === ''
const isCount = (s: string) => isNumber(s) && Number.isInteger(parse(s)) && parse(s) >= 0

const kind = z.enum(['', 'pct', 'amt'])

const lineSchema = z.object({
  block: z.enum(['D', 'P']),
  label: z
    .string()
    .refine((s) => s.trim().length > 0, 'Saisissez une désignation.')
    .refine((s) => s.length <= 255, 'Trop long (255 caractères maximum).'),
  unit: z.string(),
  quantity: z.string().refine((s) => isNumber(s) && parse(s) > 0, 'Quantité supérieure à zéro.'),
  unit_price: z.string().refine((s) => isCount(s), 'Montant entier en FCFA.'),
  discount_kind: kind,
  discount_value: z.string().refine((s) => isEmpty(s) || (isNumber(s) && parse(s) >= 0), 'Remise invalide.'),
})

export const formFields = z.object({
  issued_on: z
    .string()
    .min(1, 'Indiquez la date.')
    .refine((s) => s <= todayISO(), 'La date ne peut pas être dans le futur.'),
  currency: z.enum(['XOF', 'EUR', 'USD']),
  exchange_rate: z.string(),
  caf_value: z.string(),
  containers_20: z.string(),
  containers_40: z.string(),
  vat_enabled: z.boolean(),
  vat_rate: z.string(),
  global_discount_kind: kind,
  global_discount_value: z.string(),
  lines: z.array(lineSchema).min(1, 'Ajoutez au moins une ligne.'),
})

export type FormValues = z.infer<typeof formFields>

export const formSchema = formFields.superRefine((v, ctx) => {
  const bad = (path: (string | number)[], message: string) => ctx.addIssue({ code: 'custom', path, message })

  if (v.currency !== 'XOF' && !(isNumber(v.exchange_rate) && parse(v.exchange_rate) > 0)) {
    bad(['exchange_rate'], 'Indiquez le taux de change.')
  }
  if (v.vat_enabled && !(isNumber(v.vat_rate) && parse(v.vat_rate) >= 0 && parse(v.vat_rate) <= 100)) {
    bad(['vat_rate'], 'Taux entre 0 et 100.')
  }
  for (const f of ['caf_value', 'containers_20', 'containers_40'] as const) {
    if (!isEmpty(v[f]) && !isCount(v[f])) bad([f], 'Nombre entier positif.')
  }
  if (v.global_discount_kind && !isEmpty(v.global_discount_value)) {
    if (!isNumber(v.global_discount_value) || parse(v.global_discount_value) < 0) {
      bad(['global_discount_value'], 'Remise invalide.')
    } else if (v.global_discount_kind === 'pct' && parse(v.global_discount_value) > 100) {
      bad(['global_discount_value'], 'Maximum 100 %.')
    }
  }
})

export const newLine = (block: Block, label = ''): FormValues['lines'][number] => ({
  block,
  label,
  unit: 'ea',
  quantity: '1',
  unit_price: '',
  discount_kind: '',
  discount_value: '',
})

const text = (n: number | null | undefined) => (n ? String(n).replace('.', ',') : '')

export function makeDefaults(options: DocumentOptions, doc?: BillingDocument): FormValues {
  if (!doc) {
    return {
      issued_on: todayISO(),
      currency: 'XOF',
      exchange_rate: '',
      caf_value: '',
      containers_20: '',
      containers_40: '',
      vat_enabled: false,
      vat_rate: String(options.vat_rate),
      global_discount_kind: '',
      global_discount_value: '',
      lines: [],
    }
  }
  return {
    issued_on: doc.issued_on,
    currency: doc.currency as FormValues['currency'],
    exchange_rate: text(doc.exchange_rate),
    caf_value: text(doc.caf_value),
    containers_20: text(doc.containers_20),
    containers_40: text(doc.containers_40),
    vat_enabled: doc.vat_enabled,
    vat_rate: String(doc.vat_rate).replace('.', ','),
    global_discount_kind: doc.global_discount.kind ?? '',
    global_discount_value: doc.global_discount.kind ? text(doc.global_discount.value) : '',
    lines: (doc.lines ?? []).map((l) => ({
      block: l.block,
      label: l.label,
      unit: l.unit,
      quantity: String(l.quantity).replace('.', ','),
      unit_price: String(l.unit_price),
      discount_kind: l.discount_kind ?? '',
      discount_value: l.discount_kind ? text(l.discount_value) : '',
    })),
  }
}

const toLines = (v: FormValues): DocumentInput['lines'] =>
  (v.lines ?? []).map((l) => {
    const discounted = l.block === 'P' && l.discount_kind !== ''
    return {
      block: l.block,
      label: l.label.trim(),
      unit: l.unit.trim() || 'ea',
      quantity: Math.min(parse(l.quantity), 9_999_999),
      unit_price: Math.min(Math.max(Math.round(parse(l.unit_price)), 0), 100_000_000_000),
      discount_kind: discounted ? (l.discount_kind as 'pct' | 'amt') : null,
      discount_value: discounted ? Math.max(parse(l.discount_value), 0) : 0,
    }
  })

/** Saisie nécessaire au calcul en direct (le serveur calcule, l'écran n'applique aucune règle). */
export function toPreview(v: FormValues): PreviewInput {
  return {
    vat_enabled: !!v.vat_enabled,
    vat_rate: Math.min(Math.max(parse(v.vat_rate), 0), 100),
    global_discount_kind: v.global_discount_kind === '' ? null : v.global_discount_kind,
    global_discount_value: v.global_discount_kind === '' ? 0 : Math.max(parse(v.global_discount_value), 0),
    lines: toLines(v),
  }
}

export function toPayload(v: FormValues): DocumentInput {
  return {
    ...toPreview(v),
    issued_on: v.issued_on,
    currency: v.currency,
    exchange_rate: v.currency === 'XOF' ? null : parse(v.exchange_rate),
    caf_value: Math.round(parse(v.caf_value)),
    containers_20: Math.round(parse(v.containers_20)),
    containers_40: Math.round(parse(v.containers_40)),
  }
}