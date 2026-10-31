import { http } from '@/core/api/http'
import type { Tracking, TrackingInput } from './types'
import { downloadFile } from '@/core/api/download'
import type { Paginated } from '@/core/api/types'
import { todayISO } from '@/core/lib/format'
import type { SuiviRow } from './types'

export interface SuiviParams {
  q?: string
  state?: string
  page?: number
}

export async function listSuivi(params: SuiviParams): Promise<Paginated<SuiviRow>> {
  const { data } = await http.get<Paginated<SuiviRow>>('/api/v1/suivi', { params })
  return data
}

/** Exporte le suivi (avec les filtres en cours) vers un classeur Excel. */
export function exportSuivi(params: Pick<SuiviParams, 'q' | 'state'>): Promise<void> {
  return downloadFile('/api/v1/suivi/export', params, `suivi-dossiers-${todayISO()}.xlsx`)
}

export async function activateTracking(dossierId: number): Promise<Tracking> {
  const { data } = await http.post<{ data: Tracking }>(`/api/v1/dossiers/${dossierId}/tracking`)
  return data.data
}

export async function saveTracking(dossierId: number, input: TrackingInput): Promise<Tracking> {
  const { data } = await http.put<{ data: Tracking }>(`/api/v1/dossiers/${dossierId}/tracking`, input)
  return data.data
}