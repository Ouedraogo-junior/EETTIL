import { useMe } from '@/modules/auth/hooks'

export default function DashboardPage() {
  const { data: user } = useMe()
  return (
    <>
      <div className="ph">
        <div>
          <h1>Tableau de bord</h1>
          <div className="sub">Bienvenue, {user?.name}.</div>
        </div>
      </div>
      <div className="panel">
        <div className="empty">
          <b>Connexion opérationnelle</b>
          Les indicateurs seront ajoutés à l'étape 6. Connecté en tant que « {user?.login} » ({user?.role_label}).
        </div>
      </div>
    </>
  )
}