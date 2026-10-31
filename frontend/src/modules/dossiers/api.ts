import { http } from '@/core/api/http'
import type { Paginated } from '@/core/api/types'
import type {
  CreateDossierInput,
  Dossier,
  DossierOptions,
  DossierStatus,
  UpdateDossierInput,
} from './types'

export interface DossierListParams {
  q?: string
  status?: string
  service?: string
  client_id?: number
  page?: number
  per_page?: number
}

const base = '/api/v1/dossiers'

export async function listDossiers(params: DossierListParams): Promise<Paginated<Dossier>> {
  const { data } = await http.get<Paginated<Dossier>>(base, { params })
  return data
}

export async function getDossier(id: string): Promise<Dossier> {
  const { data } = await http.get<{ data: Dossier }>(`${base}/${id}`)
  return data.data
}

export async function createDossier(input: CreateDossierInput): Promise<Dossier> {
  const { data } = await http.post<{ data: Dossier }>(base, input)
  return data.data
}

export async function updateDossier(id: number, input: UpdateDossierInput): Promise<Dossier> {
  const { data } = await http.put<{ data: Dossier }>(`${base}/${id}`, input)
  return data.data
}

export async function changeStatus(id: number, status: DossierStatus): Promise<Dossier> {
  const { data } = await http.patch<{ data: Dossier }>(`${base}/${id}/status`, { status })
  return data.data
}

export async function deleteDossier(id: number): Promise<void> {
  await http.delete(`${base}/${id}`)
}

export async function getOptions(): Promise<DossierOptions> {
  const { data } = await http.get<{ data: DossierOptions }>(`${base}/options`)
  return data.data
}