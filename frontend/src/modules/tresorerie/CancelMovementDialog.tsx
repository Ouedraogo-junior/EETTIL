import { useState } from 'react'
import { apiError, fieldErrors } from '@/core/api/errors'
import { formatFcfa } from '@/core/lib/format'
import { ReasonModal } from '@/core/ui/ReasonModal'
import { useToast } from '@/core/ui/toast-context'
import { useCancelMovement } from './hooks'
import type { Movement } from './types'

export function CancelMovementDialog({ movement, onClose }: { movement: Movement; onClose: () => void }) {
  const toast = useToast()
  const cancel = useCancelMovement()
  const [error, setError] = useState<string | null>(null)

  const onConfirm = async (reason: string) => {
    setError(null)
    try {
      await cancel.mutateAsync({ id: movement.id, reason })
      toast('Mouvement annulé. Les soldes sont mis à jour.')
      onClose()
    } catch (e) {
      const server = fieldErrors(e)
      setError(server ? Object.values(server)[0] : apiError(e))
    }
  }

  return (
    <ReasonModal
      title="Annuler ce mouvement ?"
      message={`« ${movement.label} » (${formatFcfa(movement.amount)}) restera visible, barré, avec son motif, et ne comptera plus dans le solde.`}
      confirmLabel="Annuler le mouvement"
      busy={cancel.isPending}
      error={error}
      onConfirm={onConfirm}
      onClose={onClose}
    />
  )
}