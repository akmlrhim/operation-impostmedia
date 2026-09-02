import type { LeadStageColumn } from '@/types/crm';

export function withMovedLead(
  stages: LeadStageColumn[],
  leadId: number,
  stageId: number,
  position: number,
): LeadStageColumn[] {
  const lead = stages.flatMap((stage) => stage.leads).find((item) => item.id === leadId);

  if (!lead) {
    return stages;
  }

  return stages.map((stage) => {
    const leads = stage.leads.filter((item) => item.id !== leadId);
    const wasHere = leads.length !== stage.leads.length;
    const isTarget = stage.id === stageId;

    if (isTarget) {
      leads.splice(position, 0, lead);
    }

    const shift = (isTarget ? 1 : 0) - (wasHere ? 1 : 0);

    return {
      ...stage,
      leads,
      count: stage.count + shift,
      total: stage.total + shift * Number(lead.estimated_value),
    };
  });
}
