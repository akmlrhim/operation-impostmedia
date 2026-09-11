import { Head, router } from '@inertiajs/react';
import { Columns3, Plus } from 'lucide-react';
import { useState } from 'react';
import { LeadFormModal } from '@/components/crm/lead-form-modal';
import { LeadStageFormModal } from '@/components/crm/lead-stage-form-modal';
import { LeadKanbanBoard } from '@/components/crm/leads/lead-kanban-board';
import { LeadTableTab } from '@/components/crm/leads/lead-table-tab';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { TabNav } from '@/components/crm/tab-nav';
import { Button } from '@/components/ui/button';
import { useRealtime } from '@/hooks/use-realtime';
import { index } from '@/routes/leads';
import type {
  LeadCard,
  LeadStageColumn,
  LeadStageTable,
  Option,
  ServiceOption,
  UserOption,
} from '@/types/crm';

type Tab = 'kanban' | 'table';

type StageOption = { id: number; name: string; color: string; type: string };

type TableFilters = {
  status: string;
  filterStage: number | null;
  sort: string;
  direction: 'asc' | 'desc';
};

type Props = {
  tab: Tab;
  stageOptions: StageOption[];
  stages?: LeadStageColumn[];
  stageTables?: LeadStageTable[];
  total?: number;
  filters?: TableFilters;
  sources: Option[];
  statuses: Option[];
  temperatures: Option[];
  stageTypes: Option[];
  services: ServiceOption[];
  users: UserOption[];
};

type LeadModalState = { lead?: LeadCard; stageId: number | null };
type StageModalState = { stage?: LeadStageColumn };

const TABS: Record<Tab, { label: string }> = {
  kanban: { label: 'Kanban' },
  table: { label: 'Tabel' },
};

export default function LeadsIndex({
  tab,
  stageOptions,
  stages,
  stageTables,
  total,
  filters,
  sources,
  statuses,
  temperatures,
  stageTypes,
  services,
  users,
}: Props) {
  useRealtime(['leads', 'lead-stages'], ['stages', 'stageTables', 'total']);

  const [leadModal, setLeadModal] = useState<LeadModalState | null>(null);
  const [stageModal, setStageModal] = useState<StageModalState | null>(null);

  function applyFilters(next: Partial<TableFilters>) {
    if (!filters) {
      return;
    }

    const merged = { ...filters, ...next };

    router.get(
      index().url,
      {
        tab: 'table',
        status: merged.status,
        filter_stage: merged.filterStage,
        sort: merged.sort,
        direction: merged.direction,
      },
      { preserveState: true, replace: true },
    );
  }

  function toggleSort(column: string) {
    if (!filters) {
      return;
    }

    applyFilters({
      sort: column,
      direction: filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc',
    });
  }

  return (
    <>
      <Head title="Leads" />

      <PageHeader
        title="Leads"
        actions={
          <>
            {tab === 'kanban' && (
              <Button variant="outline" onClick={() => setStageModal({})}>
                <Columns3 className="size-4" />
                Kolom baru
              </Button>
            )}
            <Button
              disabled={stageOptions.length === 0}
              onClick={() => setLeadModal({ stageId: stageOptions[0]?.id ?? null })}
            >
              <Plus className="size-4" />
              Lead baru
            </Button>
          </>
        }
      />

      <PageBody className="h-full overflow-hidden">
        <TabNav
          label="Tampilan daftar lead"
          active={tab}
          tabs={(Object.keys(TABS) as Tab[]).map((value) => ({
            value,
            label: TABS[value].label,
            href: index({ query: { tab: value } }),
          }))}
        />

        {tab === 'kanban' && stages && (
          <LeadKanbanBoard
            stages={stages}
            temperatures={temperatures}
            onAddLead={(stageId) => setLeadModal({ stageId })}
            onEditLead={(lead, stageId) => setLeadModal({ lead, stageId })}
            onAddStage={() => setStageModal({})}
            onEditStage={(stage) => setStageModal({ stage })}
          />
        )}

        {tab === 'table' && stageTables && filters && total !== undefined && (
          <LeadTableTab
            stageTables={stageTables}
            total={total}
            filters={filters}
            stageOptions={stageOptions}
            statuses={statuses}
            temperatures={temperatures}
            onFilterStage={(filterStage) => applyFilters({ filterStage })}
            onFilterStatus={(status) => applyFilters({ status })}
            onSort={toggleSort}
            onEdit={(lead) => setLeadModal({ lead, stageId: lead.stage?.id ?? null })}
          />
        )}
      </PageBody>

      {leadModal && (
        <LeadFormModal
          lead={leadModal.lead}
          stageId={leadModal.stageId}
          stages={stageOptions.map((stage) => ({ id: stage.id, name: stage.name }))}
          sources={sources}
          statuses={statuses}
          temperatures={temperatures}
          services={services}
          users={users}
          onClose={() => setLeadModal(null)}
        />
      )}

      {stageModal && (
        <LeadStageFormModal
          stage={stageModal.stage}
          stageTypes={stageTypes}
          onClose={() => setStageModal(null)}
        />
      )}
    </>
  );
}

LeadsIndex.layout = {
  breadcrumbs: [{ title: 'Leads', href: index() }],
};
