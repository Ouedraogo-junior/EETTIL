export type Role = 'admin' | 'gerant'

export interface User {
  id: number
  name: string
  login: string
  role: Role
  role_label: string
}