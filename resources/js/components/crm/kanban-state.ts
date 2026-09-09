import { useMemo, useRef, useState } from 'react';

import { useColumnReorder } from '@/components/crm/kanban-column-drag';
import { useKanbanOverrides } from '@/components/crm/kanban-overrides';
import { useSettleGuard } from '@/components/crm/kanban-settle-guard';
import type { DropTarget, KanbanColumn } from '@/components/crm/kanban-types';

export type { KanbanColumn, DropTarget };

const LANDING_HOLD = 400;

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
  const [justMovedId, setJustMovedId] = useState<string | null>(null);
  const rects = useRef(new Map<string, DOMRect>());

  const { settledIds, guardAfterSettle } = useSettleGuard();
  const {
    renderedColumns: baseColumns,
    setCardOverride,
    setColumnOverride,
  } = useKanbanOverrides(columns, getItemId);

  const columnIds = useMemo(() => baseColumns.map((column) => column.id), [baseColumns]);

  const {
    containerRef,
    draggingColumn,
    previewIds,
    startColumnDrag,
    trackColumnPointer,
    finishColumnDrag,
  } = useColumnReorder({
    ids: columnIds,
    onCommit: (ids) => {
      setColumnOverride(ids);
      onReorderColumns?.(ids);
    },
  });

  const renderedColumns = useMemo(() => {
    if (!previewIds) {
      return baseColumns;
    }

    const byId = new Map(baseColumns.map((column) => [column.id, column]));
    const ordered = previewIds
      .map((id) => byId.get(id))
      .filter((column): column is KanbanColumn<T> => column !== undefined);

    return ordered.length === baseColumns.length ? ordered : baseColumns;
  }, [baseColumns, previewIds]);

  function reset() {
    setDragging(null);
    setTarget(null);
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

    const movedId = `card:${itemId}`;
    setJustMovedId(movedId);
    setTimeout(() => {
      setJustMovedId((current) => (current === movedId ? null : current));
    }, LANDING_HOLD);

    const affectedColumnIds = new Set([columnId, fromColumn]);

    renderedColumns
      .filter((c) => affectedColumnIds.has(c.id))
      .flatMap((c) => c.items)
      .forEach((affectedItem) => guardAfterSettle(`card:${getItemId(affectedItem)}`));

    onMove(itemId, columnId, index);
    reset();
  }

  return {
    containerRef,
    renderedColumns,
    rects,
    settledIds,
    justMovedId,
    dragging,
    setDragging,
    draggingHeight,
    setDraggingHeight,
    target,
    setTarget,
    draggingColumn,
    startColumnDrag,
    trackColumnPointer,
    finishColumnDrag,
    reset,
    handleDrop,
  };
}
