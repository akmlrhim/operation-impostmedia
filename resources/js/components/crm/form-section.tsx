import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function FormSection({
  title,
  children,
  className,
}: {
  title: string;
  children: ReactNode;
  className?: string;
}) {
  return (
    <div className={cn('space-y-5', className)}>
      <h3 className="text-sm font-medium">{title}</h3>
      {children}
    </div>
  );
}
