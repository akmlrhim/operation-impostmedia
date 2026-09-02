import { Head, router } from '@inertiajs/react';
import { AttachmentsCard } from '@/components/crm/attachments';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { ContractShowActions } from '@/components/crm/contracts/contract-show-actions';
import { ContractShowClausesCard } from '@/components/crm/contracts/contract-show-clauses-card';
import { ContractShowInvoicesCard } from '@/components/crm/contracts/contract-show-invoices-card';
import { ContractShowScopeCard } from '@/components/crm/contracts/contract-show-scope-card';
import { ContractShowSummaryCard } from '@/components/crm/contracts/contract-show-summary-card';
import { PageHeader } from '@/components/crm/page-header';
import { useRealtime } from '@/hooks/use-realtime';
import { destroy, index, show } from '@/routes/contracts';
import type { Contract, ContractClause, Option } from '@/types/crm';

type Props = {
  contract: Contract;
  types: Option[];
  statuses: Option[];
  billingCycles: Option[];
  clauses: ContractClause[];
  aiClauses: boolean;
  documentEdited: boolean;
};

export default function ContractShow({
  contract,
  types,
  statuses,
  billingCycles,
  clauses,
  aiClauses,
  documentEdited,
}: Props) {
  useRealtime(['contracts', 'attachments'], ['contract']);

  const canInvoice = ['signed', 'active', 'completed'].includes(contract.status);
  const [confirm, confirmDialog] = useConfirm();

  return (
    <>
      <Head title={contract.number} />

      <div className="flex flex-1 flex-col gap-4 p-4">
        <PageHeader
          title={contract.title}
          actions={
            <ContractShowActions
              contract={contract}
              canInvoice={canInvoice}
              confirm={confirm}
              onDelete={() => router.delete(destroy(contract.id))}
            />
          }
        />

        <div className="grid gap-4 lg:grid-cols-[2fr_1fr]">
          <div className="space-y-4">
            <ContractShowScopeCard contract={contract} />

            {clauses.length > 0 && (
              <ContractShowClausesCard
                contractId={contract.id}
                clauses={clauses}
                aiEnabled={aiClauses}
                documentEdited={documentEdited}
              />
            )}
          </div>

          <div className="space-y-4">
            <ContractShowSummaryCard
              contract={contract}
              types={types}
              statuses={statuses}
              billingCycles={billingCycles}
            />
            <ContractShowInvoicesCard contract={contract} canInvoice={canInvoice} />
          </div>
        </div>

        <AttachmentsCard
          type="contracts"
          id={contract.id}
          attachments={contract.attachments ?? []}
        />
      </div>

      {confirmDialog}
    </>
  );
}

ContractShow.layout = ({ contract }: Props) => ({
  breadcrumbs: [
    { title: 'MoU & Kontrak', href: index() },
    { title: contract.number, href: show(contract.id) },
  ],
});
