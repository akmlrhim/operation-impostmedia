import { useState } from 'react';

const SETTLE_GUARD_DELAY = 220 + 80;
const SETTLE_GUARD_DURATION = 900;

export function useSettleGuard() {
  const [settledIds, setSettledIds] = useState<Set<string>>(new Set());

  function guardAfterSettle(id: string) {
    setTimeout(() => {
      setSettledIds((prev) => new Set(prev).add(id));

      setTimeout(() => {
        setSettledIds((prev) => {
          if (!prev.has(id)) {
            return prev;
          }

          const next = new Set(prev);
          next.delete(id);

          return next;
        });
      }, SETTLE_GUARD_DURATION);
    }, SETTLE_GUARD_DELAY);
  }

  return { settledIds, guardAfterSettle };
}
