import type { DocumentState, DocumentType } from './types'

export const DOC_LABEL: Record<DocumentType, string> = {
  proforma: 'Quotation',
  facture: 'Facture',
}

export const docLabel = (type: DocumentType) => DOC_LABEL[type]

export const STATE_LABEL: Record<DocumentState, string> = {
  emis: 'Émise',
  annule: 'Annulée',
  transforme: 'Transformée en facture',
}