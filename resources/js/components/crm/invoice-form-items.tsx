import type { InertiaForm } from '@inertiajs/react';
import { Field } from '@/components/crm/field';
import { FormTotals, TotalsRow } from '@/components/crm/form-totals';
import type { InvoiceFormData } from '@/components/crm/invoice-form-types';
import { LineItemsEditor } from '@/components/crm/line-items';
import { Input } from '@/components/ui/input';
import { MoneyInput } from '@/components/ui/money-input';
import { rupiah } from '@/lib/format';
import type { ServiceOption } from '@/types/crm';

export function InvoiceFormItems({
  form,
  services,
  base,
  tax,
  total,
}: {
  form: InertiaForm<InvoiceFormData>;
  services: ServiceOption[];
  base: number;
  tax: number;
  total: number;
}) {
  return (
    <div className="space-y-6">
      <LineItemsEditor
        items={form.data.items}
        services={services}
        onChange={(items) => form.setData('items', items)}
        errors={form.errors as Record<string, string>}
      />

      <FormTotals>
        <div className="grid grid-cols-2 gap-4">
          <Field label="Diskon" htmlFor="discount_amount" error={form.errors.discount_amount}>
            <MoneyInput
              id="discount_amount"
              value={form.data.discount_amount}
              onChange={(value) => form.setData('discount_amount', value)}
              placeholder="0"
            />
          </Field>

          <Field label="PPN (%)" htmlFor="tax_percent" error={form.errors.tax_percent}>
            <Input
              id="tax_percent"
              type="number"
              min="0"
              max="100"
              step="0.01"
              value={form.data.tax_percent}
              onChange={(e) => form.setData('tax_percent', e.target.value)}
              placeholder="11"
            />
          </Field>
        </div>

        <dl className="space-y-2 border-t pt-4 text-sm">
          <TotalsRow label="DPP">{rupiah(base)}</TotalsRow>
          <TotalsRow label="PPN">{rupiah(tax)}</TotalsRow>
          <TotalsRow label="Total tagihan" strong>
            {rupiah(total)}
          </TotalsRow>
        </dl>
      </FormTotals>
    </div>
  );
}
