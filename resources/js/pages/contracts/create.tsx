import { Head } from '@inertiajs/react';
import { ContractForm } from '@/components/crm/contract-form';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { create, index } from '@/routes/contracts';
import type { Client, CompanyProfile, Option, ServiceOption, UserOption } from '@/types/crm';

type Props = {
  lead?: { id: number; company_name: string; estimated_value: string } | null;
  clientId: number | null;
  suggestedNumber: string;
  clients: Pick<Client, 'id' | 'company_name'>[];
  services: ServiceOption[];
  company: CompanyProfile;
  types: Option[];
  statuses: Option[];
  users: UserOption[];
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
  users,
  aiScopePoints,
}: Props) {
  return (
    <>
      <Head title="Buat MoU" />

      <PageHeader title="Buat MoU" backHref={index().url} />

      <PageBody className="mx-auto w-full max-w-5xl">
        <ContractForm
          lead={lead}
          clientId={clientId}
          suggestedNumber={suggestedNumber}
          clients={clients}
          services={services}
          company={company}
          types={types}
          statuses={statuses}
          users={users}
          billingCycles={billingCycles}
          aiScopePoints={aiScopePoints}
          cancelHref={index().url}
        />
      </PageBody>
    </>
  );
}

ContractCreate.layout = {
  breadcrumbs: [
    { title: 'MoU & Kontrak', href: index() },
    { title: 'Buat MoU', href: create() },
  ],
};
