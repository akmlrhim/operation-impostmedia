import { router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useMemo } from 'react';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { Kanban } from '@/components/crm/kanban';
import { LeadKanbanCard } from '@/components/crm/leads/lead-kanban-card';
import { LeadKanbanColumnHeader } from '@/components/crm/leads/lead-kanban-column-header';
import { withMovedLead } from '@/components/crm/leads/lead-kanban-utils';
import { useCan } from '@/lib/use-can';
import { destroy as destroyStage, reorder as reorderStages } from '@/routes/lead-stages';
import { convert, destroy, move } from '@/routes/leads';
import type { LeadCard, LeadStageColumn, Option } from '@/types/crm';

export function LeadKanbanBoard({
  stages,
  temperatures,
  onAddLead,
  onEditLead,
  onAddStage,
  onEditStage,
}: {
  stages: LeadStageColumn[];
  temperatures: Option[];
  onAddLead: (stageId: number) => void;
  onEditLead: (lead: LeadCard, stageId: number) => void;
  onAddStage: () => void;
  onEditStage: (stage: LeadStageColumn) => void;
}) {
  const [confirm, confirmDialog] = useConfirm();
  const can = useCan();

  const columns = useMemo(
    () => stages.map((stage) => ({ id: stage.id, items: stage.leads })),
    [stages],
  );

  function stageIdOf(lead: LeadCard): number {
    return stages.find((stage) => stage.leads.some((l) => l.id === lead.id))?.id ?? 0;
  }

  async function removeStage(stage: LeadStageColumn) {
    const confirmed = await confirm({
      title: `Hapus kolom ${stage.name}?`,
      description:
        stage.count > 0
          ? `Kolom ini masih berisi ${stage.count} lead. Pindahkan dulu sebelum menghapus.`
          : 'Kolom yang sudah dihapus tidak bisa dikembalikan.',
      confirmLabel: 'Hapus kolom',
      destructive: true,
    });

    if (confirmed) {
      router.delete(destroyStage(stage.id), { preserveScroll: true });
    }
  }

  async function removeLead(lead: LeadCard) {
    const confirmed = await confirm({
      title: `Hapus lead ${lead.company_name}?`,
      description: 'Riwayat aktivitas lead ini ikut terhapus.',
      confirmLabel: 'Hapus lead',
      destructive: true,
    });

    if (confirmed) {
      router.delete(destroy(lead.id), { preserveScroll: true });
    }
  }

  return (
    <div className="min-h-0 flex-1">
      <Kanban
        columns={columns}
        getItemId={(lead) => lead.id}
        getItemColumnId={stageIdOf}
        onMove={(leadId, stageId, position) =>
          router.post(
            move(leadId),
            { lead_stage_id: stageId, position },
            {
              preserveScroll: true,
              preserveState: true,
              showProgress: false,
              async: true,
              optimistic: (props) => ({
                stages: withMovedLead(props.stages as LeadStageColumn[], leadId, stageId, position),
              }),
            },
          )
        }
        onReorderColumns={
          can['manage-lead-stages']
            ? (ids) =>
                router.post(
                  reorderStages(),
                  { ids },
                  {
                    preserveScroll: true,
                    preserveState: true,
                    showProgress: false,
                    async: true,
                    optimistic: (props) => ({
                      stages: ids
                        .map((id) => (props.stages as LeadStageColumn[]).find((s) => s.id === id))
                        .filter((stage): stage is LeadStageColumn => stage !== undefined),
                    }),
                  },
                )
            : undefined
        }
        renderHeader={(column) => {
          const stage = stages.find((s) => s.id === column.id);

          return (
            stage && (
              <LeadKanbanColumnHeader
                stage={stage}
                onAddLead={() => onAddLead(stage.id)}
                onEditStage={() => onEditStage(stage)}
                onRemoveStage={() => removeStage(stage)}
              />
            )
          );
        }}
        renderEmpty={(column) => (
          <button
            type="button"
            onClick={() => onAddLead(column.id)}
            className="flex cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-dashed py-8 text-xs text-muted-foreground transition-colors duration-150 hover:border-primary/40 hover:bg-accent hover:text-foreground motion-reduce:transition-none"
          >
            <Plus className="size-3.5" />
            Tambah lead
          </button>
        )}
        trailing={
          can['manage-lead-stages'] && (
            <button
              type="button"
              onClick={onAddStage}
              className="mt-8 flex h-fit w-76 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-dashed py-3 text-xs text-muted-foreground transition-colors duration-150 hover:border-primary/40 hover:bg-accent hover:text-foreground motion-reduce:transition-none"
            >
              <Plus className="size-3.5" />
              Tambah kolom
            </button>
          )
        }
        renderItem={(lead) => (
          <LeadKanbanCard
            lead={lead}
            temperatures={temperatures}
            onEdit={() => onEditLead(lead, stageIdOf(lead))}
            onConvert={() => router.post(convert(lead.id))}
            onDelete={() => removeLead(lead)}
          />
        )}
      />

      {confirmDialog}
    </div>
  );
}
