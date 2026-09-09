import { useForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import { FormModal } from '@/components/crm/form-modal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { store, update } from '@/routes/lead-stages';
import type { Option } from '@/types/crm';

type Stage = { id: number; name: string; color: string; type: string };

type Props = {
  stage?: Stage;
  stageTypes: Option[];
  onClose: () => void;
};

const PRESET_COLORS = ['#94a3b8', '#38bdf8', '#6366f1', '#a855f7', '#f59e0b', '#22c55e', '#ef4444'];

export function LeadStageFormModal({ stage, stageTypes, onClose }: Props) {
  const isEdit = Boolean(stage);

  const form = useForm({
    name: stage?.name ?? '',
    color: stage?.color ?? '#38bdf8',
    type: stage?.type ?? 'open',
  });

  const title = isEdit ? `Ubah kolom ${stage?.name}` : 'Kolom baru';

  return (
    <FormModal
      title={title}
      size="sm"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(isEdit && stage ? update(stage.id) : store(), {
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
            {isEdit ? 'Simpan perubahan' : 'Tambah kolom'}
          </Button>
        </>
      }
    >
      <FormGrid>
        <Field
          label="Nama kolom"
          htmlFor="name"
          required
          className="sm:col-span-2"
          error={form.errors.name}
        >
          <Input
            id="name"
            value={form.data.name}
            onChange={(e) => form.setData('name', e.target.value)}
            placeholder="Masukkan nama kolom"
            required
          />
        </Field>

        <Field label="Jenis kolom" error={form.errors.type}>
          <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {stageTypes.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </Field>

        <Field
          label="Warna"
          htmlFor="color"
          required
          hint="Warna penanda kolom di papan kanban. Pakai kode heksadesimal, mis. #38bdf8."
          className="sm:col-span-2"
          error={form.errors.color}
        >
          <div className="flex items-center gap-2">
            <Input
              id="color"
              value={form.data.color}
              onChange={(e) => form.setData('color', e.target.value)}
              placeholder="Masukkan kode warna"
              required
            />
            <div className="flex shrink-0 items-center gap-1">
              {PRESET_COLORS.map((color) => (
                <button
                  key={color}
                  type="button"
                  aria-label={`Pakai warna ${color}`}
                  onClick={() => form.setData('color', color)}
                  style={{ backgroundColor: color }}
                  className="size-6 cursor-pointer rounded-md border transition-transform hover:scale-110"
                />
              ))}
            </div>
          </div>
        </Field>
      </FormGrid>
    </FormModal>
  );
}
