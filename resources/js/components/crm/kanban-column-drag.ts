import { useEffect, useRef, useState } from 'react';

const AUTO_SCROLL_EDGE = 72;
const AUTO_SCROLL_SPEED = 18;

type Slot = { start: number; end: number };

export function useColumnReorder({
  ids,
  onCommit,
}: {
  ids: number[];
  onCommit: (ids: number[]) => void;
}) {
  const containerRef = useRef<HTMLDivElement>(null);
  const [draggingColumn, setDraggingColumn] = useState<number | null>(null);
  const [previewIds, setPreviewIds] = useState<number[] | null>(null);

  const draggingRef = useRef<number | null>(null);
  const orderRef = useRef<number[] | null>(null);
  const slotsRef = useRef<Slot[]>([]);
  const boundsRef = useRef({ left: 0, right: 0 });
  const pointerRef = useRef(0);
  const directionRef = useRef(0);
  const frameRef = useRef<number | null>(null);

  useEffect(
    () => () => {
      if (frameRef.current !== null) {
        cancelAnimationFrame(frameRef.current);
      }
    },
    [],
  );

  function stopAutoScroll() {
    if (frameRef.current !== null) {
      cancelAnimationFrame(frameRef.current);
    }

    frameRef.current = null;
    directionRef.current = 0;
  }

  function measureSlots(container: HTMLDivElement) {
    slotsRef.current = Array.from(
      container.querySelectorAll<HTMLElement>('[data-kanban-column]'),
    ).map((node) => ({ start: node.offsetLeft, end: node.offsetLeft + node.offsetWidth }));
  }

  function slotAtPointer(container: HTMLDivElement): number {
    const slots = slotsRef.current;

    if (slots.length === 0) {
      return -1;
    }

    const x = pointerRef.current - boundsRef.current.left + container.scrollLeft;

    if (x < slots[0].start) {
      return 0;
    }

    if (x >= slots[slots.length - 1].end) {
      return slots.length - 1;
    }

    for (let index = 0; index < slots.length; index++) {
      if (x >= slots[index].start && x < slots[index].end) {
        return index;
      }
    }

    return -1;
  }

  function applyPointer(container: HTMLDivElement) {
    const order = orderRef.current;
    const dragged = draggingRef.current;

    if (!order || dragged === null) {
      return;
    }

    const from = order.indexOf(dragged);
    const to = slotAtPointer(container);

    if (from === -1 || to === -1 || to === from) {
      return;
    }

    const next = order.slice();
    next.splice(to, 0, ...next.splice(from, 1));
    orderRef.current = next;
    setPreviewIds(next);
  }

  function runAutoScroll() {
    if (frameRef.current !== null) {
      return;
    }

    const step = () => {
      const container = containerRef.current;

      if (!container || directionRef.current === 0) {
        frameRef.current = null;

        return;
      }

      const before = container.scrollLeft;
      container.scrollLeft = before + directionRef.current * AUTO_SCROLL_SPEED;

      if (container.scrollLeft !== before) {
        applyPointer(container);
      }

      frameRef.current = requestAnimationFrame(step);
    };

    frameRef.current = requestAnimationFrame(step);
  }

  function updateAutoScroll(container: HTMLDivElement) {
    const { left, right } = boundsRef.current;
    const x = pointerRef.current;
    const atStart = container.scrollLeft <= 0;
    const atEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 1;

    if (x - left < AUTO_SCROLL_EDGE && !atStart) {
      directionRef.current = -1;
    } else if (right - x < AUTO_SCROLL_EDGE && !atEnd) {
      directionRef.current = 1;
    } else {
      directionRef.current = 0;
    }

    if (directionRef.current === 0) {
      stopAutoScroll();
    } else {
      runAutoScroll();
    }
  }

  function startColumnDrag(id: number) {
    const container = containerRef.current;

    if (!container) {
      return;
    }

    const rect = container.getBoundingClientRect();
    boundsRef.current = { left: rect.left, right: rect.right };
    measureSlots(container);

    draggingRef.current = id;
    orderRef.current = ids.slice();

    setDraggingColumn(id);
    setPreviewIds(null);
  }

  function trackColumnPointer(clientX: number) {
    const container = containerRef.current;

    if (!container || draggingRef.current === null) {
      return;
    }

    const rect = container.getBoundingClientRect();
    boundsRef.current = { left: rect.left, right: rect.right };
    pointerRef.current = clientX;

    updateAutoScroll(container);
    applyPointer(container);
  }

  function finishColumnDrag(commit: boolean) {
    stopAutoScroll();

    if (draggingRef.current === null) {
      return;
    }

    const order = orderRef.current;

    draggingRef.current = null;
    orderRef.current = null;
    slotsRef.current = [];

    setDraggingColumn(null);
    setPreviewIds(null);

    if (commit && order && order.join() !== ids.join()) {
      onCommit(order);
    }
  }

  return {
    containerRef,
    draggingColumn,
    previewIds,
    startColumnDrag,
    trackColumnPointer,
    finishColumnDrag,
  };
}
