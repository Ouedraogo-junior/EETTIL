import axios from 'axios'

// Même origine (proxy Vite en dev, même domaine en prod) : le cookie XSRF-TOKEN
// est relu et renvoyé automatiquement par axios dans l'en-tête X-XSRF-TOKEN.
export const http = axios.create({
  baseURL: '/',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export const ensureCsrfCookie = () => http.get('/sanctum/csrf-cookie')