import { Fragment } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import logo from '@/assets/logo.jpg'
import { Icon } from '@/core/ui/Icon'
import { useLogout, useMe } from '@/modules/auth/hooks'
import { NAV_ITEMS } from './nav'

export default function AppLayout() {
  const { data: user } = useMe()
  const logout = useLogout()
  const { pathname } = useLocation()

  const items = NAV_ITEMS.filter((i) => !i.adminOnly || user?.role === 'admin')
  const current = NAV_ITEMS.find((i) => pathname.startsWith(i.to))

  return (
    <div className="app">
      <aside className="side">
        <div className="logo-card">
          <img src={logo} alt="EETTIL" />
        </div>
        <nav className="nav" aria-label="Navigation principale">
          {items.map((item) => (
            <Fragment key={item.to}>
              {item.adminOnly && <div className="sep" />}
              <NavLink to={item.to} className={({ isActive }) => (isActive ? 'on' : '')}>
                <Icon name={item.icon} />
                <span>{item.label}</span>
              </NavLink>
            </Fragment>
          ))}
        </nav>
        <div className="who">
          <b>{user?.name}</b>
          {user?.role_label}
        </div>
      </aside>

      <div className="main">
        <header className="top">
          <div className="crumb">{current?.label ?? ''}</div>
          <div className="grow" />
          <button className="btn sm" onClick={() => logout.mutate()} disabled={logout.isPending}>
            <Icon name="logout" />
            Déconnexion
          </button>
        </header>
        <main className="page">
          <Outlet />
        </main>
      </div>
    </div>
  )
}