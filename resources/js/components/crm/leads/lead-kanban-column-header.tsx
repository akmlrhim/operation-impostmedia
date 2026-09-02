import { MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import type { LeadStageColumn } from '@/types/crm';

function StageDot({ color, type }: { color: string; type: string }) {
  const isClosing = type !== 'open';

  return (
    <span
      aria-hidden
      className={cn('size-2.5 shrink-0 rounded-full', !isClosing && 'border-2')}
      style={isClosing ? { backgroundColor: color } : { borderColor: color }}
    />
  );
}

export function LeadKanbanColumnHeader({
  stage,
  onAddLead,
  onEditStage,
  onRemoveStage,
}: {
  stage: LeadStageColumn;
  onAddLead: () => void;
  onEditStage: () => void;
  onRemoveStage: () => void;
}) {
  return (
    <>
      <StageDot color={stage.color} type={stage.type} />
      <h2 className="min-w-0 truncate text-sm font-semibold">{stage.name}</h2>
      <span
        className="shrink-0 text-sm text-muted-foreground"
        title={
          stage.leads.length < stage.count
            ? `Menampilkan ${stage.leads.length} kartu teratas dari ${stage.count} lead. Buka tab Tabel untuk melihat semuanya.`
            : undefined
        }
      >
        {stage.leads.length < stage.count ? `${stage.leads.length}/${stage.count}` : stage.count}
      </span>

      <Button
        variant="ghost"
        size="icon"
        className="ml-auto size-6 shrink-0 text-muted-foreground"
        draggable={false}
        aria-label={`Tambah lead di kolom ${stage.name}`}
        onClick={onAddLead}
      >
        <Plus className="size-3.5" />
      </Button>

      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="ghost"
            size="icon"
            className="size-6 shrink-0 text-muted-foreground"
            aria-label={`Kelola kolom ${stage.name}`}
          >
            <MoreHorizontal className="size-3.5" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem onSelect={onEditStage}>
            <Pencil className="size-3.5" />
            Ubah kolom
          </DropdownMenuItem>
          <DropdownMenuItem variant="destructive" onSelect={onRemoveStage}>
            <Trash2 className="size-3.5" />
            Hapus kolom
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </>
  );
}
