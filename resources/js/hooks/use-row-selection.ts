import { useMemo, useState } from 'react';

export function useRowSelection<T extends { id: number }>(rows: T[]) {
  const ids = useMemo(() => rows.map((row) => row.id), [rows]);
  const idsKey = ids.join(',');

  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [trackedKey, setTrackedKey] = useState(idsKey);

  // Row set changed (new page, new filters) — ids no longer on screen shouldn't stay selected.
  if (trackedKey !== idsKey) {
    setTrackedKey(idsKey);
    setSelected(new Set());
  }

  const allSelected = ids.length > 0 && ids.every((id) => selected.has(id));
  const someSelected = selected.size > 0 && !allSelected;

  function toggle(id: number) {
    setSelected((prev) => {
      const next = new Set(prev);

      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }

      return next;
    });
  }

  function toggleAll() {
    setSelected(allSelected ? new Set() : new Set(ids));
  }

  function clear() {
    setSelected(new Set());
  }

  return { selected, toggle, toggleAll, clear, allSelected, someSelected, count: selected.size };
}
