export type AccountType = 'caisse' | 'banque'
export type Direction = 'in' | 'out'

export interface Account {
  id: number
  name: string
  type: AccountType
  type_label: string
  initial_balance: number
  active: boolean
  balance: number
}

export interface Movement {
  id: number
  moved_on: string
  direction: Direction
  amount: number
  label: string
  is_debours: boolean
  source: 'payment' | 'manual'
  payment_id: number | null
  account: { id: number; name: string } | null
  category: { id: number; name: string } | null
  dossier: { id: number; number: string } | null
  cancelled: boolean
  cancelled_at: string | null
  cancel_reason: string | null
}

export interface MovementCategory {
  id: number
  name: string
  is_debours: boolean
  is_system: boolean
}

export interface MovementOptions {
  accounts: Account[]
  categories: { in: MovementCategory[]; out: MovementCategory[] }
}

export interface MovementInput {
  direction: Direction
  moved_on: string
  label: string
  category_id: number
  amount: number
  account_id: number
  dossier_id: number | null
}

export interface MovementTotals {
  in: number
  out: number
}