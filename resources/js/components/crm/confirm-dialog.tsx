import { useCallback, useState } from 'react';
import type { ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

export type ConfirmOptions = {
  title: string;
  description?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  destructive?: boolean;
};

export type ConfirmFn = (options: ConfirmOptions) => Promise<boolean>;

type Pending = { options: ConfirmOptions; resolve: (confirmed: boolean) => void };

export function useConfirm(): [ConfirmFn, ReactNode] {
  const [pending, setPending] = useState<Pending | null>(null);

  const confirm = useCallback(
    (options: ConfirmOptions) =>
      new Promise<boolean>((resolve) => {
        setPending({ options, resolve });
      }),
    [],
  );

  function settle(confirmed: boolean) {
    pending?.resolve(confirmed);
    setPending(null);
  }

  const dialog = (
    <Dialog
      open={pending !== null}
      onOpenChange={(open) => {
        if (!open) {
          settle(false);
        }
      }}
    >
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{pending?.options.title}</DialogTitle>
          {pending?.options.description && (
            <DialogDescription>{pending.options.description}</DialogDescription>
          )}
        </DialogHeader>

        <DialogFooter className="[&>button]:w-full sm:[&>button]:w-auto">
          <Button type="button" variant="outline" onClick={() => settle(false)}>
            {pending?.options.cancelLabel ?? 'Batal'}
          </Button>
          <Button
            type="button"
            variant={pending?.options.destructive ? 'destructive' : 'default'}
            onClick={() => settle(true)}
          >
            {pending?.options.confirmLabel ?? 'Lanjutkan'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );

  return [confirm, dialog];
}
