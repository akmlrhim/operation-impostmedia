import { DocumentItems } from '@/components/crm/document-items';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { decimal, rupiah } from '@/lib/format';
import type { Contract } from '@/types/crm';

export function ContractShowScopeCard({ contract }: { contract: Contract }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Ruang lingkup pekerjaan</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {contract.scope && (
          <p className="text-sm whitespace-pre-line text-muted-foreground">{contract.scope}</p>
        )}

        <DocumentItems items={contract.items ?? []} />

        <div className="ml-auto w-full max-w-xs space-y-1.5 text-sm">
          <div className="flex justify-between">
            <span className="text-muted-foreground">Subtotal</span>
            <span>{rupiah(contract.subtotal)}</span>
          </div>
          {Number(contract.tax_amount) > 0 && (
            <div className="flex justify-between">
              <span className="text-muted-foreground">PPN {decimal(contract.tax_percent)}%</span>
              <span>{rupiah(contract.tax_amount)}</span>
            </div>
          )}
          <div className="flex justify-between border-t pt-1.5 text-base font-semibold">
            <span>Nilai kontrak</span>
            <span>{rupiah(contract.value)}</span>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
