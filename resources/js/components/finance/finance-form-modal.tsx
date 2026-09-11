import { useForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import { FormModal } from '@/components/crm/form-modal';
import { Button } from '@/components/ui/button';
import { DateField } from '@/components/ui/date-field';
import { Input } from '@/components/ui/input';
import { MoneyInput } from '@/components/ui/money-input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store, update } from '@/routes/finance';
import type { Option } from '@/types/crm';
import type { FinanceTransaction } from '@/types/finance';

type Props = {
  transaction?: FinanceTransaction;
  types: Option[];
  defaultType?: 'income' | 'expense';
  onClose: () => void;
};

export function FinanceFormModal({ transaction, types, defaultType, onClose }: Props) {
  const isEdit = Boolean(transaction);

  const form = useForm({
    type: transaction?.type ?? defaultType ?? 'income',
    category: transaction?.category ?? '',
    amount: transaction?.amount ?? '',
    transaction_date: transaction?.transaction_date ?? new Date().toISOString().slice(0, 10),
    notes: transaction?.notes ?? '',
  });

  return (
    <FormModal
      title={isEdit ? 'Ubah transaksi' : 'Transaksi baru'}
      size="sm"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(isEdit && transaction ? update(transaction.id) : store(), {
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
            {isEdit ? 'Simpan perubahan' : 'Simpan transaksi'}
          </Button>
        </>
      }
    >
      <FormGrid>
        <Field label="Jenis" htmlFor="type" required error={form.errors.type}>
          <Select
            value={form.data.type}
            onValueChange={(value) => form.setData('type', value as 'income' | 'expense')}
          >
            <SelectTrigger id="type">
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
          label="Tanggal"
          htmlFor="transaction_date"
          required
          error={form.errors.transaction_date}
        >
          <DateField
            id="transaction_date"
            value={form.data.transaction_date}
            required
            clearable={false}
            onChange={(value) => form.setData('transaction_date', value)}
          />
        </Field>

        <Field
          label="Kategori"
          htmlFor="category"
          required
          error={form.errors.category}
          className="sm:col-span-2"
        >
          <Input
            id="category"
            type="text"
            value={form.data.category}
            onChange={(e) => form.setData('category', e.target.value)}
            required
            maxLength={100}
            placeholder="Misal: Gaji, Sewa kantor, Pembayaran klien"
          />
        </Field>

        <Field label="Jumlah" htmlFor="amount" required error={form.errors.amount}>
          <MoneyInput
            id="amount"
            min={1}
            value={form.data.amount}
            onChange={(value) => form.setData('amount', value)}
            required
            placeholder="Masukkan jumlah"
          />
        </Field>

        <Field label="Catatan" htmlFor="notes" error={form.errors.notes}>
          <Textarea
            id="notes"
            rows={1}
            value={form.data.notes}
            onChange={(e) => form.setData('notes', e.target.value)}
            placeholder="Opsional"
          />
        </Field>
      </FormGrid>
    </FormModal>
  );
}
