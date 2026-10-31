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

/** Nom du fichier PDF : « FCT-2026-000-011 - Nom du client.pdf ». */
export function pdfFileName(number: string, clientName?: string): string {
  const base = number.replace(/\//g, '-') + (clientName ? ` - ${clientName}` : '')
  return base.replace(/[\\/:*?"<>|]+/g, ' ').replace(/\s+/g, ' ').trim() + '.pdf'
}