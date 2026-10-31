import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useMe } from './hooks'
import type { Role } from './types'

export function RequireAuth() {
  const { data: user, isPending } = useMe()
  const location = useLocation()

  if (isPending) return <div className="empty">Chargement…</div>
  if (!user) return <Navigate to="/login" replace state={{ from: location.pathname }} />
  return <Outlet />
}

export function RequireRole({ role }: { role: Role }) {
  const { data: user } = useMe()
  if (user?.role !== role) return <Navigate to="/" replace />
  return <Outlet />
}