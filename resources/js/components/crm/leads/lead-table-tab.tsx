import { LeadTable } from '@/components/crm/leads/lead-table';
import { Toolbar } from '@/components/crm/toolbar';
import { Card } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { LeadStageTable, Option } from '@/types/crm';

type StageOption = { id: number; name: string; color: string; type: string };

type TableFilters = {
  status: string;
  filterStage: number | null;
  sort: string;
  direction: 'asc' | 'desc';
};

export function LeadTableTab({
  stageTables,
  total,
  filters,
  stageOptions,
  statuses,
  temperatures,
  onFilterStage,
  onFilterStatus,
  onSort,
  onEdit,
}: {
  stageTables: LeadStageTable[];
  total: number;
  filters: TableFilters;
  stageOptions: StageOption[];
  statuses: Option[];
  temperatures: Option[];
  onFilterStage: (stageId: number | null) => void;
  onFilterStatus: (status: string) => void;
  onSort: (column: string) => void;
  onEdit: (lead: LeadStageTable['leads'][number]) => void;
}) {
  return (
    <div className="flex flex-col gap-3">
      <Toolbar trailing={`${total} lead`}>
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

      {total === 0 ? (
        <Card className="py-8 text-center text-sm text-muted-foreground">
          Belum ada lead yang cocok.
        </Card>
      ) : (
        stageTables.map((stage) => (
          <LeadTable
            key={stage.id ?? 'none'}
            stage={stage}
            leads={stage.leads}
            statuses={statuses}
            temperatures={temperatures}
            sort={filters.sort}
            direction={filters.direction}
            onSort={onSort}
            onEdit={onEdit}
          />
        ))
      )}
    </div>
  );
}