import { formatDateTime } from "@/utils/format";
import type {
  SalesConfirmationApprovalStep,
  SalesConfirmationDetail,
} from "@/types";

import { SALES_CONFIRMATION_STATUS } from "./status";

export type TimelineStepState =
  | "done"
  | "current"
  | "todo"
  | "rejected"
  | "skipped";

export interface TimelineStep {
  key: string;
  title: string;
  state: TimelineStepState;
  lines: string[];
  note: string | null;
}

export interface Stage {
  label: string;
  since: string;
}

function joinParts(parts: Array<string | null | undefined>): string {
  return parts.filter(Boolean).join(" · ");
}

function flowSteps(
  detail: SalesConfirmationDetail,
): SalesConfirmationApprovalStep[] {
  return [...(detail.approval_flow?.steps ?? [])].sort(
    (a, b) => a.step_order - b.step_order,
  );
}

export function findRejectedStep(
  detail: SalesConfirmationDetail,
): SalesConfirmationApprovalStep | null {
  return flowSteps(detail).find((step) => step.status === "rejected") ?? null;
}

export function findActiveStep(
  detail: SalesConfirmationDetail,
): SalesConfirmationApprovalStep | null {
  const flow = detail.approval_flow;
  return (
    flowSteps(detail).find(
      (step) =>
        step.step_order === flow?.current_step_order &&
        step.status === "pending",
    ) ?? null
  );
}

export function findNextStep(
  detail: SalesConfirmationDetail,
): SalesConfirmationApprovalStep | null {
  const current = detail.approval_flow?.current_step_order;
  if (current == null) return null;
  return flowSteps(detail).find((step) => step.step_order > current) ?? null;
}

export function buildTimelineSteps(
  detail: SalesConfirmationDetail,
): TimelineStep[] {
  const approval = detail.approval;
  const awaitingDecision =
    detail.sc.disposisi === SALES_CONFIRMATION_STATUS.InApproval;
  const hasRejection = findRejectedStep(detail) !== null;
  const canDecide = detail.approval_flow?.can_decide === true;
  const currentOrder = detail.approval_flow?.current_step_order;

  const adminStep: TimelineStep = approval?.adm_result_date
    ? {
        key: "admin",
        title: "Pengajuan Admin Finance",
        state: "done",
        lines: [
          joinParts([
            approval.adm_pic,
            formatDateTime(approval.adm_result_date),
          ]),
        ],
        note: null,
      }
    : {
        key: "admin",
        title: "Pengajuan Admin Finance",
        state:
          detail.sc.disposisi === SALES_CONFIRMATION_STATUS.PendingAdmin
            ? "current"
            : "done",
        lines: [
          detail.sc.disposisi === SALES_CONFIRMATION_STATUS.PendingAdmin
            ? "Belum diajukan"
            : "Diajukan",
        ],
        note: null,
      };

  const steps = flowSteps(detail);
  const flow = steps.map((step, index): TimelineStep => {
    const base = {
      key: `step-${step.step_order}`,
      title: step.step_name || `Langkah ${step.step_order}`,
    };
    const acted = joinParts([step.actor_name, formatDateTime(step.acted_at)]);

    if (step.status === "approved") {
      return {
        ...base,
        state: "done",
        lines: [acted],
        note: step.decision_note,
      };
    }
    if (step.status === "rejected") {
      return {
        ...base,
        state: "rejected",
        lines: [joinParts(["Ditolak", acted])],
        note: null,
      };
    }
    if (hasRejection || step.status === "cancelled") {
      return {
        ...base,
        state: "skipped",
        lines: ["Tidak dilanjutkan"],
        note: null,
      };
    }
    if (
      awaitingDecision &&
      step.status === "pending" &&
      step.step_order === currentOrder
    ) {
      return {
        ...base,
        state: "current",
        lines: [canDecide ? "Menunggu keputusan Anda" : "Menunggu keputusan"],
        note: null,
      };
    }

    const previous = steps[index - 1];
    return {
      ...base,
      state: "todo",
      lines: [
        previous?.step_name
          ? `Setelah ${previous.step_name} menyetujui`
          : "Menunggu langkah sebelumnya",
      ],
      note: null,
    };
  });

  return [adminStep, ...flow];
}

export function buildStage(detail: SalesConfirmationDetail): Stage {
  const steps = flowSteps(detail);
  const approval = detail.approval;

  switch (detail.sc.disposisi) {
    case SALES_CONFIRMATION_STATUS.Confirmed: {
      const last = [...steps]
        .reverse()
        .find((step) => step.status === "approved");
      return {
        label: "Dikonfirmasi",
        since: last
          ? joinParts([last.actor_name, formatDateTime(last.acted_at)])
          : "",
      };
    }
    case SALES_CONFIRMATION_STATUS.Rejected: {
      const rejected = findRejectedStep(detail);
      return {
        label: joinParts([
          `Ditolak ${rejected?.step_name ?? ""}`.trim(),
          "perlu diajukan ulang",
        ]),
        since: rejected
          ? joinParts([rejected.actor_name, formatDateTime(rejected.acted_at)])
          : "",
      };
    }
    case SALES_CONFIRMATION_STATUS.InApproval: {
      const active = findActiveStep(detail);
      const lastApproved = [...steps]
        .reverse()
        .find((step) => step.status === "approved");
      const since = lastApproved
        ? joinParts([
            `Disetujui ${lastApproved.step_name ?? ""}`.trim(),
            formatDateTime(lastApproved.acted_at),
          ])
        : joinParts([
            `Diajukan ${approval?.adm_pic ?? ""}`.trim(),
            formatDateTime(approval?.adm_result_date),
          ]);

      return {
        label: `Menunggu keputusan ${active?.step_name ?? "approver"}`,
        since,
      };
    }
    default:
      return { label: "Menunggu pengajuan Admin Finance", since: "" };
  }
}

export function decisionOutcome(
  kind: "approve" | "reject",
  nextStepName: string | null,
): string {
  if (kind === "reject") {
    return "Sales Confirmation ditolak dan kembali ke Admin Finance untuk diperbaiki, lalu diajukan ulang. Catatan Anda terlihat oleh Admin Finance.";
  }
  if (nextStepName)
    return `Diteruskan ke ${nextStepName} untuk keputusan berikutnya.`;
  return "SC dikonfirmasi. PO Customer dapat diproses lebih lanjut.";
}
