import { Check, ChevronsUpDown, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Field } from '@/components/crm/field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { UserOption } from '@/types/crm';

export function AssigneeField({
  id = 'assigned_to_ids',
  value,
  users,
  error,
  className,
  onChange,
}: {
  id?: string;
  value: number[];
  users: UserOption[];
  error?: string;
  className?: string;
  onChange: (value: number[]) => void;
}) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');

  const selected = useMemo(
    () =>
      value
        .map((id) => users.find((user) => user.value === id))
        .filter((user) => user !== undefined),
    [users, value],
  );

  const needle = query.trim().toLowerCase();

  const filtered = useMemo(() => {
    if (needle === '') {
      return users;
    }

    return users.filter((user) => user.label.toLowerCase().includes(needle));
  }, [users, needle]);

  function toggle(userId: number) {
    onChange(
      value.includes(userId) ? value.filter((id) => id !== userId) : [...value, userId],
    );
  }

  return (
    <Field
      label="Penanggung jawab"
      htmlFor={id}
      hint="Orang-orang ini ikut dikabari setiap ada perubahan pada data ini."
      error={error}
      className={className}
    >
      <div className="grid gap-2">
        <Popover
          modal
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
              id={id}
              type="button"
              variant="outline"
              role="combobox"
              aria-expanded={open}
              aria-label="Pilih penanggung jawab"
              className={cn(
                'w-full justify-between font-normal',
                selected.length === 0 && 'text-muted-foreground',
              )}
            >
              <span className="truncate">
                {selected.length === 0
                  ? 'Belum ditentukan'
                  : `${selected.length} orang penanggung jawab`}
              </span>
              <ChevronsUpDown aria-hidden className="size-4 shrink-0 opacity-50" />
            </Button>
          </PopoverTrigger>

          <PopoverContent className="w-(--radix-popover-trigger-width) min-w-64 p-0" align="start">
            <div className="border-b p-2">
              <Input
                autoFocus
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Cari nama..."
                aria-label="Cari penanggung jawab"
                className="h-8"
              />
            </div>

            <div className="max-h-60 overflow-y-auto p-1">
              {filtered.length === 0 && (
                <p className="px-2 py-3 text-center text-sm text-muted-foreground">
                  Anggota tidak ditemukan.
                </p>
              )}

              {filtered.map((user) => (
                <button
                  key={user.value}
                  type="button"
                  aria-pressed={value.includes(user.value)}
                  onClick={() => toggle(user.value)}
                  className="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                >
                  <Check
                    aria-hidden
                    className={cn(
                      'size-4 shrink-0',
                      !value.includes(user.value) && 'opacity-0',
                    )}
                  />
                  <span className="flex-1 truncate">{user.label}</span>
                </button>
              ))}
            </div>
          </PopoverContent>
        </Popover>

        {selected.length > 0 && (
          <ul className="flex flex-wrap gap-1.5">
            {selected.map((user) => (
              <li key={user.value}>
                <span className="inline-flex max-w-full items-center gap-1 rounded-md border py-0.5 pr-1 pl-2 text-xs">
                  <span className="truncate">{user.label}</span>
                  <button
                    type="button"
                    aria-label={`Hapus ${user.label}`}
                    onClick={() => toggle(user.value)}
                    className="cursor-pointer rounded-sm p-0.5 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                  >
                    <X aria-hidden className="size-3" />
                  </button>
                </span>
              </li>
            ))}
          </ul>
        )}
      </div>
    </Field>
  );
}
