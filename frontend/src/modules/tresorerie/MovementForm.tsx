import { useState } from 'react'
import { Controller, useForm, useWatch } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { apiError, fieldErrors } from '@/core/api/errors'
import { formatFcfa, formatNumber, parseAmount, todayISO } from '@/core/lib/format'
import { Field } from '@/core/ui/Field'
import { Modal } from '@/core/ui/Modal'
import { useToast } from '@/core/ui/toast-context'
import { DossierPicker } from '@/modules/dossiers/DossierPicker'
import { useMovementOptions, useRecordMovement } from './hooks'
import type { Direction, MovementOptions } from './types'

interface Props {
  direction: Direction
  onClose: () => void
}

/** Saisie d'une entrée ou d'une sortie d'argent. Les paiements clients se saisissent depuis « Paiements ». */
export function MovementForm({ direction, onClose }: Props) {
  const { data: options, isError, error } = useMovementOptions()
  const title = direction === 'in' ? 'Nouvelle entrée d’argent' : 'Nouvelle sortie d’argent'

  if (!options) {
    return (
      <Modal title={title} onClose={onClose}>
        {isError ? <p className="warn">{apiError(error)}</p> : <p>Chargement…</p>}
      </Modal>
    )
  }
  return <MovementFormInner direction={direction} options={options} title={title} onClose={onClose} />
}

const FIELDS = ['moved_on', 'label', 'category_id', 'amount', 'account_id', 'dossier_id'] as const

function MovementFormInner({
  direction,
  options,
  title,
  onClose,
}: Props & { options: MovementOptions; title: string }) {
  const toast = useToast()
  const record = useRecordMovement()
  const [formError, setFormError] = useState<string | null>(null)

  // Les catégories « système » (ex. Paiement client) ne se saisissent pas à la main.
  const categories = options.categories[direction].filter((c) => !c.is_system)
  const out = direction === 'out'

  const schema = z
    .object({
      moved_on: z
        .string()
        .min(1, 'Indiquez la date.')
        .refine((v) => v <= todayISO(), 'La date ne peut pas être dans le futur.'),
      label: z.string().refine((v) => v.trim().length > 0, 'Saisissez un libellé.').refine((v) => v.length <= 255, 'Trop long (255 caractères maximum).'),
      category_id: z.string().min(1, 'Choisissez une catégorie.'),
      amount: z.string().refine((v) => {
        const n = parseAmount(v)
        return n !== null && n >= 1
      }, 'Saisissez un montant en francs, supérieur à zéro.'),
      account_id: z.string().min(1, 'Choisissez un compte.'),
      dossier_id: z.string(),
    })
    .superRefine((v, ctx) => {
      const cat = categories.find((c) => String(c.id) === v.category_id)
      if (cat?.is_debours && !v.dossier_id) {
        ctx.addIssue({ code: 'custom', path: ['dossier_id'], message: 'Un débours doit être rattaché à un dossier.' })
      }
    })
  type Values = z.infer<typeof schema>

  const {
    register,
    control,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: {
      moved_on: todayISO(),
      label: '',
      category_id: '',
      amount: '',
      account_id: String(options.accounts[0]?.id ?? ''),
      dossier_id: '',
    },
  })

  const categoryId = useWatch({ control, name: 'category_id' })
  const accountId = useWatch({ control, name: 'account_id' })
  const amountText = useWatch({ control, name: 'amount' })
  const category = categories.find((c) => String(c.id) === categoryId)
  const account = options.accounts.find((a) => String(a.id) === accountId)
  const amount = parseAmount(amountText)
  const lowCash = out && account?.type === 'caisse' && amount !== null && amount > account.balance

  const onSubmit = async (v: Values) => {
    setFormError(null)
    try {
      await record.mutateAsync({
        direction,
        moved_on: v.moved_on,
        label: v.label.trim(),
        category_id: Number(v.category_id),
        amount: parseAmount(v.amount) ?? 0,
        account_id: Number(v.account_id),
        dossier_id: v.dossier_id ? Number(v.dossier_id) : null,
      })
      toast(out ? 'Sortie enregistrée.' : 'Entrée enregistrée.')
      onClose()
    } catch (e) {
      const server = fieldErrors(e)
      if (server) {
        let other: string | null = null
        for (const [field, message] of Object.entries(server)) {
          if ((FIELDS as readonly string[]).includes(field)) setError(field as keyof Values, { message })
          else other = message
        }
        setFormError(other ?? 'Corrigez les champs signalés en rouge.')
      } else {
        setFormError(apiError(e))
      }
    }
  }

  const cls = (name: keyof Values) => 'inp' + (errors[name] ? ' err' : '')

  return (
    <Modal
      title={title}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn" onClick={onClose}>
            Annuler
          </button>
          <button type="submit" form="movement-form" className="btn primary" disabled={record.isPending}>
            {record.isPending ? 'Enregistrement…' : 'Enregistrer'}
          </button>
        </>
      }
    >
      <form id="movement-form" onSubmit={handleSubmit(onSubmit)} noValidate>
        <div className="row2">
          <Field id="moved_on" label="Date *" error={errors.moved_on?.message}>
            <input id="moved_on" type="date" max={todayISO()} className={cls('moved_on')} {...register('moved_on')} />
          </Field>
          <Field id="amount" label="Montant (FCFA) *" error={errors.amount?.message}>
            <input id="amount" inputMode="numeric" autoComplete="off" className={cls('amount')} {...register('amount')} />
          </Field>
        </div>

        <Field id="account_id" label={out ? 'Payé depuis *' : 'Versé sur *'} error={errors.account_id?.message}>
          <select id="account_id" className="sel" {...register('account_id')}>
            {options.accounts.map((a) => (
              <option key={a.id} value={a.id}>
                {a.name} · solde {formatNumber(a.balance)}
              </option>
            ))}
          </select>
        </Field>
        {lowCash && account && (
          <p className="warn" role="alert">
            La caisse ne contient que {formatFcfa(account.balance)} : cette sortie sera refusée. Choisissez la banque ou
            enregistrez d’abord une entrée dans la caisse.
          </p>
        )}

        <Field id="category_id" label="Catégorie *" error={errors.category_id?.message}>
          <select id="category_id" className="sel" {...register('category_id')}>
            <option value="">Choisir…</option>
            {categories.map((c) => (
              <option key={c.id} value={c.id}>
                {c.name}
              </option>
            ))}
          </select>
        </Field>

        <Field id="label" label="Libellé *" hint="Ex. : Carburant camion, Frais de port MSKU…" error={errors.label?.message}>
          <input id="label" className={cls('label')} {...register('label')} />
        </Field>

        <div className="field">
          <label htmlFor="dossier_id">Dossier{category?.is_debours ? ' *' : ' (facultatif)'}</label>
          <Controller
            control={control}
            name="dossier_id"
            render={({ field }) => <DossierPicker value={field.value} onChange={field.onChange} invalid={!!errors.dossier_id} />}
          />
          {category?.is_debours && !errors.dossier_id && (
            <span className="hint">
              Un débours est une somme avancée pour le compte du client : il doit être rattaché à son dossier pour être refacturé.
            </span>
          )}
          {!category?.is_debours && !errors.dossier_id && out && (
            <span className="hint">Rattachez la dépense à un dossier pour qu’elle compte dans sa marge.</span>
          )}
          {errors.dossier_id && <span className="errmsg">{errors.dossier_id.message}</span>}
        </div>

        {formError && (
          <p className="warn" role="alert">
            {formError}
          </p>
        )}
      </form>
    </Modal>
  )
}