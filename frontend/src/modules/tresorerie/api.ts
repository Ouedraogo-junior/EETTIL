import { http } from '@/core/api/http'
import type { Paginated } from '@/core/api/types'
import type { Account, Movement, MovementInput, MovementOptions, MovementTotals } from './types'

export interface MovementListParams {
  account_id?: number
  direction?: string
  category_id?: number
  dossier_id?: number
  from?: string
  to?: string
  q?: string
  page?: number
  per_page?: number
}

export async function listAccounts(): Promise<{ accounts: Account[]; total: number }> {
  const { data } = await http.get<{ data: Account[]; total: number }>('/api/v1/accounts')
  return { accounts: data.data, total: data.total }
}

export async function listMovements(params: MovementListParams): Promise<Paginated<Movement> & { totals: MovementTotals }> {
  const { data } = await http.get<Paginated<Movement> & { totals: MovementTotals }>('/api/v1/movements', { params })
  return data
}

export async function getMovementOptions(): Promise<MovementOptions> {
  const { data } = await http.get<{ data: MovementOptions }>('/api/v1/movements/options')
  return data.data
}

export async function recordMovement(input: MovementInput): Promise<Movement> {
  const { data } = await http.post<{ data: Movement }>('/api/v1/movements', input)
  return data.data
}

export async function cancelMovement(id: number, reason: string): Promise<Movement> {
  const { data } = await http.post<{ data: Movement }>(`/api/v1/movements/${id}/cancel`, { reason })
  return data.data
}