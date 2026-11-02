import { useState } from 'react'
import { Modal } from './Modal'

interface Props {
  title: string
  message: string
  confirmLabel: string
  busy?: boolean
  /** Message d'erreur du serveur à afficher sous le champ (ex. « Ce dossier est clôturé… »). */
  error?: string | null
  onConfirm: (reason: string) => void
  onClose: () => void
}

/** Demande un motif avant une annulation (on n'efface jamais : on annule avec une raison). */
export function ReasonModal({ title, message, confirmLabel, busy, error, onConfirm, onClose }: Props) {
  const [reason, setReason] = useState('')
  const [touched, setTouched] = useState(false)
  const tooShort = reason.trim().length < 3

  const submit = () => {
    setTouched(true)
    if (!tooShort) onConfirm(reason.trim())
  }

  return (
    <Modal
      title={title}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn" onClick={onClose} disabled={busy}>
            Retour
          </button>
          <button type="button" className="btn red" onClick={submit} disabled={busy}>
            {busy ? 'Patientez…' : confirmLabel}
          </button>
        </>
      }
    >
      <p style={{ marginTop: 0 }}>{message}</p>
      <div className="field">
        <label htmlFor="cancel-reason">Motif *</label>
        <textarea
          id="cancel-reason"
          rows={3}
          className={'txt' + (touched && tooShort ? ' err' : '')}
          value={reason}
          maxLength={500}
          onChange={(e) => setReason(e.target.value)}
        />
        {touched && tooShort && <span className="errmsg">Indiquez le motif de l’annulation (3 caractères au moins).</span>}
      </div>
      {error && (
        <p className="warn" role="alert">
          {error}
        </p>
      )}
    </Modal>
  )
}