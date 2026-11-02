import { useState } from 'react'
import { apiError, fieldErrors } from '@/core/api/errors'
import { formatFcfa } from '@/core/lib/format'
import { ReasonModal } from '@/core/ui/ReasonModal'
import { useToast } from '@/core/ui/toast-context'
import { useCancelPayment } from './hooks'
import type { Payment } from './types'

export function CancelPaymentDialog({ payment, onClose }: { payment: Payment; onClose: () => void }) {
  const toast = useToast()
  const cancel = useCancelPayment()
  const [error, setError] = useState<string | null>(null)

  const onConfirm = async (reason: string) => {
    setError(null)
    try {
      await cancel.mutateAsync({ id: payment.id, reason })
      toast('Paiement annulé. La facture et la trésorerie sont mises à jour.')
      onClose()
    } catch (e) {
      const server = fieldErrors(e)
      setError(server ? Object.values(server)[0] : apiError(e))
    }
  }

  return (
    <ReasonModal
      title="Annuler ce paiement ?"
      message={`Le paiement de ${formatFcfa(payment.amount)}${payment.document ? ` sur ${payment.document.number}` : ''} restera visible, barré, avec son motif. Le montant sera retiré du compte ${payment.account?.name ?? ''} et ajouté au reste à payer.`}
      confirmLabel="Annuler le paiement"
      busy={cancel.isPending}
      error={error}
      onConfirm={onConfirm}
      onClose={onClose}
    />
  )
}