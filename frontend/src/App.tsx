import { Navigate, Route, Routes } from 'react-router-dom'
import AppLayout from '@/core/layout/AppLayout'
import { ComingSoon } from '@/core/ui/ComingSoon'
import LoginPage from '@/modules/auth/LoginPage'
import { RequireAuth, RequireRole } from '@/modules/auth/RequireAuth'
import DashboardPage from '@/modules/dashboard/DashboardPage'
import ClientDetailPage from '@/modules/clients/ClientDetailPage'
import ClientFormPage from '@/modules/clients/ClientFormPage'
import ClientsListPage from '@/modules/clients/ClientsListPage'

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />

      <Route element={<RequireAuth />}>
        <Route element={<AppLayout />}>
          <Route index element={<Navigate to="/tableau-de-bord" replace />} />
          <Route path="tableau-de-bord" element={<DashboardPage />} />
          <Route path="clients">
            <Route index element={<ClientsListPage />} />
            <Route path="nouveau" element={<ClientFormPage />} />
            <Route path=":id" element={<ClientDetailPage />} />
            <Route path=":id/modifier" element={<ClientFormPage />} />
          </Route>
          <Route path="dossiers" element={<ComingSoon title="Dossiers" step={3} />} />
          <Route path="suivi" element={<ComingSoon title="Suivi transit" step={3} />} />
          <Route path="factures" element={<ComingSoon title="Factures" step={4} />} />
          <Route path="paiements" element={<ComingSoon title="Paiements" step={5} />} />
          <Route path="tresorerie" element={<ComingSoon title="Trésorerie" step={5} />} />
          <Route path="depenses" element={<ComingSoon title="Dépenses" step={6} />} />
          <Route path="rapports" element={<ComingSoon title="Rapports" step={6} />} />

          <Route element={<RequireRole role="admin" />}>
            <Route path="administration" element={<ComingSoon title="Administration" step={7} />} />
          </Route>

          <Route
            path="*"
            element={
              <div className="panel">
                <div className="empty">
                  <b>Page introuvable</b>
                  Cette adresse n'existe pas.
                </div>
              </div>
            }
          />
        </Route>
      </Route>
    </Routes>
  )
}