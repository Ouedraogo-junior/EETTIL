import { useState } from 'react'
import { Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import logo from '@/assets/logo.jpg'
import { apiError } from '@/core/api/errors'
import { useLogin, useMe } from './hooks'

const schema = z.object({
  login: z.string().min(1, 'Saisissez votre pseudo.'),
  password: z.string().min(1, 'Saisissez votre mot de passe.'),
})
type Values = z.infer<typeof schema>

export default function LoginPage() {
  const { data: user, isPending } = useMe()
  const loginMutation = useLogin()
  const navigate = useNavigate()
  const location = useLocation()
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<Values>({ resolver: zodResolver(schema) })

  if (isPending) return null
  if (user) return <Navigate to="/" replace />

  const from = (location.state as { from?: string } | null)?.from ?? '/'

  const onSubmit = async (values: Values) => {
    setFormError(null)
    try {
      await loginMutation.mutateAsync({ login: values.login.trim(), password: values.password })
      navigate(from, { replace: true })
    } catch (e) {
      setFormError(apiError(e))
    }
  }

  return (
    <div className="login">
      <section className="login-art">
        <div className="logo-card">
          <img src={logo} alt="EETTIL" />
        </div>
        <div>
          <h1>Gestion des dossiers, de la facturation et de la trésorerie</h1>
          <p>
            Suivez chaque dossier, de la proforma jusqu'au dernier paiement et à la livraison, et sachez à tout
            moment ce que chacun rapporte.
          </p>
        </div>
        <p style={{ fontSize: 13 }}>Transport · Logistique · Manutention · Dédouanement aérien, maritime et routier</p>
      </section>

      <section className="login-form">
        <h2>Connexion</h2>
        <p className="sub">Espace réservé au personnel d'EETTIL.</p>
        <form onSubmit={handleSubmit(onSubmit)} noValidate>
          <div className="field">
            <label htmlFor="login">Pseudo</label>
            <input
              id="login"
              className={'inp' + (errors.login ? ' err' : '')}
              autoComplete="username"
              autoFocus
              {...register('login')}
            />
            {errors.login && <span className="errmsg">{errors.login.message}</span>}
          </div>
          <div className="field">
            <label htmlFor="password">Mot de passe</label>
            <input
              id="password"
              type="password"
              className={'inp' + (errors.password ? ' err' : '')}
              autoComplete="current-password"
              {...register('password')}
            />
            {errors.password && <span className="errmsg">{errors.password.message}</span>}
          </div>
          {formError && (
            <p className="errmsg" role="alert" style={{ marginBottom: 14 }}>
              {formError}
            </p>
          )}
          <button
            className="btn primary"
            type="submit"
            disabled={loginMutation.isPending}
            style={{ width: '100%', justifyContent: 'center', height: 42 }}
          >
            {loginMutation.isPending ? 'Connexion…' : 'Se connecter'}
          </button>
        </form>
      </section>
    </div>
  )
}