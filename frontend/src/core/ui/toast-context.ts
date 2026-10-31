import { createContext, useContext } from 'react'

export type ToastFn = (message: string, kind?: 'ok' | 'bad') => void

export const ToastContext = createContext<ToastFn>(() => {})
export const useToast = () => useContext(ToastContext)