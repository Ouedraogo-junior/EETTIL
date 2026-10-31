import { Fragment, useState } from 'react'
import { NavLink, Outlet, useLocation } from 'react-router-dom'
import logo from '@/assets/logo.jpg'
import { Icon } from '@/core/ui/Icon'
import { useLogout, useMe } from '@/modules/auth/hooks'
import { NAV_ITEMS } from './nav'

export default function AppLayout() {
  const { data: user } = useMe()
  const logout = useLogout()
  const { pathname } = useLocation()
  const [menuOpen, setMenuOpen] = useState(false)

  const items = NAV_ITEMS.filter((i) => !i.adminOnly || user?.role === 'admin')
  const current = NAV_ITEMS.find((i) => pathname.startsWith(i.to))
  const closeMenu = () => setMenuOpen(false)

  return (
    <div className={'app' + (menuOpen ? ' menu-open' : '')}>
      <aside className="side" id="menu">
        <div className="logo-card">
          <img src={logo} alt="EETTIL" />
        </div>
        <nav className="nav" aria-label="Navigation principale" onClick={closeMenu}>
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
      <div className="scrim" onClick={closeMenu} />

      <div className="main">
        <header className="top">
          <button
            className="burger"
            aria-label="Ouvrir le menu"
            aria-controls="menu"
            aria-expanded={menuOpen}
            onClick={() => setMenuOpen(true)}
          >
            <Icon name="menu" />
          </button>
          <div className="crumb">{current?.label ?? ''}</div>
          <div className="grow" />
          <button className="btn sm" onClick={() => logout.mutate()} disabled={logout.isPending}>
            <Icon name="logout" />
            <span className="hide-sm">Déconnexion</span>
          </button>
        </header>
        <main className="page">
          <Outlet />
        </main>
      </div>
    </div>
  )
}