import { Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const DEFAULT_OPTIONS = ['Manajerial', 'Admin', 'Owner'];

export function PicSelect({
  id,
  value,
  onChange,
}: {
  id?: string;
  value: string;
  onChange: (value: string) => void;
}) {
  const [open, setOpen] = useState(false);

  function select(option: string) {
    onChange(option);
    setOpen(false);
  }

  return (
    <Popover modal open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          id={id}
          type="button"
          variant="outline"
          role="combobox"
          aria-expanded={open}
          aria-label="Pilih PIC"
          className={cn(
            'w-full justify-between font-normal',
            !value && 'text-muted-foreground',
          )}
        >
          <span className="truncate">{value || 'Pilih PIC'}</span>
          <ChevronsUpDown aria-hidden className="size-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>

      <PopoverContent className="w-(--radix-popover-trigger-width) min-w-56 p-1" align="start">
        <div className="p-1">
          <p className="px-2 py-1 text-xs font-medium text-muted-foreground">Pilihan</p>

          <div className="max-h-44 overflow-y-auto">
            {DEFAULT_OPTIONS.map((option) => (
              <button
                key={option}
                type="button"
                aria-pressed={value === option}
                onClick={() => select(option)}
                className={cn(
                  'flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent',
                  value === option && 'text-foreground',
                )}
              >
                <Check
                  aria-hidden
                  className={cn(
                    'size-4 shrink-0',
                    value !== option && 'opacity-0',
                  )}
                />
                <span className="flex-1 truncate">{option}</span>
              </button>
            ))}
          </div>
        </div>
      </PopoverContent>
    </Popover>
  );
}
