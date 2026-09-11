import type { FormEvent, ReactNode } from 'react';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { bottomSheetHandle, bottomSheetOnMobile } from '@/lib/bottom-sheet';
import { cn } from '@/lib/utils';

const SIZES = {
  sm: 'sm:max-w-md',
  md: 'sm:max-w-2xl',
  lg: 'sm:max-w-4xl',
  xl: 'sm:max-w-6xl',
} as const;

export function FormModal({
  title,
  size = 'md',
  onOpenChange,
  onSubmit,
  footer,
  children,
}: {
  title: string;
  size?: keyof typeof SIZES;
  onOpenChange: (open: boolean) => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  footer: ReactNode;
  children: ReactNode;
}) {
  return (
    <Dialog open onOpenChange={onOpenChange}>
      <DialogContent
        className={cn('flex flex-col gap-0 overflow-hidden p-0', bottomSheetOnMobile, SIZES[size])}
      >
        <form onSubmit={onSubmit} className="flex min-h-0 flex-1 flex-col">
          <DialogHeader className="shrink-0 border-b bg-panel-header px-4 py-2.5">
            <div aria-hidden className={bottomSheetHandle} />
            <DialogTitle>{title}</DialogTitle>
          </DialogHeader>

          <div className="min-h-0 flex-1 space-y-5 overflow-y-auto px-4 py-4">{children}</div>

          <DialogFooter className="shrink-0 border-t bg-panel-header px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-3 [&>button]:w-full sm:[&>button]:w-auto">
            {footer}
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
