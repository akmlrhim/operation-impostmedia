import { Check, ChevronsUpDown } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type ClientOption = { id: number; company_name: string };

const MAX_VISIBLE = 50;

export function ClientCombobox({
  clients,
  value,
  onChange,
  placeholder = 'Semua klien',
  className,
}: {
  clients: ClientOption[];
  value: number | null;
  onChange: (value: number | null) => void;
  placeholder?: string;
  className?: string;
}) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');

  const selected = clients.find((client) => client.id === value);
  const needle = query.trim().toLowerCase();

  const filtered = useMemo(
    () =>
      needle === ''
        ? clients
        : clients.filter((client) => client.company_name.toLowerCase().includes(needle)),
    [clients, needle],
  );

  const visible = filtered.slice(0, MAX_VISIBLE);
  const hidden = filtered.length - visible.length;

  function pick(id: number | null) {
    onChange(id);
    setOpen(false);
    setQuery('');
  }

  return (
    <Popover
      open={open}
      onOpenChange={(next) => {
        setOpen(next);

        if (!next) {
          setQuery('');
        }
      }}
    >
      <PopoverTrigger asChild>
        <Button
          type="button"
          variant="outline"
          role="combobox"
          aria-expanded={open}
          data-placeholder={selected ? undefined : ''}
          className={cn(
            'w-full justify-between font-normal sm:w-56',
            !selected && 'text-muted-foreground',
            className,
          )}
        >
          <span className="truncate">{selected?.company_name ?? placeholder}</span>
          <ChevronsUpDown aria-hidden className="size-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>

      <PopoverContent className="w-64 p-0" align="start">
        <div className="border-b p-2">
          <Input
            autoFocus
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Cari klien..."
            aria-label="Cari klien"
            className="h-8"
          />
        </div>

        <div className="max-h-64 overflow-y-auto p-1">
          <button
            type="button"
            onClick={() => pick(null)}
            className="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
          >
            <Check aria-hidden className={cn('size-4 shrink-0', value !== null && 'opacity-0')} />
            {placeholder}
          </button>

          {filtered.length === 0 && (
            <p className="px-2 py-3 text-center text-sm text-muted-foreground">
              Klien tidak ditemukan.
            </p>
          )}

          {visible.map((client) => (
            <button
              key={client.id}
              type="button"
              onClick={() => pick(client.id)}
              className="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
            >
              <Check
                aria-hidden
                className={cn('size-4 shrink-0', value !== client.id && 'opacity-0')}
              />
              <span className="truncate">{client.company_name}</span>
            </button>
          ))}

          {hidden > 0 && (
            <p className="px-2 py-2 text-center text-xs text-muted-foreground">
              {hidden} klien lain tidak ditampilkan. Ketik untuk menyaring.
            </p>
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}
