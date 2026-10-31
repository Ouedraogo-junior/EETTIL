import { useEffect, useState } from 'react'

/** Retarde la prise en compte d'une valeur (ex. saisie de recherche) pour éviter un appel par frappe. */
export function useDebouncedValue<T>(value: T, delay = 300): T {
  const [debounced, setDebounced] = useState(value)
  useEffect(() => {
    const t = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(t)
  }, [value, delay])
  return debounced
}