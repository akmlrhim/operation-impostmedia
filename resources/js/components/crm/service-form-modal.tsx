import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { KeyboardEvent } from 'react';
import { Field, FormGrid } from '@/components/crm/field';
import { FormModal } from '@/components/crm/form-modal';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MoneyInput } from '@/components/ui/money-input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { rupiahOrCustom } from '@/lib/format';
import { store, update } from '@/routes/services';
import type { Option, ServiceItem, ServicePackage } from '@/types/crm';

type Props = {
  service?: ServiceItem;
  types: Option[];
  billingTypes: Option[];
  onClose: () => void;
};

const emptyPackage: ServicePackage = {
  name: '',
  description: null,
  price: '0',
  unit: 'paket',
  billing_type: 'one_time',
  is_active: true,
  requires_visit: false,
  points: [{ label: '' }],
};

export function ServiceFormModal({ service, types, billingTypes, onClose }: Props) {
  const isEdit = Boolean(service);

  const form = useForm({
    type: service?.type ?? 'umkm',
    name: service?.name ?? '',
    description: service?.description ?? '',
    is_active: service?.is_active ?? true,
    packages: service?.packages?.length
      ? service.packages.map((servicePackage) => ({
          ...servicePackage,
          points: servicePackage.points.length ? servicePackage.points : [{ label: '' }],
        }))
      : [{ ...emptyPackage }],
  });

  const title = isEdit ? `Ubah ${service?.name}` : 'Layanan baru';

  const errors = form.errors as Record<string, string | undefined>;

  function updatePackage(index: number, patch: Partial<ServicePackage>) {
    form.setData(
      'packages',
      form.data.packages.map((item, i) => (i === index ? { ...item, ...patch } : item)),
    );
  }

  function updatePoint(packageIndex: number, pointIndex: number, label: string) {
    updatePackage(packageIndex, {
      points: form.data.packages[packageIndex].points.map((point, i) =>
        i === pointIndex ? { ...point, label } : point,
      ),
    });
  }

  function pointId(packageIndex: number, pointIndex: number) {
    return `package-${packageIndex}-point-${pointIndex}`;
  }

  function addPoint(packageIndex: number, pointIndex: number) {
    const points = [...form.data.packages[packageIndex].points];
    points.splice(pointIndex + 1, 0, { label: '' });
    updatePackage(packageIndex, { points });
    requestAnimationFrame(() =>
      document.getElementById(pointId(packageIndex, pointIndex + 1))?.focus(),
    );
  }

  function handlePointKeyDown(
    packageIndex: number,
    pointIndex: number,
    event: KeyboardEvent<HTMLInputElement>,
  ) {
    const points = form.data.packages[packageIndex].points;

    if (event.key === 'Enter') {
      event.preventDefault();
      addPoint(packageIndex, pointIndex);

      return;
    }

    if (event.key === 'Backspace' && points[pointIndex].label === '' && points.length > 1) {
      event.preventDefault();
      const remaining = points.filter((_, i) => i !== pointIndex);
      updatePackage(packageIndex, { points: remaining });
      const focusIndex = Math.max(0, pointIndex - 1);
      requestAnimationFrame(() =>
        document.getElementById(pointId(packageIndex, focusIndex))?.focus(),
      );
    }
  }

  return (
    <FormModal
      title={title}
      size="lg"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();
        form.transform((data) => ({
          ...data,
          packages: data.packages.map((servicePackage) => ({
            ...servicePackage,
            points: servicePackage.points.filter((point) => point.label.trim() !== ''),
          })),
        }));
        form.submit(isEdit && service ? update(service.id) : store(), {
          preserveScroll: true,
          onSuccess: onClose,
        });
      }}
      footer={
        <>
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" disabled={form.processing}>
            {isEdit ? 'Simpan perubahan' : 'Simpan layanan'}
          </Button>
        </>
      }
    >
      <FormGrid>
        <Field label="Tipe" error={form.errors.type}>
          <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {types.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </Field>

        <Field
          label="Nama layanan"
          htmlFor="name"
          required
          hint="Boleh sama dengan layanan tipe lain, tapi tidak boleh sama di dalam satu tipe."
          error={form.errors.name}
        >
          <Input
            id="name"
            value={form.data.name}
            onChange={(e) => form.setData('name', e.target.value)}
            required
            placeholder="Social Media Management"
          />
        </Field>

        <Field
          label="Deskripsi"
          htmlFor="description"
          className="sm:col-span-2"
          error={form.errors.description}
        >
          <Textarea
            id="description"
            rows={2}
            value={form.data.description}
            onChange={(e) => form.setData('description', e.target.value)}
            placeholder="Masukkan deskripsi"
          />
        </Field>

        <div className="flex items-center gap-2 sm:col-span-2">
          <Checkbox
            id="is_active"
            checked={form.data.is_active}
            onCheckedChange={(checked) => form.setData('is_active', checked === true)}
          />
          <Label htmlFor="is_active" className="font-normal">
            Aktif dan bisa dipilih di dokumen
          </Label>
        </div>
      </FormGrid>

      <div className="space-y-3 border-t pt-5">
        <div className="flex items-baseline justify-between gap-3">
          <div>
            <h3 className="text-sm font-medium">Paket</h3>
            <p className="text-xs text-muted-foreground">
              Harga menempel di paket, bukan di layanan. Poinnya ikut terisi jadi rincian saat paket
              dipilih di MoU atau invoice. Paket yang ditandai butuh kunjungan lokasi akan
              memunculkan pasal Ketentuan Visit di MoU yang memesannya.
            </p>
          </div>
        </div>

        {form.data.packages.map((servicePackage, index) => (
          <div key={index} className="space-y-4 rounded-lg border p-4 sm:p-5">
            <div className="flex items-center justify-between gap-3">
              <span className="text-xs font-medium text-muted-foreground">Paket {index + 1}</span>

              <div className="flex items-center gap-1">
                <span className="text-sm font-medium">{rupiahOrCustom(servicePackage.price)}</span>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  disabled={form.data.packages.length === 1}
                  onClick={() =>
                    form.setData(
                      'packages',
                      form.data.packages.filter((_, i) => i !== index),
                    )
                  }
                  aria-label={`Hapus paket ${index + 1}`}
                >
                  <Trash2 className="size-4" />
                </Button>
              </div>
            </div>

            <FormGrid className="lg:grid-cols-6">
              <Field
                label="Nama paket"
                htmlFor={`package-${index}-name`}
                required
                className="lg:col-span-2"
                error={errors[`packages.${index}.name`]}
              >
                <Input
                  id={`package-${index}-name`}
                  aria-label={`Nama paket ${index + 1}`}
                  value={servicePackage.name}
                  onChange={(e) => updatePackage(index, { name: e.target.value })}
                  required
                  placeholder="Silver"
                />
              </Field>

              <Field
                label="Harga"
                htmlFor={`package-${index}-price`}
                required={servicePackage.price !== null}
                className="lg:col-span-2"
                error={errors[`packages.${index}.price`]}
              >
                {servicePackage.price === null ? (
                  <p className="text-sm font-medium text-muted-foreground">Custom</p>
                ) : (
                  <MoneyInput
                    id={`package-${index}-price`}
                    aria-label={`Harga paket ${index + 1}`}
                    value={servicePackage.price}
                    onChange={(value) => updatePackage(index, { price: value })}
                    required
                    placeholder="0"
                  />
                )}

                <div className="flex items-center gap-2 pt-2">
                  <Checkbox
                    id={`package-${index}-custom-price`}
                    checked={servicePackage.price === null}
                    onCheckedChange={(checked) =>
                      updatePackage(index, { price: checked ? null : '0' })
                    }
                  />
                  <Label htmlFor={`package-${index}-custom-price`} className="font-normal">
                    Harga custom
                  </Label>
                </div>
              </Field>

              <Field
                label="Satuan"
                htmlFor={`package-${index}-unit`}
                required
                className="lg:col-span-2"
                error={errors[`packages.${index}.unit`]}
              >
                <Input
                  id={`package-${index}-unit`}
                  aria-label={`Satuan paket ${index + 1}`}
                  value={servicePackage.unit}
                  onChange={(e) => updatePackage(index, { unit: e.target.value })}
                  required
                  placeholder="bulan"
                />
              </Field>

              <Field
                label="Model penagihan"
                className="lg:col-span-3"
                error={errors[`packages.${index}.billing_type`]}
              >
                <Select
                  value={servicePackage.billing_type}
                  onValueChange={(value) => updatePackage(index, { billing_type: value })}
                >
                  <SelectTrigger aria-label={`Model penagihan paket ${index + 1}`}>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {billingTypes.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </Field>

              <div className="flex items-center gap-2 lg:col-span-3 lg:pt-6">
                <Checkbox
                  id={`package-${index}-active`}
                  checked={servicePackage.is_active}
                  onCheckedChange={(checked) =>
                    updatePackage(index, { is_active: checked === true })
                  }
                />
                <Label htmlFor={`package-${index}-active`} className="font-normal">
                  Paket bisa dipilih di dokumen
                </Label>
              </div>

              <div className="flex items-center gap-2 lg:col-span-3 lg:pt-6">
                <Checkbox
                  id={`package-${index}-visit`}
                  checked={servicePackage.requires_visit}
                  onCheckedChange={(checked) =>
                    updatePackage(index, { requires_visit: checked === true })
                  }
                />
                <Label htmlFor={`package-${index}-visit`} className="font-normal">
                  Butuh kunjungan lokasi (visit)
                </Label>
              </div>

              <div className="space-y-2 sm:col-span-2 lg:col-span-6">
                <div className="flex items-baseline justify-between gap-3">
                  <Label className="text-sm">Poin yang didapat</Label>
                  <span className="text-xs text-muted-foreground">
                    Enter untuk menambah poin, Backspace pada poin kosong untuk menghapus
                  </span>
                </div>

                {servicePackage.points.map((point, pointIndex) => (
                  <div key={pointIndex} className="flex items-start gap-2">
                    <div className="flex-1">
                      <Input
                        id={pointId(index, pointIndex)}
                        aria-label={`Poin ${pointIndex + 1} paket ${index + 1}`}
                        value={point.label}
                        onChange={(e) => updatePoint(index, pointIndex, e.target.value)}
                        onKeyDown={(e) => handlePointKeyDown(index, pointIndex, e)}
                        placeholder="8 Konten Feed Design"
                      />
                      <InputError
                        message={errors[`packages.${index}.points.${pointIndex}.label`]}
                      />
                    </div>
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      disabled={servicePackage.points.length === 1}
                      onClick={() =>
                        updatePackage(index, {
                          points: servicePackage.points.filter((_, i) => i !== pointIndex),
                        })
                      }
                      aria-label={`Hapus poin ${pointIndex + 1} paket ${index + 1}`}
                    >
                      <Trash2 className="size-4" />
                    </Button>
                  </div>
                ))}

                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    updatePackage(index, { points: [...servicePackage.points, { label: '' }] })
                  }
                >
                  <Plus className="size-4" />
                  Tambah poin
                </Button>
              </div>
            </FormGrid>
          </div>
        ))}

        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => form.setData('packages', [...form.data.packages, { ...emptyPackage }])}
        >
          <Plus className="size-4" />
          Tambah paket
        </Button>

        <InputError message={form.errors.packages} />
      </div>
    </FormModal>
  );
}
