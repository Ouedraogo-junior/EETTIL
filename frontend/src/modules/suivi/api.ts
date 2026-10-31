import { http } from '@/core/api/http'
import type { Tracking, TrackingInput } from './types'

export async function activateTracking(dossierId: number): Promise<Tracking> {
  const { data } = await http.post<{ data: Tracking }>(`/api/v1/dossiers/${dossierId}/tracking`)
  return data.data
}

export async function saveTracking(dossierId: number, input: TrackingInput): Promise<Tracking> {
  const { data } = await http.put<{ data: Tracking }>(`/api/v1/dossiers/${dossierId}/tracking`, input)
  return data.data
}