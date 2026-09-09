import type { InertiaForm } from '@inertiajs/react';
import type { ContractFormData } from '@/components/crm/contract-form-types';
import { Field } from '@/components/crm/field';
import { FormTotals, TotalsRow } from '@/components/crm/form-totals';
import { LineItemsEditor } from '@/components/crm/line-items';
import { Input } from '@/components/ui/input';
import { MoneyInput } from '@/components/ui/money-input';
import { writeScopePoints } from '@/lib/crm/scope-points';
import { rupiah } from '@/lib/format';
import type { ServiceOption } from '@/types/crm';

export function ContractFormScope({
  form,
  services,
  subtotal,
  base,
  tax,
  aiScopePoints,
}: {
  form: InertiaForm<ContractFormData>;
  services: ServiceOption[];
  subtotal: number;
  base: number;
  tax: number;
  aiScopePoints: boolean;
}) {
  return (
    <div className="space-y-6">
      <LineItemsEditor
        items={form.data.items}
        services={services}
        onChange={(items) => form.setData('items', items)}
        errors={form.errors as Record<string, string>}
        writePoints={
          aiScopePoints
            ? (item) =>
                writeScopePoints({
                  name: item.name,
                  service_package_id: item.service_package_id,
                  client_id: form.data.client_id === '' ? null : Number(form.data.client_id),
                  title: form.data.title === '' ? null : form.data.title,
                })
            : undefined
        }
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
          <TotalsRow label="Subtotal">{rupiah(subtotal)}</TotalsRow>
          {Number(form.data.discount_amount) > 0 && (
            <TotalsRow label="Diskon">-{rupiah(form.data.discount_amount)}</TotalsRow>
          )}
          <TotalsRow label="PPN">{rupiah(tax)}</TotalsRow>
          <TotalsRow label="Nilai kontrak" strong>
            {rupiah(base + tax)}
          </TotalsRow>
        </dl>
      </FormTotals>
    </div>
  );
}
