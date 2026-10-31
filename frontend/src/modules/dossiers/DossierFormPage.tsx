import { useState } from 'react'
import { Controller, useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { z } from 'zod'
import { apiError, fieldErrors } from '@/core/api/errors'
import { Field } from '@/core/ui/Field'
import { useToast } from '@/core/ui/toast-context'
import { ClientPicker } from '@/modules/clients/ClientPicker'
import { useCreateDossier, useDossier, useDossierOptions, useUpdateDossier } from './hooks'
import type { Dossier } from './types'

const fields = z.object({
  client_id: z.string(),
  services: z.array(z.string()).min(1, 'Choisissez au moins un service.'),
  description: z.string().max(1000, 'Trop long (1000 caractères maximum).'),
  notes: z.string().max(2000, 'Trop long (2000 caractères maximum).'),
  track: z.boolean(),
})
type Values = z.infer<typeof fields>

const makeSchema = (editing: boolean) =>
  fields.superRefine((v, ctx) => {
    if (!editing && !v.client_id) {
      ctx.addIssue({ code: 'custom', path: ['client_id'], message: 'Choisissez un client.' })
    }
  })

export default function DossierFormPage() {
  const { id } = useParams()
  const [params] = useSearchParams()
  const { data: dossier, isPending, isError } = useDossier(id)

  if (id && isPending) return <div className="empty">Chargement…</div>
  if (id && (isError || !dossier)) {
    return (
      <div className="panel">
        <div className="empty">
          <b>Dossier introuvable</b>
          <Link to="/dossiers">Retour à la liste</Link>
        </div>
      </div>
    )
  }
  return <DossierForm dossier={id ? dossier : undefined} presetClient={params.get('client') ?? ''} />
}

function DossierForm({ dossier, presetClient }: { dossier?: Dossier; presetClient: string }) {
  const navigate = useNavigate()
  const toast = useToast()
  const { data: options } = useDossierOptions()
  const create = useCreateDossier()
  const update = useUpdateDossier(dossier?.id ?? 0)
  const [formError, setFormError] = useState<string | null>(null)
  const editing = !!dossier

  const {
    register,
    control,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<Values>({
    resolver: zodResolver(makeSchema(editing)),
    defaultValues: {
      client_id: presetClient,
      services: dossier ? dossier.services.map((s) => s.value) : [],
      description: dossier?.description ?? '',
      notes: dossier?.notes ?? '',
      track: true,
    },
  })

  const onSubmit = async (v: Values) => {
    setFormError(null)
    try {
      let saved: Dossier
      if (dossier) {
        saved = await update.mutateAsync({ services: v.services, description: v.description, notes: v.notes })
        toast('Dossier modifié')
      } else {
        saved = await create.mutateAsync({
          client_id: Number(v.client_id),
          services: v.services,
          description: v.description,
          track: v.track,
        })
        toast(`Dossier ${saved.number} créé`)
      }
      navigate(`/dossiers/${saved.id}`, { replace: true })
    } catch (e) {
      const serverErrors = fieldErrors(e)
      if (serverErrors) {
        const known = Object.keys(fields.shape)
        for (const [field, message] of Object.entries(serverErrors)) {
          const key = field.split('.')[0]
          if (known.includes(key)) setError(key as keyof Values, { message })
        }
        setFormError('Corrigez les champs signalés en rouge.')
      } else {
        setFormError(apiError(e))
      }
    }
  }

  const cancelTo = dossier ? `/dossiers/${dossier.id}` : '/dossiers'
  const pending = create.isPending || update.isPending

  return (
    <>
      <div>
        <Link to={cancelTo} className="btn link backlink">
          ← {dossier ? 'Retour au dossier' : 'Dossiers'}
        </Link>
      </div>
      <div className="ph">
        <h1>{dossier ? `Modifier le dossier ${dossier.number}` : 'Nouveau dossier'}</h1>
      </div>

      <form className="panel" onSubmit={handleSubmit(onSubmit)} noValidate style={{ maxWidth: 760 }}>
        <div className="bd">
          <div className="field">
            <span className="lbl">Client *</span>
            {dossier ? (
              <div className="picked">
                <b>{dossier.client?.name}</b>
              </div>
            ) : (
              <Controller
                name="client_id"
                control={control}
                render={({ field }) => (
                  <ClientPicker value={field.value} onChange={field.onChange} invalid={!!errors.client_id} />
                )}
              />
            )}
            {errors.client_id && <span className="errmsg">{errors.client_id.message}</span>}
            {!dossier && (
              <span className="hint">
                Client absent de la liste ? <Link to="/clients/nouveau">Créez-le d’abord</Link>.
              </span>
            )}
          </div>

          <div className="field">
            <span className="lbl">Services demandés *</span>
            <div className="checks">
              {options?.services.map((s) => (
                <label key={s.value} className="check">
                  <input type="checkbox" value={s.value} {...register('services')} />
                  {s.label}
                </label>
              ))}
            </div>
            {errors.services && <span className="errmsg">{errors.services.message}</span>}
          </div>

          <Field id="description" label="Description du dossier" error={errors.description?.message}>
            <textarea
              id="description"
              rows={3}
              className="txt"
              placeholder="Ex. : un conteneur 40 pieds de matériel, Abidjan – Ouagadougou"
              {...register('description')}
            />
          </Field>

          {dossier ? (
            <Field id="notes" label="Notes" error={errors.notes?.message}>
              <textarea id="notes" rows={3} className="txt" {...register('notes')} />
            </Field>
          ) : (
            <label className="check" style={{ alignItems: 'flex-start' }}>
              <input type="checkbox" style={{ marginTop: 3 }} {...register('track')} />
              <span>
                <b>Suivre le transit de ce dossier</b>
                <br />
                <span className="hint">
                  À décocher pour un dossier sans transit ni dédouanement (manutention seule, conseil…). Vous pourrez
                  l’activer plus tard.
                </span>
              </span>
            </label>
          )}

          {formError && (
            <p className="warn" role="alert" style={{ marginTop: 14 }}>
              {formError}
            </p>
          )}
        </div>
        <div
          style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, padding: '14px 18px', borderTop: '1px solid var(--line-2)' }}
        >
          <Link to={cancelTo} className="btn">
            Annuler
          </Link>
          <button type="submit" className="btn primary" disabled={pending}>
            {pending ? 'Enregistrement…' : dossier ? 'Enregistrer' : 'Créer le dossier'}
          </button>
        </div>
      </form>
    </>
  )
}