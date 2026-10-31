import type { IconName } from '@/core/ui/Icon'

export interface NavItem {
  to: string
  label: string
  icon: IconName
  adminOnly?: boolean
}

export const NAV_ITEMS: NavItem[] = [
  { to: '/tableau-de-bord', label: 'Tableau de bord', icon: 'dashboard' },
  { to: '/clients', label: 'Clients', icon: 'clients' },
  { to: '/dossiers', label: 'Dossiers', icon: 'orders' },
  { to: '/suivi', label: 'Suivi transit', icon: 'suivi' },
  { to: '/factures', label: 'Factures', icon: 'invoices' },
  { to: '/paiements', label: 'Paiements', icon: 'payments' },
  { to: '/tresorerie', label: 'Trésorerie', icon: 'cash' },
  { to: '/depenses', label: 'Dépenses', icon: 'expenses' },
  { to: '/rapports', label: 'Rapports', icon: 'reports' },
  { to: '/administration', label: 'Administration', icon: 'admin', adminOnly: true },
]