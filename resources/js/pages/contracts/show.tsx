import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { AttachmentsCard } from '@/components/crm/attachments';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { ContractShowActions } from '@/components/crm/contracts/contract-show-actions';
import { ContractShowClausesCard } from '@/components/crm/contracts/contract-show-clauses-card';
import { ContractShowInvoicesCard } from '@/components/crm/contracts/contract-show-invoices-card';
import { ContractShowScopeCard } from '@/components/crm/contracts/contract-show-scope-card';
import { ContractShowSummaryCard } from '@/components/crm/contracts/contract-show-summary-card';
import { ContractSignModal } from '@/components/crm/contracts/contract-sign-modal';
import { PageHeader } from '@/components/crm/page-header';
import { useRealtime } from '@/hooks/use-realtime';
import { destroy, index, show } from '@/routes/contracts';
import type { CompanyIdentity, Contract, ContractClause, Option } from '@/types/crm';

type Props = {
  contract: Contract;
  company: CompanyIdentity;
  invoiceBlocker: string | null;
  types: Option[];
  statuses: Option[];
  billingCycles: Option[];
  clauses: ContractClause[];
  aiClauses: boolean;
  documentEdited: boolean;
  signatureUrl: string | null;
};

export default function ContractShow({
  contract,
  company,
  invoiceBlocker,
  types,
  statuses,
  billingCycles,
  clauses,
  aiClauses,
  documentEdited,
  signatureUrl,
}: Props) {
  useRealtime(['contracts', 'attachments'], ['contract']);

  const [confirm, confirmDialog] = useConfirm();
  const [signModal, setSignModal] = useState(false);

  return (
    <>
      <Head title={contract.number} />

      <div className="flex flex-1 flex-col gap-4 p-4">
        <PageHeader
          title={contract.title}
          actions={
            <ContractShowActions
              contract={contract}
              confirm={confirm}
              onSign={() => setSignModal(true)}
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
              company={company}
              types={types}
              statuses={statuses}
              billingCycles={billingCycles}
            />
            <ContractShowInvoicesCard contract={contract} invoiceBlocker={invoiceBlocker} />
          </div>
        </div>

        <AttachmentsCard
          type="contracts"
          id={contract.id}
          attachments={contract.attachments ?? []}
        />
      </div>

      {signModal && (
        <ContractSignModal
          contract={contract}
          signatureUrl={signatureUrl}
          onClose={() => setSignModal(false)}
        />
      )}

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
