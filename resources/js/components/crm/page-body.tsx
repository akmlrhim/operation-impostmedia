import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function PageBody({ children, className }: { children: ReactNode; className?: string }) {
  return <div className={cn('flex flex-1 flex-col gap-3 p-3 sm:p-4', className)}>{children}</div>;
}
