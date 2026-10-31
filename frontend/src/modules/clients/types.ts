export interface Client {
  id: number
  name: string
  phone: string | null
  email: string | null
  address: string | null
  ifu: string | null
  rccm: string | null
  notes: string | null
  created_at: string
}

/** Données envoyées à l'API (les champs vides sont convertis en null côté serveur). */
export interface ClientInput {
  name: string
  phone: string
  email: string
  address: string
  ifu: string
  rccm: string
  notes: string
}