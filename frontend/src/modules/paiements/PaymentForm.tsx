import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { apiError, fieldErrors } from '@/core/api/errors'
import { formatFcfa, formatNumber, parseAmount, todayISO } from '@/core/lib/format'
import { Field } from '@/core/ui/Field'
import { Modal } from '@/core/ui/Modal'
import { useToast } from '@/core/ui/toast-context'
import { usePaymentOptions, useRecordPayment } from './hooks'
import type { InvoiceSummary, PaymentModeValue, PaymentOptions } from './types'

interface Props {
  invoice: InvoiceSummary
  onClose: () => void
}

/** Enregistrement d'un paiement sur une facture : le formulaire s'ouvre dès que les comptes sont chargés. */
export function PaymentForm({ invoice, onClose }: Props) {
  const { data: options, isError, error } = usePaymentOptions()

  if (!options) {
    return (
      <Modal title={`Paiement – ${invoice.number}`} onClose={onClose}>
        {isError ? <p className="warn">{apiError(error)}</p> : <p>Chargement…</p>}
      </Modal>
    )
  }
  return <PaymentFormInner invoice={invoice} options={options} onClose={onClose} />
}

const FIELDS = ['paid_on', 'amount', 'mode', 'account_id', 'reference'] as const

function PaymentFormInner({ invoice, options, onClose }: Props & { options: PaymentOptions }) {
  const toast = useToast()
  const record = useRecordPayment()
  const [formError, setFormError] = useState<string | null>(null)
  const rest = invoice.payment.rest

  const accountFor = (mode: string) => {
    const wanted = options.modes.find((m) => m.value === mode)?.account_type
    return options.accounts.find((a) => a.type === wanted) ?? options.accounts[0]
  }

  const schema = z.object({
    paid_on: z
      .string()
      .min(1, 'Indiquez la date du paiement.')
      .refine((v) => v <= todayISO(), 'La date du paiement ne peut pas être dans le futur.'),
    amount: z.string().superRefine((v, ctx) => {
      const n = parseAmount(v)
      if (n === null || n < 1) ctx.addIssue({ code: 'custom', message: 'Saisissez un montant en francs, supérieur à zéro.' })
      else if (n > rest) ctx.addIssue({ code: 'custom', message: `Ce montant dépasse le reste à payer (${formatFcfa(rest)}).` })
    }),
    mode: z.string().min(1, 'Choisissez le mode de paiement.'),
    account_id: z.string().min(1, 'Choisissez le compte crédité.'),
    reference: z.string().max(100, 'Trop long (100 caractères maximum).'),
  })
  type Values = z.infer<typeof schema>

  const firstMode = options.modes[0]?.value ?? 'especes'
  const {
    register,
    handleSubmit,
    setError,
    setValue,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: {
      paid_on: todayISO(),
      amount: String(rest),
      mode: firstMode,
      account_id: String(accountFor(firstMode)?.id ?? ''),
      reference: '',
    },
  })

  const modeField = register('mode')

  const onSubmit = async (v: Values) => {
    setFormError(null)
    try {
      const result = await record.mutateAsync({
        documentId: invoice.id,
        input: {
          paid_on: v.paid_on,
          amount: parseAmount(v.amount) ?? 0,
          mode: v.mode as PaymentModeValue,
          account_id: Number(v.account_id),
          reference: v.reference,
        },
      })
      toast(result.invoice.state === 'settled' ? 'Paiement enregistré : la facture est soldée.' : 'Paiement enregistré.')
      onClose()
    } catch (e) {
      const server = fieldErrors(e)
      if (server) {
        let shown = false
        for (const [field, message] of Object.entries(server)) {
          if ((FIELDS as readonly string[]).includes(field)) {
            setError(field as keyof Values, { message })
            shown = true
          } else {
            setFormError(message)
            shown = true
          }
        }
        if (!shown) setFormError(apiError(e))
      } else {
        setFormError(apiError(e))
      }
    }
  }

  const cls = (name: keyof Values) => 'inp' + (errors[name] ? ' err' : '')

  return (
    <Modal
      title={`Paiement – ${invoice.number}`}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn" onClick={onClose}>
            Annuler
          </button>
          <button type="submit" form="payment-form" className="btn primary" disabled={record.isPending}>
            {record.isPending ? 'Enregistrement…' : 'Enregistrer le paiement'}
          </button>
        </>
      }
    >
      <form id="payment-form" onSubmit={handleSubmit(onSubmit)} noValidate>
        <p className="pay-recap">
          {invoice.dossier?.client?.name && <b>{invoice.dossier.client.name}</b>}
          <span>
            Facture {formatFcfa(invoice.total)} · déjà payé {formatFcfa(invoice.payment.paid)} · <b>reste {formatFcfa(rest)}</b>
          </span>
        </p>

        <Field id="amount" label="Montant reçu (FCFA) *" error={errors.amount?.message}>
          <div className="amount-row">
            <input id="amount" inputMode="numeric" autoComplete="off" className={cls('amount')} {...register('amount')} />
            <button type="button" className="btn sm" onClick={() => setValue('amount', String(rest), { shouldValidate: true })}>
              Solde : {formatNumber(rest)}
            </button>
          </div>
        </Field>

        <div className="row2">
          <Field id="paid_on" label="Date du paiement *" error={errors.paid_on?.message}>
            <input id="paid_on" type="date" max={todayISO()} className={cls('paid_on')} {...register('paid_on')} />
          </Field>
          <Field id="mode" label="Mode de paiement *" error={errors.mode?.message}>
            <select
              id="mode"
              className="sel"
              {...modeField}
              onChange={(e) => {
                modeField.onChange(e)
                const account = accountFor(e.target.value)
                if (account) setValue('account_id', String(account.id))
              }}
            >
              {options.modes.map((m) => (
                <option key={m.value} value={m.value}>
                  {m.label}
                </option>
              ))}
            </select>
          </Field>
        </div>

        <Field
          id="account_id"
          label="Compte crédité *"
          hint="Le compte est proposé selon le mode (espèces : caisse, virement : banque). Vous pouvez le changer."
          error={errors.account_id?.message}
        >
          <select id="account_id" className="sel" {...register('account_id')}>
            {options.accounts.map((a) => (
              <option key={a.id} value={a.id}>
                {a.name} · solde {formatNumber(a.balance)}
              </option>
            ))}
          </select>
        </Field>

        <Field
          id="reference"
          label="Référence"
          hint="N° de virement, de transaction ou de chèque. Laissez vide : un numéro de reçu (REC-0001…) est créé."
          error={errors.reference?.message}
        >
          <input id="reference" className={cls('reference')} {...register('reference')} />
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