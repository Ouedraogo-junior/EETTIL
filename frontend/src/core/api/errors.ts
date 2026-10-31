import axios from 'axios'

type ApiErrorBody = { message?: string; errors?: Record<string, string[]> }

/** Transforme une erreur d'appel API en message lisible pour l'utilisateur. */
export function apiError(e: unknown, fallback = 'Une erreur est survenue. Réessayez.'): string {
  if (axios.isAxiosError(e)) {
    if (!e.response) return "Impossible de joindre le serveur. Vérifiez que l'API est démarrée."
    const body = e.response.data as ApiErrorBody | undefined
    if (e.response.status === 422 && body?.errors) {
      return Object.values(body.errors)[0]?.[0] ?? body.message ?? fallback
    }
    if (e.response.status === 429) return 'Trop de tentatives. Patientez une minute puis réessayez.'
    if (body?.message) return body.message
  }
  return fallback
}

/** Erreurs de validation (422) champ par champ : { ifu: "Cette valeur existe déjà." } ou null. */
export function fieldErrors(e: unknown): Record<string, string> | null {
  if (axios.isAxiosError(e) && e.response?.status === 422) {
    const errors = (e.response.data as ApiErrorBody | undefined)?.errors
    if (errors) {
      return Object.fromEntries(Object.entries(errors).map(([field, msgs]) => [field, msgs[0]]))
    }
  }
  return null
}