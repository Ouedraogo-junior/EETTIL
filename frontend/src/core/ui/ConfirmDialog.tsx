import { useEffect } from 'react'

interface Props {
  title: string
  message: string
  confirmLabel: string
  danger?: boolean
  busy?: boolean
  onConfirm: () => void
  onCancel: () => void
}

export function ConfirmDialog({ title, message, confirmLabel, danger, busy, onConfirm, onCancel }: Props) {
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onCancel()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onCancel])

  return (
    <div className="overlay" onClick={onCancel}>
      <div
        className="modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dialog-title"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="hd">
          <h3 id="dialog-title">{title}</h3>
          <button className="x" aria-label="Fermer" onClick={onCancel}>
            ×
          </button>
        </div>
        <div className="bd">{message}</div>
        <div className="ft">
          <button className="btn" onClick={onCancel} disabled={busy}>
            Annuler
          </button>
          <button className={'btn ' + (danger ? 'red' : 'navy')} onClick={onConfirm} disabled={busy}>
            {busy ? 'Patientez…' : confirmLabel}
          </button>
        </div>
      </div>
    </div>
  )
}