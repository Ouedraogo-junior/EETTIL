import axios from 'axios'
import { ensureCsrfCookie, http } from '@/core/api/http'
import type { User } from './types'

/** Utilisateur connecté, ou null si la session n'existe pas / a expiré. */
export async function fetchMe(): Promise<User | null> {
  try {
    const { data } = await http.get<{ data: User }>('/api/v1/auth/me')
    return data.data
  } catch (e) {
    if (axios.isAxiosError(e) && e.response?.status === 401) return null
    throw e
  }
}

export async function login(payload: { login: string; password: string }): Promise<User | null> {
  await ensureCsrfCookie()
  await http.post('/api/v1/auth/login', payload)
  return fetchMe()
}

export async function logout(): Promise<void> {
  await http.post('/api/v1/auth/logout')
}