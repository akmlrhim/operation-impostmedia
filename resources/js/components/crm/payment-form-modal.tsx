import { useForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import { FormModal } from '@/components/crm/form-modal';
import { Button } from '@/components/ui/button';
import { DateField } from '@/components/ui/date-field';
import { MoneyInput } from '@/components/ui/money-input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/payments';
import type { Invoice, Option } from '@/types/crm';

type Props = {
  invoice: Invoice;
  methods: Option[];
  onClose: () => void;
};

export function PaymentFormModal({ invoice, methods, onClose }: Props) {
  const balance = Number(invoice.balance_due);

  const form = useForm({
    amount: balance > 0 ? String(balance) : '',
    paid_at: new Date().toISOString().slice(0, 10),
    method: 'transfer',
    notes: '',
  });

  return (
    <FormModal
      title="Catat pembayaran"
      size="sm"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(e) => {
        e.preventDefault();
        form.submit(store(invoice.id), { preserveScroll: true, onSuccess: onClose });
      }}
      footer={
        <>
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" disabled={form.processing}>
            Simpan pembayaran
          </Button>
        </>
      }
    >
      <FormGrid>
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

        <Field label="Tanggal bayar" htmlFor="paid_at" required error={form.errors.paid_at}>
          <DateField
            id="paid_at"
            value={form.data.paid_at}
            required
            clearable={false}
            onChange={(value) => form.setData('paid_at', value)}
          />
        </Field>

        <Field label="Metode" error={form.errors.method}>
          <Select value={form.data.method} onValueChange={(value) => form.setData('method', value)}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {methods.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </Field>

        <Field label="Catatan" htmlFor="notes" className="sm:col-span-2" error={form.errors.notes}>
          <Textarea
            id="notes"
            rows={2}
            value={form.data.notes}
            onChange={(e) => form.setData('notes', e.target.value)}
            placeholder="Masukkan catatan"
          />
        </Field>
      </FormGrid>
    </FormModal>
  );
}
