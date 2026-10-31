import { useCallback, useState, type ReactNode } from 'react'
import { ToastContext, type ToastFn } from './toast-context'

interface Item {
  id: number
  message: string
  kind: 'ok' | 'bad'
}

export function ToastProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<Item[]>([])

  const push = useCallback<ToastFn>((message, kind = 'ok') => {
    const id = Date.now() + Math.random()
    setItems((list) => [...list, { id, message, kind }])
    setTimeout(() => setItems((list) => list.filter((t) => t.id !== id)), 3500)
  }, [])

  return (
    <ToastContext.Provider value={push}>
      {children}
      <div id="toast" role="status" aria-live="polite">
        {items.map((t) => (
          <div key={t.id} className={'toast' + (t.kind === 'bad' ? ' bad' : '')}>
            {t.message}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}