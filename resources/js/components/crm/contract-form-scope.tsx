import type { InertiaForm } from '@inertiajs/react';
import type { ContractFormData } from '@/components/crm/contract-form-types';
import { Field } from '@/components/crm/field';
import { FormTotals, TotalsRow } from '@/components/crm/form-totals';
import { LineItemsEditor } from '@/components/crm/line-items';
import { Input } from '@/components/ui/input';
import { writeScopePoints } from '@/lib/crm/scope-points';
import { rupiah } from '@/lib/format';
import type { ServiceOption } from '@/types/crm';

export function ContractFormScope({
  form,
  services,
  subtotal,
  tax,
  aiScopePoints,
}: {
  form: InertiaForm<ContractFormData>;
  services: ServiceOption[];
  subtotal: number;
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
        <Field
          label="PPN (%)"
          htmlFor="tax_percent"
          className="max-w-40"
          error={form.errors.tax_percent}
        >
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

        <dl className="space-y-2 border-t pt-4 text-sm">
          <TotalsRow label="Subtotal">{rupiah(subtotal)}</TotalsRow>
          <TotalsRow label="PPN">{rupiah(tax)}</TotalsRow>
          <TotalsRow label="Nilai kontrak" strong>
            {rupiah(subtotal + tax)}
          </TotalsRow>
        </dl>
      </FormTotals>
    </div>
  );
}
