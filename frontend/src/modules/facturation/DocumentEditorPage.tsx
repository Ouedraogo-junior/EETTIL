import { useState } from 'react'
import { useFieldArray, useForm, useWatch, type FieldErrors, type FieldPath, type UseFormRegister } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { apiError, fieldErrors } from '@/core/api/errors'
import { formatXOF, todayISO } from '@/core/lib/format'
import { useDebouncedValue } from '@/core/lib/useDebouncedValue'
import { Field } from '@/core/ui/Field'
import { useToast } from '@/core/ui/toast-context'
import { useDossier } from '@/modules/dossiers/hooks'
import {
  useCreateDocument,
  useDocument,
  useDocumentOptions,
  useDocumentPreview,
  useDossierDocuments,
  useUpdateDocument,
} from './hooks'
import { formFields, formSchema, makeDefaults, newLine, parse, toPayload, toPreview, type FormValues } from './editor-form'
import { docLabel } from './labels'
import { TotalsBox } from './TotalsBox'
import type { Block, BillingDocument, DocumentOptions, DocumentTotals, DocumentType, PreviewLine } from './types'

interface Head {
  dossierId: number
  dossierNumber: string
  clientName: string
}

const EMPTY_TOTALS: DocumentTotals = {
  debours: 0,
  prestations_subtotal: 0,
  global_discount: 0,
  prestations_ht: 0,
  vat: 0,
  prestations_total: 0,
  total: 0,
  total_in_words: 'ZÉRO FRANC CFA',
}

const BLOCKS: Record<Block, { title: string; hint: string }> = {
  D: { title: 'A. Débours', hint: 'Sommes avancées pour le client (droits, taxes…). Sans TVA ni remise.' },
  P: { title: 'B. Prestations', hint: 'Honoraires et services de l’entreprise. Remises et TVA possibles.' },
}

export default function DocumentEditorPage() {
  const { id, dossierId } = useParams()
  const [params] = useSearchParams()
  const { data: options, isPending, isError } = useDocumentOptions()

  if (isPending) return <div className="empty">Chargement…</div>
  if (isError || !options) {
    return (
      <div className="panel">
        <div className="empty">
          <b>Chargement impossible</b>
          Réessayez dans un instant.
        </div>
      </div>
    )
  }
  if (id) return <EditExisting id={id} options={options} />
  return (
    <CreateNew
      dossierId={dossierId ?? ''}
      type={params.get('type') === 'facture' ? 'facture' : 'proforma'}
      options={options}
    />
  )
}

function Blocked({ title, message, to, toLabel }: { title: string; message: string; to: string; toLabel: string }) {
  return (
    <div className="panel">
      <div className="empty">
        <b>{title}</b>
        {message}
        <div style={{ marginTop: 12 }}>
          <Link to={to} className="btn sm">
            {toLabel}
          </Link>
        </div>
      </div>
    </div>
  )
}

function CreateNew({ dossierId, type, options }: { dossierId: string; type: DocumentType; options: DocumentOptions }) {
  const { data: dossier, isPending, isError } = useDossier(dossierId)
  const { data: documents, isPending: docsPending } = useDossierDocuments(Number(dossierId))

  if (isPending || docsPending) return <div className="empty">Chargement…</div>
  if (isError || !dossier) {
    return <Blocked title="Dossier introuvable" message="Ce dossier n’existe pas." to="/dossiers" toLabel="Retour aux dossiers" />
  }

  const active = documents?.find((d) => d.type === 'facture' && d.state !== 'annule')
  if (type === 'facture' && active) {
    return (
      <Blocked
        title="Ce dossier a déjà une facture"
        message={`La facture ${active.number} existe déjà. Modifiez-la, ou annulez-la avant d’en créer une nouvelle.`}
        to={`/documents/${active.id}`}
        toLabel="Voir la facture"
      />
    )
  }

  const head: Head = { dossierId: dossier.id, dossierNumber: dossier.number, clientName: dossier.client?.name ?? '' }
  return <DocumentEditor head={head} type={type} options={options} />
}

function EditExisting({ id, options }: { id: string; options: DocumentOptions }) {
  const { data: doc, isPending, isError } = useDocument(id)

  if (isPending) return <div className="empty">Chargement…</div>
  if (isError || !doc) {
    return <Blocked title="Document introuvable" message="Ce document n’existe pas." to="/factures" toLabel="Retour à la liste" />
  }
  if (!doc.is_editable) {
    return (
      <Blocked
        title="Document non modifiable"
        message={`Ce document est ${doc.state === 'annule' ? 'annulé' : 'déjà transformé en facture'}.`}
        to={`/documents/${doc.id}`}
        toLabel="Voir le document"
      />
    )
  }

  const head: Head = {
    dossierId: doc.dossier_id,
    dossierNumber: doc.dossier?.number ?? '',
    clientName: doc.dossier?.client?.name ?? '',
  }
  return <DocumentEditor head={head} type={doc.type} options={options} document={doc} />
}

interface EditorProps {
  head: Head
  type: DocumentType
  options: DocumentOptions
  document?: BillingDocument
}

function DocumentEditor({ head, type, options, document }: EditorProps) {
  const navigate = useNavigate()
  const toast = useToast()
  const create = useCreateDocument(head.dossierId)
  const update = useUpdateDocument(document?.id ?? 0)
  const [formError, setFormError] = useState<string | null>(null)
  const label = docLabel(type)

  const {
    register,
    control,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: makeDefaults(options, document),
  })
  const { fields, append, remove } = useFieldArray({ control, name: 'lines' })
  const values = useWatch({ control }) as FormValues

  // Totaux calculés par le serveur à partir de la saisie (après une courte pause de frappe)
  const hasLines = (values.lines ?? []).length > 0
  const previewKey = JSON.stringify(toPreview(values))
  const debouncedKey = useDebouncedValue(previewKey, 350)
  const preview = useDocumentPreview(debouncedKey, hasLines)
  const totals: DocumentTotals = hasLines && preview.data ? preview.data : EMPTY_TOTALS
  const calcLines: PreviewLine[] = hasLines ? (preview.data?.lines ?? []) : []

  const onSubmit = async (v: FormValues) => {
    setFormError(null)
    try {
      const payload = toPayload(v)
      const saved = document
        ? await update.mutateAsync(payload)
        : await create.mutateAsync({ ...payload, type })
      toast(document ? `${label} ${saved.number} modifiée` : `${label} ${saved.number} créée`)
      navigate(`/documents/${saved.id}`, { replace: true })
    } catch (e) {
      const serverErrors = fieldErrors(e)
      if (serverErrors) {
        const known = Object.keys(formFields.shape)
        const others: string[] = []
        for (const [path, message] of Object.entries(serverErrors)) {
          if (known.includes(path.split('.')[0])) setError(path as FieldPath<FormValues>, { message })
          else others.push(message)
        }
        setFormError(others[0] ?? 'Corrigez les champs signalés en rouge.')
      } else {
        setFormError(apiError(e))
      }
    }
  }

  const backTo = document ? `/documents/${document.id}` : `/dossiers/${head.dossierId}?onglet=documents`
  const pending = create.isPending || update.isPending
  const linesError = errors.lines?.message ?? errors.lines?.root?.message
  const globalKind = values.global_discount_kind

  return (
    <form onSubmit={handleSubmit(onSubmit)} noValidate>
      <div>
        <Link to={backTo} className="btn link backlink">
          ← {document ? 'Retour au document' : 'Retour au dossier'}
        </Link>
      </div>
      <div className="ph">
        <div>
          <h1>{document ? `Modifier ${document.number}` : type === 'facture' ? 'Nouvelle facture' : 'Nouvelle quotation'}</h1>
          <div className="sub">
            Dossier <span className="num">{head.dossierNumber}</span> · {head.clientName}
          </div>
        </div>
      </div>

      <div className="stack">
        <section className="panel">
          <div className="bd">
            <div className="row2">
              <Field id="issued_on" label="Date du document *" error={errors.issued_on?.message}>
                <input
                  id="issued_on"
                  type="date"
                  className={'inp' + (errors.issued_on ? ' err' : '')}
                  max={todayISO()}
                  {...register('issued_on')}
                />
              </Field>
              <div className="field">
                <span className="lbl">Numéro</span>
                <div className="picked">
                  <b className="num">{document ? document.number : 'Attribué à l’enregistrement'}</b>
                </div>
              </div>
            </div>
          </div>
        </section>

        {(['D', 'P'] as const).map((block) => (
          <section className="panel" key={block}>
            <div className="bd">
              <div className="dblock-hd">
                <h2>{BLOCKS[block].title}</h2>
                <div className="sub">{BLOCKS[block].hint}</div>
              </div>

              <div className="dlines">
                {fields.map((f, i) =>
                  f.block === block ? (
                    <LineRow
                      key={f.id}
                      i={i}
                      block={block}
                      register={register}
                      errors={errors}
                      calc={calcLines[i]}
                      units={options.units}
                      onRemove={() => remove(i)}
                    />
                  ) : null,
                )}
              </div>

              <div className="dadd">
                <select
                  className="sel"
                  aria-label={`Ajouter une ligne type au bloc ${block === 'D' ? 'débours' : 'prestations'}`}
                  value=""
                  onChange={(e) => e.target.value && append(newLine(block, e.target.value))}
                >
                  <option value="">+ Ligne type…</option>
                  {options.templates[block].map((t) => (
                    <option key={t} value={t}>
                      {t}
                    </option>
                  ))}
                </select>
                <button type="button" className="btn sm" onClick={() => append(newLine(block))}>
                  + Ligne libre
                </button>
              </div>
            </div>
          </section>
        ))}
        {linesError && (
          <p className="warn" role="alert">
            {linesError}
          </p>
        )}

        <section className="panel">
          <div className="bd">
            <h2 style={{ margin: '0 0 8px', fontSize: 15 }}>Remise globale et TVA (prestations seulement)</h2>
            <div className="row2">
              <Field id="gdk" label="Remise globale" error={errors.global_discount_value?.message}>
                <div className="disc">
                  <select id="gdk" className="sel" {...register('global_discount_kind')}>
                    <option value="">Aucune</option>
                    <option value="pct">%</option>
                    <option value="amt">FCFA</option>
                  </select>
                  <input
                    className={'inp' + (errors.global_discount_value ? ' err' : '')}
                    inputMode="decimal"
                    aria-label="Valeur de la remise globale"
                    disabled={!globalKind}
                    {...register('global_discount_value')}
                  />
                </div>
              </Field>
              <div className="field">
                <span className="lbl">TVA</span>
                <label className="check">
                  <input type="checkbox" {...register('vat_enabled')} />
                  Appliquer la TVA
                </label>
                {values.vat_enabled && (
                  <div style={{ maxWidth: 140 }}>
                    <input
                      className={'inp' + (errors.vat_rate ? ' err' : '')}
                      inputMode="decimal"
                      aria-label="Taux de TVA en pourcentage"
                      {...register('vat_rate')}
                    />
                    {errors.vat_rate && <span className="errmsg">{errors.vat_rate.message}</span>}
                    <span className="hint">Taux en %.</span>
                  </div>
                )}
              </div>
            </div>
            {preview.data?.global_over_limit && hasLines && (
              <p className="warn">La remise globale dépasse le sous-total des prestations : elle sera refusée à l’enregistrement.</p>
            )}
          </div>
        </section>

        <section className="panel">
          <details className="more">
            <summary>Informations sur la marchandise (facultatif)</summary>
            <div className="bd">
              <div className="row2">
                <Field id="currency" label="Devise de la facture fournisseur">
                  <select id="currency" className="sel" {...register('currency')}>
                    {options.currencies.map((c) => (
                      <option key={c} value={c}>
                        {c}
                      </option>
                    ))}
                  </select>
                </Field>
                {values.currency !== 'XOF' && (
                  <Field id="exchange_rate" label="Taux de change (en FCFA)" error={errors.exchange_rate?.message}>
                    <input
                      id="exchange_rate"
                      inputMode="decimal"
                      className={'inp' + (errors.exchange_rate ? ' err' : '')}
                      {...register('exchange_rate')}
                    />
                  </Field>
                )}
              </div>
              <div className="row2">
                <Field id="caf_value" label="Valeur CAF (FCFA)" error={errors.caf_value?.message}>
                  <input id="caf_value" inputMode="numeric" className={'inp' + (errors.caf_value ? ' err' : '')} {...register('caf_value')} />
                </Field>
                <div className="row2">
                  <Field id="containers_20" label="Conteneurs 20’" error={errors.containers_20?.message}>
                    <input id="containers_20" inputMode="numeric" className={'inp' + (errors.containers_20 ? ' err' : '')} {...register('containers_20')} />
                  </Field>
                  <Field id="containers_40" label="Conteneurs 40’" error={errors.containers_40?.message}>
                    <input id="containers_40" inputMode="numeric" className={'inp' + (errors.containers_40 ? ' err' : '')} {...register('containers_40')} />
                  </Field>
                </div>
              </div>
              <p className="hint" style={{ margin: 0 }}>
                Ces informations s’affichent sur le document. Les montants de la facture restent en FCFA.
              </p>
            </div>
          </details>
        </section>

        <section className="panel">
          <div className="bd">
            <TotalsBox
              totals={totals}
              vatEnabled={!!values.vat_enabled}
              vatRate={parse(values.vat_rate ?? '0')}
              discountNote={globalKind === 'pct' ? `${values.global_discount_value} %` : undefined}
            />
            {preview.isError && hasLines && (
              <p className="warn" style={{ marginTop: 12 }}>
                Le calcul n’a pas pu être mis à jour. Vérifiez les montants saisis.
              </p>
            )}
          </div>
        </section>

        {formError && (
          <p className="warn" role="alert">
            {formError}
          </p>
        )}
      </div>

      <div className="dbar">
        <div>
          <div className="l">Total {preview.isFetching ? '(calcul…)' : ''}</div>
          <b className="num">{formatXOF(totals.total)}</b>
        </div>
        <div className="acts">
          <Link to={backTo} className="btn">
            Annuler
          </Link>
          <button type="submit" className="btn primary" disabled={pending}>
            {pending ? 'Enregistrement…' : document ? 'Enregistrer' : `Créer la ${label.toLowerCase()}`}
          </button>
        </div>
      </div>
    </form>
  )
}

interface LineRowProps {
  i: number
  block: Block
  register: UseFormRegister<FormValues>
  errors: FieldErrors<FormValues>
  calc?: PreviewLine
  units: string[]
  onRemove: () => void
}

function LineRow({ i, block, register, errors, calc, units, onRemove }: LineRowProps) {
  const e = errors.lines?.[i]
  const cls = (bad: boolean) => 'inp' + (bad ? ' err' : '')

  return (
    <div className={'dline' + (block === 'D' ? ' d' : '')}>
      <input type="hidden" {...register(`lines.${i}.block` as const)} />
      <div className="f-label">
        <label htmlFor={`l${i}-label`}>Désignation</label>
        <input id={`l${i}-label`} className={cls(!!e?.label)} autoComplete="off" {...register(`lines.${i}.label` as const)} />
      </div>
      <div>
        <label htmlFor={`l${i}-unit`}>Unité</label>
        <select id={`l${i}-unit`} className="sel" {...register(`lines.${i}.unit` as const)}>
          {units.map((u) => (
            <option key={u} value={u}>
              {u}
            </option>
          ))}
        </select>
      </div>
      <div>
        <label htmlFor={`l${i}-qty`}>Quantité</label>
        <input id={`l${i}-qty`} inputMode="decimal" className={cls(!!e?.quantity)} {...register(`lines.${i}.quantity` as const)} />
      </div>
      <div>
        <label htmlFor={`l${i}-price`}>Prix unitaire (FCFA)</label>
        <input id={`l${i}-price`} inputMode="numeric" className={cls(!!e?.unit_price)} {...register(`lines.${i}.unit_price` as const)} />
      </div>
      {block === 'P' && (
        <div className="f-disc">
          <label htmlFor={`l${i}-dk`}>Remise sur la ligne</label>
          <div className="disc">
            <select id={`l${i}-dk`} className="sel" {...register(`lines.${i}.discount_kind` as const)}>
              <option value="">Aucune</option>
              <option value="pct">%</option>
              <option value="amt">FCFA</option>
            </select>
            <input
              inputMode="decimal"
              aria-label="Valeur de la remise"
              className={cls(!!e?.discount_value)}
              {...register(`lines.${i}.discount_value` as const)}
            />
          </div>
        </div>
      )}
      <div className="end">
        <span className="tot">{calc ? formatXOF(calc.total) : '–'}</span>
        <button type="button" className="btn sm danger" onClick={onRemove} aria-label="Retirer cette ligne">
          Retirer
        </button>
      </div>
      {(e?.label || e?.quantity || e?.unit_price || e?.discount_value) && (
        <span className="errmsg">
          {e?.label?.message ?? e?.quantity?.message ?? e?.unit_price?.message ?? e?.discount_value?.message}
        </span>
      )}
      {calc?.over_limit && (
        <p className="warn">La remise dépasse le montant de la ligne : elle sera refusée à l’enregistrement.</p>
      )}
    </div>
  )
}