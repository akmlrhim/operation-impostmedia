import { Check, ChevronsUpDown, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { PackagePrice } from '@/components/crm/package-price';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { ServiceOption } from '@/types/crm';

export function ServicePackageMultiCombobox({
  services,
  value,
  onChange,
  ariaLabel,
  placeholder = 'Pilih paket layanan',
}: {
  services: ServiceOption[];
  value: number[];
  onChange: (packageIds: number[]) => void;
  ariaLabel: string;
  placeholder?: string;
}) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');

  const pairs = useMemo(
    () =>
      services.flatMap((service) =>
        service.packages.map((servicePackage) => ({ service, package: servicePackage })),
      ),
    [services],
  );

  const selected = useMemo(
    () =>
      value
        .map((id) => pairs.find((pair) => pair.package.id === id))
        .filter((pair) => pair !== undefined),
    [pairs, value],
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

  function toggle(packageId: number) {
    onChange(
      value.includes(packageId) ? value.filter((id) => id !== packageId) : [...value, packageId],
    );
  }

  return (
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
            type="button"
            variant="outline"
            role="combobox"
            aria-expanded={open}
            aria-label={ariaLabel}
            className={cn(
              'w-full justify-between font-normal',
              selected.length === 0 && 'text-muted-foreground',
            )}
          >
            <span className="truncate">
              {selected.length === 0 ? placeholder : `${selected.length} paket dipilih`}
            </span>
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
                    aria-pressed={value.includes(servicePackage.id)}
                    onClick={() => toggle(servicePackage.id)}
                    className="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                  >
                    <Check
                      aria-hidden
                      className={cn(
                        'size-4 shrink-0',
                        !value.includes(servicePackage.id) && 'opacity-0',
                      )}
                    />
                    <span className="flex-1 truncate">{servicePackage.name}</span>
                    <span className="shrink-0 text-xs text-muted-foreground">
                      <PackagePrice
                        price={servicePackage.price}
                        quantity={servicePackage.quantity}
                        unit={servicePackage.unit}
                      />
                    </span>
                  </button>
                ))}
              </div>
            ))}
          </div>
        </PopoverContent>
      </Popover>

      {selected.length > 0 && (
        <ul className="flex flex-wrap gap-1.5">
          {selected.map((pair) => (
            <li key={pair.package.id}>
              <span className="inline-flex max-w-full items-center gap-1 rounded-md border py-0.5 pr-1 pl-2 text-xs">
                <span className="truncate">
                  {pair.service.name} - {pair.package.name}
                </span>
                <button
                  type="button"
                  aria-label={`Hapus ${pair.package.name}`}
                  onClick={() => toggle(pair.package.id)}
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
  );
}
