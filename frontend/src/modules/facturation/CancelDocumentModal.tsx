import { useEffect, useState } from 'react'
import { apiError } from '@/core/api/errors'
import { useToast } from '@/core/ui/toast-context'
import { useCancelDocument } from './hooks'
import { docLabel } from './labels'
import type { BillingDocument } from './types'

export function CancelDocumentModal({ doc, onClose }: { doc: BillingDocument; onClose: () => void }) {
  const toast = useToast()
  const cancel = useCancelDocument(doc.id)
  const [reason, setReason] = useState('')
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onClose])

  const submit = async () => {
    if (reason.trim().length < 3) {
      setError('Indiquez le motif de l’annulation (3 caractères minimum).')
      return
    }
    try {
      await cancel.mutateAsync(reason.trim())
      toast(`${docLabel(doc.type)} ${doc.number} annulée`)
      onClose()
    } catch (e) {
      setError(apiError(e))
    }
  }

  return (
    <div className="overlay">
      <div className="modal" role="dialog" aria-modal="true" aria-labelledby="cancel-title">
        <div className="hd">
          <h3 id="cancel-title">Annuler {doc.number} ?</h3>
          <button type="button" className="x" aria-label="Fermer" onClick={onClose}>
            ×
          </button>
        </div>
        <div className="bd">
          <p style={{ marginTop: 0 }}>
            Le document restera visible avec la mention « Annulée » et son numéro ne sera pas réutilisé. Cette action ne
            peut pas être défaite.
          </p>
          <div className="field">
            <label htmlFor="reason">Motif de l’annulation *</label>
            <textarea
              id="reason"
              rows={3}
              className="txt"
              autoFocus
              value={reason}
              onChange={(e) => setReason(e.target.value)}
            />
          </div>
          {error && (
            <p className="warn" role="alert">
              {error}
            </p>
          )}
        </div>
        <div className="ft">
          <button type="button" className="btn" onClick={onClose} disabled={cancel.isPending}>
            Retour
          </button>
          <button type="button" className="btn red" onClick={submit} disabled={cancel.isPending}>
            {cancel.isPending ? 'Patientez…' : 'Annuler le document'}
          </button>
        </div>
      </div>
    </div>
  )
}