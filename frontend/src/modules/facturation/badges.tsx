import { STATE_LABEL } from './labels'
import type { DocumentState } from './types'

const STATE_CLASS: Record<DocumentState, string> = {
  emis: 'b-blue',
  annule: 'b-grey',
  transforme: 'b-teal',
}

export function DocStateBadge({ state }: { state: DocumentState }) {
  return <span className={'badge ' + STATE_CLASS[state]}>{STATE_LABEL[state]}</span>
}