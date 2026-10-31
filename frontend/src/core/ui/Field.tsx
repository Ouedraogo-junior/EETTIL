import type { ReactNode } from 'react'

interface Props {
  id: string
  label: string
  error?: string
  hint?: string
  children: ReactNode
}

/** Libellé + champ + aide + message d'erreur, aligné sur le style de la maquette. */
export function Field({ id, label, error, hint, children }: Props) {
  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      {children}
      {hint && !error && <span className="hint">{hint}</span>}
      {error && <span className="errmsg">{error}</span>}
    </div>
  )
}