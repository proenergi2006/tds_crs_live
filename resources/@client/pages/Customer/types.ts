export interface CustomerDocumentType {
  id: number
  code: string
  name: string
  is_active: boolean
  category: string | null
}

export interface CustomerDocumentRecord {
  id: number
  id_customer: number
  id_document_type: number | null
  document_type: { id: number; code: string; name: string } | null
  document_name: string | null
  document_number: string | null
  file_name: string
  file_path: string
  url: string | null
  uploaded_at: string | null
  uploaded_by: { id: number; name: string } | null
}

export interface DocumentRowState {
  file: File | null
  documentNumber: string
  uploading: boolean
  error: string
}

export interface NewFreeFormRow {
  label: string
  file: File | null
  error: string
}

export interface CustomerContactRecord {
  id: number
  id_customer: number
  id_lcr: number | null
  full_name: string
  position: string | null
  phone: string | null
  mobile: string | null
  email: string | null
  created_at: string | null
  updated_at: string | null
}
