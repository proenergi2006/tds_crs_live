export type FormMode = "create" | "edit";

export interface EnumOption<T extends string = string> {
  value: T;
  label: string;
}

export interface TransporterRef {
  id: number;
  company_name: string;
}

export type LogisticParentType = "transporters" | "personnels" | "vessels" | "trucks";

export interface LogisticDocument {
  id: number;
  documentable_type: string;
  documentable_id: number;
  document_type: EnumOption;
  file_url: string | null;
  file_name: string | null;
  valid_until: string | null;
  uploaded_at: string | null;
  uploader: { id: number; name: string } | null;
  created_at: string;
}

export interface DeleteConflict<TDependents> {
  message: string;
  dependents: TDependents | null;
}

export interface StagedDocument {
  document_type: string;
  file: File;
  valid_until: string;
}
