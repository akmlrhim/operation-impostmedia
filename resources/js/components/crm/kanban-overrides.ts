import { useEffect, useMemo, useState } from 'react';
import type { KanbanColumn } from '@/components/crm/kanban-types';

export function useKanbanOverrides<T>(columns: KanbanColumn<T>[], getItemId: (item: T) => number) {
  const [cardOverride, setCardOverride] = useState<{
    itemId: number;
    columnId: number;
    index: number;
  } | null>(null);
  const [columnOverride, setColumnOverride] = useState<number[] | null>(null);

  const [columnsSeen, setColumnsSeen] = useState(columns);

  if (columns !== columnsSeen) {
    setColumnsSeen(columns);

    if (cardOverride) {
      const column = columns.find((c) => c.id === cardOverride.columnId);
      const index = column?.items.findIndex((item) => getItemId(item) === cardOverride.itemId);

      if (index === cardOverride.index) {
        setCardOverride(null);
      }
    }

    if (
      columnOverride &&
      columns.map((column) => column.id).join(',') === columnOverride.join(',')
    ) {
      setColumnOverride(null);
    }
  }

  useEffect(() => {
    if (!cardOverride) {
      return;
    }

    const timeout = setTimeout(() => setCardOverride(null), 4000);

    return () => clearTimeout(timeout);
  }, [cardOverride]);

  useEffect(() => {
    if (!columnOverride) {
      return;
    }

    const timeout = setTimeout(() => setColumnOverride(null), 4000);

    return () => clearTimeout(timeout);
  }, [columnOverride]);

  const renderedColumns = useMemo(() => {
    let next = columns;

    if (columnOverride) {
      const byId = new Map(next.map((column) => [column.id, column]));
      const ordered = columnOverride
        .map((id) => byId.get(id))
        .filter((column): column is KanbanColumn<T> => column !== undefined);

      if (ordered.length === next.length) {
        next = ordered;
      }
    }

    if (cardOverride) {
      const item = next
        .flatMap((column) => column.items)
        .find((candidate) => getItemId(candidate) === cardOverride.itemId);

      if (item) {
        next = next.map((column) => {
          const items = column.items.filter(
            (candidate) => getItemId(candidate) !== cardOverride.itemId,
          );

          if (column.id === cardOverride.columnId) {
            items.splice(cardOverride.index, 0, item);
          }

          return { ...column, items };
        });
      }
    }

    return next;
  }, [columns, cardOverride, columnOverride, getItemId]);

  return { renderedColumns, setCardOverride, setColumnOverride };
}
