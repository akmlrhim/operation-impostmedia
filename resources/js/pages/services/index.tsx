import { Head, router } from '@inertiajs/react';
import { Check, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { RowActions } from '@/components/crm/row-actions';
import { ServiceFormModal } from '@/components/crm/service-form-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useRealtime } from '@/hooks/use-realtime';
import { rupiah } from '@/lib/format';
import { destroy, index } from '@/routes/services';
import type { Option, ServiceItem } from '@/types/crm';

type Props = {
  services: ServiceItem[];
  types: Option[];
  billingTypes: Option[];
};

export default function ServicesIndex({ services, types, billingTypes }: Props) {
  useRealtime(['services'], ['services']);

  const [confirm, confirmDialog] = useConfirm();
  const [serviceModal, setServiceModal] = useState<{ service?: ServiceItem } | null>(null);

  const groups = useMemo(
    () =>
      types.map((type) => ({
        ...type,
        services: services
          .filter((service) => service.type === type.value)
          .sort((a, b) => a.name.localeCompare(b.name)),
      })),
    [services, types],
  );

  async function askDelete(service: ServiceItem) {
    const confirmed = await confirm({
      title: `Hapus layanan ${service.name}?`,
      description:
        'Semua paket dan poinnya ikut terhapus. MoU dan invoice yang sudah memakainya tidak berubah.',
      confirmLabel: 'Hapus layanan',
      destructive: true,
    });

    if (confirmed) {
      router.delete(destroy(service.id), { preserveScroll: true });
    }
  }

  return (
    <>
      <Head title="Layanan" />

      <PageHeader
        title="Layanan"
        actions={
          <Button onClick={() => setServiceModal({})}>
            <Plus className="size-4" />
            Layanan baru
          </Button>
        }
      />

      <PageBody className="gap-5">
        {groups.map((group) => (
          <section key={group.value} className="space-y-3">
            <div className="flex items-baseline gap-2">
              <h2 className="text-[0.6875rem] font-semibold tracking-[0.08em] text-muted-foreground uppercase">
                {group.label}
              </h2>
              <span className="num text-xs text-muted-foreground">
                {group.services.length} layanan
              </span>
            </div>

            {group.services.length === 0 && (
              <Card className="px-4 py-6 text-center text-sm text-muted-foreground">
                Belum ada layanan {group.label}.
              </Card>
            )}

            {group.services.map((service) => (
              <Card
                key={service.id}
                className={`gap-0 overflow-hidden p-0 ${service.is_active ? '' : 'opacity-60'}`}
              >
                <div className="flex items-start justify-between gap-3 border-b bg-panel-header px-3 py-2">
                  <div className="min-w-0">
                    <button
                      type="button"
                      onClick={() => setServiceModal({ service })}
                      className="cursor-pointer text-left font-medium hover:underline"
                    >
                      {service.name}
                    </button>
                    {!service.is_active && (
                      <Badge variant="outline" className="ml-2">
                        Nonaktif
                      </Badge>
                    )}
                    {service.description && (
                      <p className="mt-1 text-sm text-muted-foreground">{service.description}</p>
                    )}
                  </div>

                  <RowActions
                    label={service.name}
                    actions={[
                      {
                        label: 'Ubah layanan',
                        icon: Pencil,
                        onSelect: () => setServiceModal({ service }),
                      },
                      {
                        label: 'Hapus layanan',
                        icon: Trash2,
                        destructive: true,
                        onSelect: () => askDelete(service),
                      },
                    ]}
                  />
                </div>

                <div className="flex flex-wrap gap-px bg-border">
                  {service.packages.length === 0 && (
                    <p className="flex-1 bg-card p-3 text-sm text-muted-foreground">
                      Belum ada paket.
                    </p>
                  )}

                  {service.packages.map((servicePackage) => (
                    <div
                      key={servicePackage.id}
                      className={`min-w-64 flex-1 space-y-1.5 bg-card p-3 ${servicePackage.is_active ? '' : 'opacity-60'}`}
                    >
                      <div className="flex items-baseline justify-between gap-2">
                        <span className="font-medium">{servicePackage.name}</span>
                        {!servicePackage.is_active && (
                          <Badge variant="outline" className="shrink-0">
                            Nonaktif
                          </Badge>
                        )}
                      </div>

                      <div className="num text-sm font-medium">
                        {rupiah(servicePackage.price)}
                        <span className="text-muted-foreground"> / {servicePackage.unit}</span>
                      </div>

                      <div className="text-xs text-muted-foreground">
                        {billingTypes.find((b) => b.value === servicePackage.billing_type)?.label ??
                          servicePackage.billing_type}
                      </div>

                      {servicePackage.points.length > 0 && (
                        <ul className="space-y-1 pt-1">
                          {servicePackage.points.map((point) => (
                            <li key={point.id} className="flex gap-2 text-sm">
                              <Check className="mt-0.5 size-3.5 shrink-0 text-muted-foreground" />
                              <span>{point.label}</span>
                            </li>
                          ))}
                        </ul>
                      )}
                    </div>
                  ))}
                </div>
              </Card>
            ))}
          </section>
        ))}
      </PageBody>

      {serviceModal && (
        <ServiceFormModal
          service={serviceModal.service}
          types={types}
          billingTypes={billingTypes}
          onClose={() => setServiceModal(null)}
        />
      )}

      {confirmDialog}
    </>
  );
}

ServicesIndex.layout = {
  breadcrumbs: [{ title: 'Layanan', href: index() }],
};
