import { cn } from '@/lib/utils';

const tints = [
  'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
  'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
  'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
  'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
  'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
];

function tintFor(name: string): string {
  let hash = 0;

  for (const char of name) {
    hash = (hash * 31 + char.codePointAt(0)!) % 0xffffffff;
  }

  return tints[hash % tints.length];
}

export function InitialBadge({ name, className }: { name: string; className?: string }) {
  const initial = Array.from(name.trim())[0]?.toUpperCase() ?? '?';

  return (
    <span
      aria-hidden
      className={cn(
        'flex size-5 shrink-0 items-center justify-center rounded-md text-[10px] font-semibold',
        tintFor(name),
        className,
      )}
    >
      {initial}
    </span>
  );
}
