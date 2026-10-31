import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createClient, deleteClient, getClient, listClients, updateClient, type ClientListParams } from './api'
import type { ClientInput } from './types'

export const clientKeys = {
  lists: ['clients', 'list'] as const,
  list: (p: ClientListParams) => ['clients', 'list', p] as const,
  detail: (id: string) => ['clients', 'detail', id] as const,
}

export function useClients(params: ClientListParams) {
  return useQuery({
    queryKey: clientKeys.list(params),
    queryFn: () => listClients(params),
    placeholderData: keepPreviousData, // garde l'ancienne liste affichée pendant la recherche
  })
}

export function useClient(id: string | undefined) {
  return useQuery({
    queryKey: clientKeys.detail(id ?? ''),
    queryFn: () => getClient(id!),
    enabled: !!id,
  })
}

export function useSaveClient() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, input }: { id?: number; input: ClientInput }) =>
      id ? updateClient(id, input) : createClient(input),
    onSuccess: (client) => {
      qc.setQueryData(clientKeys.detail(String(client.id)), client)
      qc.invalidateQueries({ queryKey: clientKeys.lists })
    },
  })
}

export function useDeleteClient() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: deleteClient,
    onSuccess: (_void, id) => {
      qc.removeQueries({ queryKey: clientKeys.detail(String(id)) })
      qc.invalidateQueries({ queryKey: clientKeys.lists })
    },
  })
}