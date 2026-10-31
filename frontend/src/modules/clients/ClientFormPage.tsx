import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { apiError, fieldErrors } from '@/core/api/errors'
import { Field } from '@/core/ui/Field'
import { useToast } from '@/core/ui/toast-context'
import { useClient, useSaveClient } from './hooks'
import type { Client } from './types'

const schema = z.object({
  name: z.string().min(1, 'Le nom est obligatoire.').max(150, 'Trop long (150 caractères maximum).'),
  phone: z.string().max(50, 'Trop long (50 caractères maximum).'),
  email: z
    .string()
    .max(150, 'Trop long (150 caractères maximum).')
    .refine((v) => v === '' || /^\S+@\S+\.\S+$/.test(v), 'Adresse e-mail invalide.'),
  address: z.string().max(255, 'Trop long (255 caractères maximum).'),
  ifu: z.string().max(50, 'Trop long (50 caractères maximum).'),
  rccm: z.string().max(100, 'Trop long (100 caractères maximum).'),
  notes: z.string().max(2000, 'Trop long (2000 caractères maximum).'),
})
type Values = z.infer<typeof schema>

const EMPTY: Values = { name: '', phone: '', email: '', address: '', ifu: '', rccm: '', notes: '' }

const toValues = (c: Client): Values => ({
  name: c.name,
  phone: c.phone ?? '',
  email: c.email ?? '',
  address: c.address ?? '',
  ifu: c.ifu ?? '',
  rccm: c.rccm ?? '',
  notes: c.notes ?? '',
})

export default function ClientFormPage() {
  const { id } = useParams()
  const { data: client, isPending, isError } = useClient(id)

  if (id && isPending) return <div className="empty">Chargement…</div>
  if (id && (isError || !client)) {
    return (
      <div className="panel">
        <div className="empty">
          <b>Client introuvable</b>
          <Link to="/clients">Retour à la liste</Link>
        </div>
      </div>
    )
  }
  return <ClientForm client={id ? client : undefined} />
}

function ClientForm({ client }: { client?: Client }) {
  const navigate = useNavigate()
  const toast = useToast()
  const save = useSaveClient()
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<Values>({ resolver: zodResolver(schema), defaultValues: client ? toValues(client) : EMPTY })

  const onSubmit = async (values: Values) => {
    setFormError(null)
    try {
      const saved = await save.mutateAsync({ id: client?.id, input: values })
      toast(client ? 'Client modifié' : 'Client ajouté')
      navigate(`/clients/${saved.id}`, { replace: true })
    } catch (e) {
      const serverErrors = fieldErrors(e)
      if (serverErrors) {
        const known = Object.keys(schema.shape)
        for (const [field, message] of Object.entries(serverErrors)) {
          if (known.includes(field)) setError(field as keyof Values, { message })
        }
        setFormError('Corrigez les champs signalés en rouge.')
      } else {
        setFormError(apiError(e))
      }
    }
  }

  const cancelTo = client ? `/clients/${client.id}` : '/clients'
  const cls = (name: keyof Values) => 'inp' + (errors[name] ? ' err' : '')

  return (
    <>
      <div>
        <Link to={cancelTo} className="btn link backlink">
          ← {client ? 'Retour à la fiche' : 'Clients'}
        </Link>
      </div>
      <div className="ph">
        <h1>{client ? 'Modifier le client' : 'Nouveau client'}</h1>
      </div>

      <form className="panel" onSubmit={handleSubmit(onSubmit)} noValidate style={{ maxWidth: 760 }}>
        <div className="bd">
          <Field id="name" label="Nom du client *" error={errors.name?.message}>
            <input id="name" className={cls('name')} autoComplete="off" autoFocus {...register('name')} />
          </Field>

          <div className="row2">
            <Field id="phone" label="Téléphone" error={errors.phone?.message}>
              <input id="phone" type="tel" inputMode="tel" className={cls('phone')} {...register('phone')} />
            </Field>
            <Field id="email" label="E-mail" error={errors.email?.message}>
              <input
                id="email"
                type="email"
                inputMode="email"
                autoCapitalize="none"
                className={cls('email')}
                {...register('email')}
              />
            </Field>
          </div>

          <Field id="address" label="Adresse" error={errors.address?.message}>
            <input id="address" className={cls('address')} {...register('address')} />
          </Field>

          <div className="row2">
            <Field
              id="ifu"
              label="IFU"
              error={errors.ifu?.message}
              hint="Imprimé sous le nom du client sur la facture."
            >
              <input id="ifu" autoCapitalize="characters" className={cls('ifu')} {...register('ifu')} />
            </Field>
            <Field id="rccm" label="RCCM" error={errors.rccm?.message}>
              <input id="rccm" className={cls('rccm')} {...register('rccm')} />
            </Field>
          </div>

          <Field id="notes" label="Notes" error={errors.notes?.message}>
            <textarea id="notes" rows={4} className="txt" {...register('notes')} />
          </Field>

          {formError && (
            <p className="warn" role="alert">
              {formError}
            </p>
          )}
        </div>
        <div className="ft" style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, padding: '14px 18px', borderTop: '1px solid var(--line-2)' }}>
          <Link to={cancelTo} className="btn">
            Annuler
          </Link>
          <button type="submit" className="btn primary" disabled={save.isPending}>
            {save.isPending ? 'Enregistrement…' : 'Enregistrer'}
          </button>
        </div>
      </form>
    </>
  )
}