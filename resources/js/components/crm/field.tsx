import { CircleHelp } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

export function Field({
  label,
  htmlFor,
  error,
  hint,
  required,
  className,
  children,
}: {
  label: string;
  htmlFor?: string;
  error?: string;
  hint?: string;
  required?: boolean;
  className?: string;
  children: ReactNode;
}) {
  const [invalidMessage, setInvalidMessage] = useState('');

  return (
    <div
      className={cn('grid content-start gap-1.5', className)}
      onInvalidCapture={(event) => {
        event.preventDefault();

        const control = event.target as HTMLInputElement;

        setInvalidMessage(
          control.validity.valueMissing ? 'Wajib diisi.' : control.validationMessage,
        );
      }}
      onChangeCapture={() => setInvalidMessage('')}
    >
      <div className="flex items-center gap-1">
        <Label
          htmlFor={htmlFor}
          className="text-xs font-bold tracking-wide text-muted-foreground uppercase"
        >
          {label}
          {required && (
            <span aria-hidden className="text-destructive">
              *
            </span>
          )}
        </Label>

        {hint && (
          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                aria-label={`Keterangan ${label}`}
                className="rounded-full text-muted-foreground transition-colors outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
              >
                <CircleHelp className="size-3.5" />
              </button>
            </TooltipTrigger>
            <TooltipContent>{hint}</TooltipContent>
          </Tooltip>
        )}
      </div>

      <div className="grid [&>*]:w-full">{children}</div>

      <InputError message={error || invalidMessage} className="mt-0" />
    </div>
  );
}

export function FormGrid({ className, children }: { className?: string; children: ReactNode }) {
  return <div className={cn('grid gap-4 sm:grid-cols-2 sm:gap-5', className)}>{children}</div>;
}
