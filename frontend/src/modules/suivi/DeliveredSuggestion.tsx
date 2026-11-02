import { useState } from 'react'
import { apiError } from '@/core/api/errors'
import { useToast } from '@/core/ui/toast-context'
import { useChangeStatus } from '@/modules/dossiers/hooks'
import type { Dossier } from '@/modules/dossiers/types'

/**
 * Quand la marchandise est livrée à l'agence et que le dossier est encore « En cours »,
 * le système propose de le passer à « Terminée ». Il ne le fait jamais seul : il faut peut-être encore facturer ou encaisser.
 */
export function DeliveredSuggestion({ dossier }: { dossier: Dossier }) {
  const toast = useToast()
  const change = useChangeStatus(dossier.id)
  const [later, setLater] = useState(false)

  if (later || dossier.tracking?.state !== 'delivered' || dossier.status !== 'en_cours') return null

  const accept = async () => {
    try {
      await change.mutateAsync('terminee')
      toast('Dossier passé à « Terminée ».')
    } catch (e) {
      toast(apiError(e), 'bad')
    }
  }

  return (
    <div className="suggest" role="status">
      <div>
        <b>La marchandise est livrée.</b> Passer le dossier à « Terminée » ?
        <div className="hint">Vous pourrez le clôturer plus tard, une fois la facture soldée.</div>
      </div>
      <div className="acts">
        <button type="button" className="btn" onClick={() => setLater(true)}>
          Plus tard
        </button>
        <button type="button" className="btn primary" onClick={accept} disabled={change.isPending}>
          {change.isPending ? 'Patientez…' : 'Passer à Terminée'}
        </button>
      </div>
    </div>
  )
}