import { Check, ChevronsUpDown } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { rupiahOrCustom } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ServiceOption } from '@/types/crm';

export function ServicePackageCombobox({
  services,
  value,
  onChange,
  ariaLabel,
  placeholder = 'Pilih paket layanan',
}: {
  services: ServiceOption[];
  value: number | null;
  onChange: (packageId: number) => void;
  ariaLabel: string;
  placeholder?: string;
}) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');

  const selected = useMemo(
    () =>
      services
        .flatMap((service) =>
          service.packages.map((servicePackage) => ({ service, package: servicePackage })),
        )
        .find((pair) => pair.package.id === value) ?? null,
    [services, value],
  );

  const needle = query.trim().toLowerCase();

  const groups = useMemo(() => {
    if (needle === '') {
      return services.map((service) => ({ service, packages: service.packages }));
    }

    return services
      .map((service) => {
        const matchesService = `${service.type_label} ${service.name}`
          .toLowerCase()
          .includes(needle);

        return {
          service,
          packages: matchesService
            ? service.packages
            : service.packages.filter((servicePackage) =>
                servicePackage.name.toLowerCase().includes(needle),
              ),
        };
      })
      .filter((group) => group.packages.length > 0);
  }, [services, needle]);

  function pick(packageId: number) {
    onChange(packageId);
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
          aria-label={ariaLabel}
          className={cn('w-full justify-between font-normal', !selected && 'text-muted-foreground')}
        >
          {selected ? (
            <span className="flex min-w-0 items-center gap-1.5">
              <Badge variant="secondary" className="shrink-0 px-1.5 py-0 text-[10px]">
                {selected.service.type_label}
              </Badge>
              <span className="truncate">
                {selected.service.name} - {selected.package.name}
              </span>
            </span>
          ) : (
            <span className="truncate">{placeholder}</span>
          )}
          <ChevronsUpDown aria-hidden className="size-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>

      <PopoverContent className="w-(--radix-popover-trigger-width) min-w-72 p-0" align="start">
        <div className="border-b p-2">
          <Input
            autoFocus
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Cari layanan atau paket..."
            aria-label="Cari paket layanan"
            className="h-8"
          />
        </div>

        <div className="max-h-72 overflow-y-auto p-1">
          {groups.length === 0 && (
            <p className="px-2 py-3 text-center text-sm text-muted-foreground">
              Paket tidak ditemukan.
            </p>
          )}

          {groups.map(({ service, packages }) => (
            <div key={service.id} className="pb-1">
              <div className="flex items-center gap-1.5 px-2 py-1.5">
                <Badge variant="secondary" className="shrink-0 px-1.5 py-0 text-[10px]">
                  {service.type_label}
                </Badge>
                <span className="truncate text-xs font-medium text-muted-foreground">
                  {service.name}
                </span>
              </div>

              {packages.map((servicePackage) => (
                <button
                  key={servicePackage.id}
                  type="button"
                  onClick={() => pick(servicePackage.id)}
                  className="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                >
                  <Check
                    aria-hidden
                    className={cn('size-4 shrink-0', value !== servicePackage.id && 'opacity-0')}
                  />
                  <span className="flex-1 truncate">{servicePackage.name}</span>
                  <span className="shrink-0 text-xs text-muted-foreground">
                    {rupiahOrCustom(servicePackage.price)} / {servicePackage.unit}
                  </span>
                </button>
              ))}
            </div>
          ))}
        </div>
      </PopoverContent>
    </Popover>
  );
}
