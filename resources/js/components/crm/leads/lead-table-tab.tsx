import { LeadTable } from '@/components/crm/leads/lead-table';
import { Pagination } from '@/components/crm/pagination';
import { Toolbar } from '@/components/crm/toolbar';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { LeadCard, Option, Paginated } from '@/types/crm';

type LeadRow = LeadCard & { stage: { id: number; name: string; color: string } | null };

type StageOption = { id: number; name: string; color: string; type: string };

type TableFilters = {
  status: string;
  filterStage: number | null;
  sort: string;
  direction: 'asc' | 'desc';
};

export function LeadTableTab({
  leads,
  filters,
  stageOptions,
  statuses,
  temperatures,
  onFilterStage,
  onFilterStatus,
  onSort,
  onEdit,
}: {
  leads: Paginated<LeadRow>;
  filters: TableFilters;
  stageOptions: StageOption[];
  statuses: Option[];
  temperatures: Option[];
  onFilterStage: (stageId: number | null) => void;
  onFilterStatus: (status: string) => void;
  onSort: (column: string) => void;
  onEdit: (lead: LeadRow) => void;
}) {
  return (
    <>
      <Toolbar trailing={`${leads.total} lead`}>
        <Select
          value={filters.filterStage ? String(filters.filterStage) : 'all'}
          onValueChange={(value) => onFilterStage(value === 'all' ? null : Number(value))}
        >
          <SelectTrigger className="w-full sm:w-48">
            <SelectValue placeholder="Semua kolom" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Semua kolom</SelectItem>
            {stageOptions.map((stage) => (
              <SelectItem key={stage.id} value={String(stage.id)}>
                {stage.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Select
          value={filters.status || 'all'}
          onValueChange={(value) => onFilterStatus(value === 'all' ? '' : value)}
        >
          <SelectTrigger className="w-full sm:w-48">
            <SelectValue placeholder="Semua status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Semua status</SelectItem>
            {statuses.map((option) => (
              <SelectItem key={option.value} value={option.value}>
                {option.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </Toolbar>

      <LeadTable
        leads={leads.data}
        statuses={statuses}
        temperatures={temperatures}
        sort={filters.sort}
        direction={filters.direction}
        onSort={onSort}
        onEdit={onEdit}
      />

      <Pagination meta={leads} />
    </>
  );
}
