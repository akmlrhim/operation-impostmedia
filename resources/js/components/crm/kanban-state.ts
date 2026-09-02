import { useRef, useState } from 'react';
import { useKanbanOverrides } from '@/components/crm/kanban-overrides';
import { useSettleGuard } from '@/components/crm/kanban-settle-guard';
import type { DropTarget, KanbanColumn } from '@/components/crm/kanban-types';

export type { KanbanColumn, DropTarget };

export function useKanbanState<T>({
  columns,
  getItemId,
  getItemColumnId,
  onMove,
  onReorderColumns,
}: {
  columns: KanbanColumn<T>[];
  getItemId: (item: T) => number;
  getItemColumnId: (item: T) => number;
  onMove: (itemId: number, columnId: number, position: number) => void;
  onReorderColumns?: (ids: number[]) => void;
}) {
  const [dragging, setDragging] = useState<T | null>(null);
  const [draggingHeight, setDraggingHeight] = useState(96);
  const [target, setTarget] = useState<DropTarget | null>(null);
  const [draggingColumn, setDraggingColumn] = useState<number | null>(null);
  const [columnTarget, setColumnTarget] = useState<number | null>(null);
  const rects = useRef(new Map<string, DOMRect>());

  const { settledIds, guardAfterSettle } = useSettleGuard();
  const { renderedColumns, setCardOverride, setColumnOverride } = useKanbanOverrides(
    columns,
    getItemId,
  );

  function reset() {
    setDragging(null);
    setTarget(null);
  }

  function resetColumn() {
    setDraggingColumn(null);
    setColumnTarget(null);
  }

  function handleDrop(columnId: number) {
    if (!dragging) {
      return;
    }

    const itemId = getItemId(dragging);
    const fromColumn = getItemColumnId(dragging);
    const column = renderedColumns.find((c) => c.id === columnId);
    let index = target?.index ?? column?.items.length ?? 0;

    if (fromColumn === columnId) {
      const currentIndex = column?.items.findIndex((item) => getItemId(item) === itemId);

      if (currentIndex !== undefined && currentIndex > -1) {
        if (index > currentIndex) {
          index -= 1;
        }

        if (index === currentIndex) {
          reset();

          return;
        }
      }
    }

    setCardOverride({ itemId, columnId, index });

    const affectedColumnIds = new Set([columnId, fromColumn]);

    renderedColumns
      .filter((c) => affectedColumnIds.has(c.id))
      .flatMap((c) => c.items)
      .forEach((affectedItem) => guardAfterSettle(`card:${getItemId(affectedItem)}`));

    onMove(itemId, columnId, index);
    reset();
  }

  function handleColumnDrop(overId: number) {
    if (draggingColumn === null || draggingColumn === overId) {
      resetColumn();

      return;
    }

    const ids = renderedColumns.map((column) => column.id);
    const from = ids.indexOf(draggingColumn);
    const to = ids.indexOf(overId);

    if (from === -1 || to === -1) {
      resetColumn();

      return;
    }

    ids.splice(to, 0, ...ids.splice(from, 1));
    setColumnOverride(ids);
    ids.forEach((id) => guardAfterSettle(`col:${id}`));
    onReorderColumns?.(ids);
    resetColumn();
  }

  return {
    renderedColumns,
    rects,
    settledIds,
    dragging,
    setDragging,
    draggingHeight,
    setDraggingHeight,
    target,
    setTarget,
    draggingColumn,
    setDraggingColumn,
    columnTarget,
    setColumnTarget,
    reset,
    resetColumn,
    handleDrop,
    handleColumnDrop,
  };
}
