import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { apiError, fieldErrors } from '@/core/api/errors'
import { todayISO } from '@/core/lib/format'
import { Field } from '@/core/ui/Field'
import { Modal } from '@/core/ui/Modal'
import { useToast } from '@/core/ui/toast-context'
import { useDossierOptions } from '@/modules/dossiers/hooks'
import type { Dossier } from '@/modules/dossiers/types'
import { useSaveTracking } from './hooks'
import type { Tracking } from './types'

const FUTURE = 'On ne saisit que des dates réelles, pas de date à venir.'
const realDate = z.string().refine((v) => v === '' || v <= todayISO(), FUTURE)

const fields = z.object({
  supplier_invoices: z.string().max(500, 'Trop long (500 caractères maximum).'),
  dpi_number: z.string().max(50, 'Trop long (50 caractères maximum).'),
  dpi_date: realDate,
  bl_lta: z.string().max(100, 'Trop long (100 caractères maximum).'),
  transit_order_number: z.string().max(100, 'Trop long (100 caractères maximum).'),
  regime: z.string(),
  nature: z
    .string()
    .refine((v) => v.trim().length > 0, 'Indiquez la nature de la marchandise.')
    .refine((v) => v.length <= 255, 'Trop long (255 caractères maximum).'),
  packages: z.string().refine((v) => /^\d+$/.test(v.trim()) && Number(v) > 0, 'Indiquez le nombre de colis (entier, au moins 1).'),
  weight_kg: z.string().refine((v) => {
    const n = Number(v.replace(',', '.'))
    return v.trim() !== '' && Number.isFinite(n) && n > 0
  }, 'Indiquez le poids en kilogrammes.'),
  date_ot: realDate,
  date_arrival: realDate,
  date_docs: realDate,
  date_declaration: realDate,
  date_bae: realDate,
  date_delivery: realDate,
  comments: z.string().max(2000, 'Trop long (2000 caractères maximum).'),
})
const schema = fields.superRefine((v, ctx) => {
  if (v.dpi_date && !v.dpi_number.trim()) {
    ctx.addIssue({ code: 'custom', path: ['dpi_number'], message: 'Saisissez le numéro de la DPI, ou retirez sa date.' })
  }
})
type Values = z.infer<typeof fields>

interface Props {
  dossier: Dossier
  tracking: Tracking
  onClose: () => void
}

export function TrackingForm({ dossier, tracking, onClose }: Props) {
  const toast = useToast()
  const { data: options } = useDossierOptions()
  const save = useSaveTracking(dossier.id)
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: {
      supplier_invoices: tracking.supplier_invoices ?? '',
      dpi_number: tracking.dpi_number ?? '',
      dpi_date: tracking.dpi_date ?? '',
      bl_lta: tracking.bl_lta ?? '',
      transit_order_number: tracking.transit_order_number ?? '',
      regime: tracking.regime ?? '',
      nature: tracking.nature ?? '',
      packages: tracking.packages !== null ? String(tracking.packages) : '',
      weight_kg: tracking.weight_kg !== null ? String(tracking.weight_kg) : '',
      date_ot: tracking.date_ot ?? '',
      date_arrival: tracking.date_arrival ?? '',
      date_docs: tracking.date_docs ?? '',
      date_declaration: tracking.date_declaration ?? '',
      date_bae: tracking.date_bae ?? '',
      date_delivery: tracking.date_delivery ?? '',
      comments: tracking.comments ?? '',
    },
  })

  const onSubmit = async (v: Values) => {
    setFormError(null)
    try {
      await save.mutateAsync({
        supplier_invoices: v.supplier_invoices.trim(),
        dpi_number: v.dpi_number.trim(),
        dpi_date: v.dpi_date,
        bl_lta: v.bl_lta.trim(),
        transit_order_number: v.transit_order_number.trim(),
        regime: v.regime,
        nature: v.nature.trim(),
        packages: Number(v.packages),
        weight_kg: Number(v.weight_kg.replace(',', '.')),
        date_ot: v.date_ot,
        date_arrival: v.date_arrival,
        date_docs: v.date_docs,
        date_declaration: v.date_declaration,
        date_bae: v.date_bae,
        date_delivery: v.date_delivery,
        comments: v.comments.trim(),
      })
      toast('Suivi enregistré')
      onClose()
    } catch (e) {
      const serverErrors = fieldErrors(e)
      if (serverErrors) {
        const known = Object.keys(fields.shape)
        for (const [field, message] of Object.entries(serverErrors)) {
          if (known.includes(field)) setError(field as keyof Values, { message })
        }
        setFormError('Corrigez les champs signalés en rouge.')
      } else {
        setFormError(apiError(e))
      }
    }
  }

  const cls = (name: keyof Values) => 'inp' + (errors[name] ? ' err' : '')

  return (
    <Modal
      title={`Suivi du dossier ${dossier.number}`}
      onClose={onClose}
      wide
      footer={
        <>
          <button type="button" className="btn" onClick={onClose}>
            Annuler
          </button>
          <button type="submit" form="tracking-form" className="btn primary" disabled={save.isPending}>
            {save.isPending ? 'Enregistrement…' : 'Enregistrer le suivi'}
          </button>
        </>
      }
    >
      <form id="tracking-form" onSubmit={handleSubmit(onSubmit)} noValidate>
        <p className="hint" style={{ marginTop: 0 }}>
          {dossier.client?.name} · les champs marqués d’un astérisque sont obligatoires.
        </p>

        <div className="row2">
          <Field
            id="supplier_invoices"
            label="Facture(s) fournisseur"
            hint="Plusieurs numéros : séparez-les par « ; »"
            error={errors.supplier_invoices?.message}
          >
            <input id="supplier_invoices" className={cls('supplier_invoices')} {...register('supplier_invoices')} />
          </Field>
          <Field id="bl_lta" label="BL / LTA" error={errors.bl_lta?.message}>
            <input id="bl_lta" className={cls('bl_lta')} {...register('bl_lta')} />
          </Field>
        </div>

        <div className="row2">
          <Field id="dpi_number" label="N° de DPI (déclaration préalable d’importation)" error={errors.dpi_number?.message}>
            <input id="dpi_number" className={cls('dpi_number')} {...register('dpi_number')} />
          </Field>
          <Field id="dpi_date" label="Date de la DPI" error={errors.dpi_date?.message}>
            <input id="dpi_date" type="date" max={todayISO()} className={cls('dpi_date')} {...register('dpi_date')} />
          </Field>
        </div>

        <div className="row2">
          <Field id="transit_order_number" label="N° d’ordre de transit" error={errors.transit_order_number?.message}>
            <input id="transit_order_number" className={cls('transit_order_number')} {...register('transit_order_number')} />
          </Field>
          <Field id="regime" label="Régime douanier" error={errors.regime?.message}>
            <select id="regime" className="sel" {...register('regime')}>
              <option value="">Non précisé</option>
              {options?.regimes.map((r) => (
                <option key={r} value={r}>
                  {r}
                </option>
              ))}
            </select>
          </Field>
        </div>

        <Field id="nature" label="Nature de la marchandise *" error={errors.nature?.message}>
          <input id="nature" className={cls('nature')} {...register('nature')} />
        </Field>

        <div className="row2">
          <Field id="packages" label="Nombre de colis *" error={errors.packages?.message}>
            <input id="packages" inputMode="numeric" className={cls('packages')} {...register('packages')} />
          </Field>
          <Field id="weight_kg" label="Poids (kg) *" error={errors.weight_kg?.message}>
            <input id="weight_kg" inputMode="decimal" className={cls('weight_kg')} {...register('weight_kg')} />
          </Field>
        </div>

        <div className="lbl" style={{ margin: '6px 0 4px' }}>
          Dates réelles
        </div>
        <p className="hint" style={{ margin: '0 0 10px' }}>
          Saisissez la date le jour où l’étape est faite. Laissez vide si elle n’est pas encore faite.
        </p>
        <div className="row2">
          {tracking.steps.map((s) => (
            <Field key={s.key} id={s.key} label={s.label} error={errors[s.key]?.message}>
              <input id={s.key} type="date" max={todayISO()} className={cls(s.key)} {...register(s.key)} />
            </Field>
          ))}
        </div>

        <Field id="comments" label="Commentaires" error={errors.comments?.message}>
          <textarea id="comments" rows={3} className="txt" {...register('comments')} />
        </Field>

        {formError && (
          <p className="warn" role="alert">
            {formError}
          </p>
        )}
      </form>
    </Modal>
  )
}