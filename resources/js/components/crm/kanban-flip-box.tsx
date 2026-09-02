import { useLayoutEffect, useRef } from 'react';
import type { HTMLAttributes } from 'react';

const FLIP_DURATION = 220;
const FLIP_EASING = 'cubic-bezier(0.2, 0, 0, 1)';

function prefersReducedMotion() {
  return (
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
  );
}

export function FlipBox({
  id,
  rects,
  disabled,
  className,
  children,
  ...props
}: {
  id: string;
  rects: React.MutableRefObject<Map<string, DOMRect>>;
  disabled?: boolean;
} & HTMLAttributes<HTMLDivElement>) {
  const ref = useRef<HTMLDivElement>(null);
  const cancelRef = useRef<() => void>(() => {});

  useLayoutEffect(() => {
    const el = ref.current;

    if (!el) {
      return;
    }

    cancelRef.current();
    cancelRef.current = () => {};

    const rect = el.getBoundingClientRect();
    const prev = rects.current.get(id);
    rects.current.set(id, rect);

    if (!prev || disabled || prefersReducedMotion()) {
      el.style.transition = '';
      el.style.transform = '';

      return;
    }

    const dx = prev.left - rect.left;
    const dy = prev.top - rect.top;

    if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) {
      return;
    }

    el.style.transition = 'none';
    el.style.transform = `translate(${dx}px, ${dy}px)`;
    el.getBoundingClientRect();

    const frame = requestAnimationFrame(() => {
      el.style.transition = `transform ${FLIP_DURATION}ms ${FLIP_EASING}`;
      el.style.transform = '';
    });

    const clear = () => {
      el.style.transition = '';
    };
    el.addEventListener('transitionend', clear, { once: true });

    cancelRef.current = () => {
      cancelAnimationFrame(frame);
      el.removeEventListener('transitionend', clear);
      el.style.transition = '';
      el.style.transform = '';
    };
  });

  return (
    <div ref={ref} className={className} {...props}>
      {children}
    </div>
  );
}

export function DropGhost({ height }: { height: number }) {
  return (
    <div
      aria-hidden
      className="mb-2 shrink-0 rounded-xl border-2 border-dashed border-primary/50 bg-primary/5 motion-safe:animate-in motion-safe:duration-150 motion-safe:fade-in"
      style={{ height }}
    />
  );
}
