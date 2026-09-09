import { Loader2, Plus, Sparkles, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Field, FormGrid } from '@/components/crm/field';
import { ServicePackageCombobox } from '@/components/crm/service-package-combobox';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { MoneyInput } from '@/components/ui/money-input';
import { Textarea } from '@/components/ui/textarea';
import { rupiah } from '@/lib/format';
import type { LineItem, ServiceOption } from '@/types/crm';

export const emptyLineItem: LineItem = {
  service_package_id: null,
  name: '',
  description: null,
  quantity: 1,
  unit: 'paket',
  unit_price: 0,
};

export function lineAmount(item: LineItem): number {
  return Number(item.quantity || 0) * Number(item.unit_price || 0);
}

export function LineItemsEditor({
  items,
  services,
  onChange,
  errors,
  writePoints,
}: {
  items: LineItem[];
  services: ServiceOption[];
  onChange: (items: LineItem[]) => void;
  errors: Record<string, string>;
  writePoints?: (item: LineItem) => Promise<string[]>;
}) {
  const [writingIndex, setWritingIndex] = useState<number | null>(null);

  function update(index: number, patch: Partial<LineItem>) {
    onChange(items.map((item, i) => (i === index ? { ...item, ...patch } : item)));
  }

  async function writeDescription(index: number) {
    const item = items[index];

    if (!writePoints || writingIndex !== null) {
      return;
    }

    if (item.name.trim() === '') {
      toast.error('Isi dulu uraian pekerjaannya, itu yang jadi bahan tulisan AI.');

      return;
    }

    setWritingIndex(index);

    try {
      const points = await writePoints(item);

      if (points.length === 0) {
        toast.error('AI tidak mengembalikan rincian apa pun. Coba lagi sebentar.');

        return;
      }

      update(index, { description: points.join('\n') });
      toast.success(`${points.length} poin rincian ditulis. Periksa dulu sebelum disimpan.`);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Rincian gagal ditulis.');
    } finally {
      setWritingIndex(null);
    }
  }

  function pickPackage(index: number, packageId: number) {
    const service = services.find((s) =>
      s.packages.some((servicePackage) => servicePackage.id === packageId),
    );
    const picked = service?.packages.find((servicePackage) => servicePackage.id === packageId);

    if (!service || !picked) {
      return;
    }

    update(index, {
      service_package_id: picked.id,
      name: `${service.name} - ${picked.name}`,
      unit: picked.unit,
      unit_price: picked.price,
      description: picked.points.map((point) => point.label).join('\n') || null,
    });
  }

  return (
    <div className="space-y-4">
      <div className="space-y-3">
        {items.map((item, index) => (
          <div key={index} className="space-y-4 rounded-lg border p-4 sm:p-5">
            <div className="flex items-center justify-between gap-3">
              <span className="text-xs font-medium text-muted-foreground">Baris {index + 1}</span>

              <div className="flex items-center gap-1">
                <span className="text-sm font-medium">{rupiah(lineAmount(item))}</span>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  disabled={items.length === 1}
                  onClick={() => onChange(items.filter((_, i) => i !== index))}
                  aria-label={`Hapus baris ${index + 1}`}
                >
                  <Trash2 className="size-4" />
                </Button>
              </div>
            </div>

            <FormGrid className="lg:grid-cols-6">
              <Field label="Paket layanan" className="lg:col-span-2">
                <ServicePackageCombobox
                  services={services}
                  value={item.service_package_id}
                  onChange={(packageId) => pickPackage(index, packageId)}
                  ariaLabel={`Paket layanan baris ${index + 1}`}
                />
              </Field>

              <Field
                label="Uraian"
                htmlFor={`item-${index}-name`}
                required
                className="lg:col-span-4"
                error={errors[`items.${index}.name`]}
              >
                <Input
                  id={`item-${index}-name`}
                  aria-label={`Uraian baris ${index + 1}`}
                  value={item.name}
                  onChange={(e) => update(index, { name: e.target.value })}
                  required
                  placeholder="Social Media Management"
                />
              </Field>

              <Field
                label="Volume"
                htmlFor={`item-${index}-quantity`}
                required
                className="lg:col-span-2"
                error={errors[`items.${index}.quantity`]}
              >
                <Input
                  id={`item-${index}-quantity`}
                  aria-label={`Volume baris ${index + 1}`}
                  type="number"
                  min="0"
                  step="0.01"
                  value={item.quantity}
                  onChange={(e) => update(index, { quantity: e.target.value })}
                  required
                  placeholder="1"
                />
              </Field>

              <Field
                label="Satuan"
                htmlFor={`item-${index}-unit`}
                required
                className="lg:col-span-2"
                error={errors[`items.${index}.unit`]}
              >
                <Input
                  id={`item-${index}-unit`}
                  aria-label={`Satuan baris ${index + 1}`}
                  value={item.unit}
                  onChange={(e) => update(index, { unit: e.target.value })}
                  required
                  placeholder="bulan"
                />
              </Field>

              <Field
                label="Harga satuan"
                htmlFor={`item-${index}-unit-price`}
                required
                className="lg:col-span-2"
                error={errors[`items.${index}.unit_price`]}
              >
                <MoneyInput
                  id={`item-${index}-unit-price`}
                  aria-label={`Harga satuan baris ${index + 1}`}
                  value={item.unit_price}
                  onChange={(value) => update(index, { unit_price: value })}
                  required
                  placeholder="0"
                />
              </Field>

              <Field
                label="Rincian"
                htmlFor={`item-${index}-description`}
                className="sm:col-span-2 lg:col-span-6"
                hint="Satu poin per baris. Ikut tercetak sebagai daftar di bawah uraian."
                error={errors[`items.${index}.description`]}
              >
                <div className="space-y-2">
                  <Textarea
                    id={`item-${index}-description`}
                    aria-label={`Rincian baris ${index + 1}`}
                    rows={3}
                    value={item.description ?? ''}
                    onChange={(e) => update(index, { description: e.target.value || null })}
                    placeholder={'8 Konten Feed Design\n8 Video Reels\nReport & Analisis'}
                  />

                  {writePoints && (
                    <div className="flex justify-end">
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={writingIndex !== null}
                        onClick={() => writeDescription(index)}
                      >
                        {writingIndex === index ? (
                          <>
                            <Loader2 className="size-4 animate-spin" />
                            Menulis rincian…
                          </>
                        ) : (
                          <>
                            <Sparkles className="size-4" />
                            Tulis dengan AI
                          </>
                        )}
                      </Button>
                    </div>
                  )}
                </div>
              </Field>
            </FormGrid>
          </div>
        ))}
      </div>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => onChange([...items, { ...emptyLineItem }])}
        >
          <Plus className="size-4" />
          Tambah baris
        </Button>

        <div className="flex items-baseline gap-2">
          <span className="text-sm text-muted-foreground">Subtotal</span>
          <span className="text-lg font-semibold">
            {rupiah(items.reduce((sum, item) => sum + lineAmount(item), 0))}
          </span>
        </div>
      </div>

      <InputError message={errors.items} />
    </div>
  );
}
