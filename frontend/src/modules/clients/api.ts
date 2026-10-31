import { http } from '@/core/api/http'
import type { Paginated } from '@/core/api/types'
import type { Client, ClientInput } from './types'

export interface ClientListParams {
  q?: string
  page?: number
  per_page?: number
}

const base = '/api/v1/clients'

export async function listClients(params: ClientListParams): Promise<Paginated<Client>> {
  const { data } = await http.get<Paginated<Client>>(base, { params })
  return data
}

export async function getClient(id: string): Promise<Client> {
  const { data } = await http.get<{ data: Client }>(`${base}/${id}`)
  return data.data
}

export async function createClient(input: ClientInput): Promise<Client> {
  const { data } = await http.post<{ data: Client }>(base, input)
  return data.data
}

export async function updateClient(id: number, input: ClientInput): Promise<Client> {
  const { data } = await http.put<{ data: Client }>(`${base}/${id}`, input)
  return data.data
}

export async function deleteClient(id: number): Promise<void> {
  await http.delete(`${base}/${id}`)
}