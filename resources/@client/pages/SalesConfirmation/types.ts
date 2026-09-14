export type UnblockRequestedBy = { id: number; name: string; role_name: string | null };

export type UnblockStep = {
  step_order: number;
  step_name: string | null;
  status: string | null;
  actor_name: string | null;
  acted_at: string | null;
  decision_note: string | null;
};

export type UnblockAttachment = { path: string; original_name: string; size_bytes: number | null };

export type UnblockApproval = {
  current_step_order: number | null;
  steps: UnblockStep[];
};

export type UnblockRequest = {
  id: number;
  id_poc: number;
  reason: string | null;
  attachments: UnblockAttachment[];
  status: string | null;
  status_label: string | null;
  requested_by: UnblockRequestedBy | null;
  created_at: string | null;
  approval: UnblockApproval | null;
};

export type UnblockContextPo = {
  id_poc: number;
  nomor_poc: string | null;
  customer: { customer_code: string | null; company_name: string | null } | null;
  nilai_order: number;
  current_credit_limit: number;
  exposure: number;
  headroom: number;
  status_key: string | null;
  status_label: string | null;
};

export type UnblockContext = { po: UnblockContextPo; active_request: UnblockRequest | null };
