import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { fetchMe, login, logout } from './api'

export const ME_KEY = ['me'] as const

export function useMe() {
  return useQuery({ queryKey: ME_KEY, queryFn: fetchMe, staleTime: Infinity, retry: false })
}

export function useLogin() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: login,
    onSuccess: (user) => qc.setQueryData(ME_KEY, user),
  })
}

export function useLogout() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: logout,
    onSettled: () => {
      // On vide tout le cache métier, puis on marque l'utilisateur comme déconnecté.
      qc.removeQueries({ predicate: (q) => q.queryKey[0] !== ME_KEY[0] })
      qc.setQueryData(ME_KEY, null)
    },
  })
}