import type { Tracking } from '@/modules/suivi/types'

export type DossierStatus = 'nouvelle' | 'en_cours' | 'terminee' | 'cloturee' | 'annulee'

export interface Option {
  value: string
  label: string
}

export interface DossierOptions {
  statuses: Option[]
  services: Option[]
  regimes: string[]
  tracking_states: Option[]
}

export interface Dossier {
  id: number
  number: string
  opened_on: string
  status: DossierStatus
  status_label: string
  services: Option[]
  description: string | null
  notes: string | null
  client_id: number
  client: { id: number; name: string } | null
  tracking: Tracking | null
}

export interface CreateDossierInput {
  client_id: number
  services: string[]
  description: string
  track: boolean
}

export interface UpdateDossierInput {
  services: string[]
  description: string
  notes: string
}