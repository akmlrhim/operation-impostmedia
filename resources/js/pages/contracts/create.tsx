import { Head } from '@inertiajs/react';
import { ContractForm } from '@/components/crm/contract-form';
import { PageHeader } from '@/components/crm/page-header';
import { create, index } from '@/routes/contracts';
import type { Client, CompanyProfile, Option, ServiceOption } from '@/types/crm';

type Props = {
  lead?: { id: number; company_name: string; estimated_value: string } | null;
  clientId: number | null;
  suggestedNumber: string;
  clients: Pick<Client, 'id' | 'company_name'>[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
  billingCycles: Option[];
  aiScopePoints: boolean;
};

export default function ContractCreate({
  lead,
  clientId,
  suggestedNumber,
  clients,
  services,
  company,
  types,
  statuses,
  billingCycles,
  aiScopePoints,
}: Props) {
  return (
    <>
      <Head title="Buat MoU" />

      <div className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <PageHeader title="Buat MoU" backHref={index().url} />

        <ContractForm
          lead={lead}
          clientId={clientId}
          suggestedNumber={suggestedNumber}
          clients={clients}
          services={services}
          company={company}
          types={types}
          statuses={statuses}
          billingCycles={billingCycles}
          aiScopePoints={aiScopePoints}
          cancelHref={index().url}
        />
      </div>
    </>
  );
}

ContractCreate.layout = {
  breadcrumbs: [
    { title: 'MoU & Kontrak', href: index() },
    { title: 'Buat MoU', href: create() },
  ],
};
