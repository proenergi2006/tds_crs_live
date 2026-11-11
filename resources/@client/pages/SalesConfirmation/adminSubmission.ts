import type { SalesConfirmationAdminAttachment } from '@/types'

export interface AttachmentKind {
  icon: 'FileText' | 'FileImage' | 'File'
  iconClass: string
  label: string
}

export function resolveAttachmentKind(name: string): AttachmentKind {
  const ext = name.split('.').pop()?.toLowerCase() ?? ''
  if (ext === 'pdf') return { icon: 'FileText', iconClass: 'bg-red-100 text-red-600', label: 'File PDF' }
  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
    return { icon: 'FileImage', iconClass: 'bg-blue-100 text-blue-600', label: 'File gambar' }
  }
  return { icon: 'File', iconClass: 'bg-slate-100 text-slate-600', label: 'File' }
}

export function adminAttachmentName(file: SalesConfirmationAdminAttachment): string {
  return file.original_name || file.path.split('/').pop() || file.path
}

export function normalizeAdminSummary(value: string | null | undefined): string {
  return String(value ?? '').replace(/<br\s*\/?>(\r?\n)?/gi, (_match, newline) => (newline ? newline : '\n'))
}
