import type { Tracking } from '@/modules/suivi/types'
import type { DossierStatus } from './types'

const STATUS_CLASS: Record<DossierStatus, string> = {
  nouvelle: 'b-blue',
  en_cours: 'b-amber',
  terminee: 'b-teal',
  cloturee: 'b-green',
  annulee: 'b-grey',
}

export function StatusBadge({ status, label }: { status: DossierStatus; label: string }) {
  return <span className={'badge ' + STATUS_CLASS[status]}>{label}</span>
}

/** État du transit : Livré, étape actuelle, Pas commencé, ou « Sans transit » si le suivi n'est pas activé. */
export function TrackingBadge({ tracking }: { tracking: Tracking | null }) {
  if (!tracking) return <span className="faint">Sans transit</span>
  if (tracking.state === 'delivered') return <span className="badge b-green">Livré</span>
  if (tracking.current_step) return <span className="badge b-amber">{tracking.current_step.label}</span>
  return <span className="badge b-grey">Pas commencé</span>
}