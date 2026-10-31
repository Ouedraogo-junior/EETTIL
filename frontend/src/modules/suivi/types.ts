export type TrackingState = 'not_started' | 'in_progress' | 'delivered'

export const STEP_KEYS = ['date_ot', 'date_arrival', 'date_docs', 'date_declaration', 'date_bae', 'date_delivery'] as const
export type StepKey = (typeof STEP_KEYS)[number]

export interface TrackingStep {
  key: StepKey
  label: string
  date: string | null
}

interface TrackingBase {
  id: number
  dossier_id: number
  supplier_invoices: string | null
  dpi_number: string | null
  dpi_date: string | null
  bl_lta: string | null
  transit_order_number: string | null
  regime: string | null
  nature: string | null
  packages: number | null
  weight_kg: number | null
  comments: string | null
  steps: TrackingStep[]
  state: TrackingState
  state_label: string
  current_step: { key: StepKey; label: string } | null
  last_date: string | null
}

export type Tracking = TrackingBase & Record<StepKey, string | null>

/** Données envoyées à l'API (les champs vides sont convertis en null côté serveur). */
export type TrackingInput = {
  supplier_invoices: string
  dpi_number: string
  dpi_date: string
  bl_lta: string
  transit_order_number: string
  regime: string
  nature: string
  packages: number
  weight_kg: number
  comments: string
} & Record<StepKey, string>