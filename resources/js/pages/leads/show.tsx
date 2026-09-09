import { Head, Link, router } from '@inertiajs/react';
import { ArrowRightLeft, ExternalLink, Pencil, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { AttachmentsCard } from '@/components/crm/attachments';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { LeadFormModal } from '@/components/crm/lead-form-modal';
import { LeadTimeline } from '@/components/crm/leads/lead-timeline';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useRealtime } from '@/hooks/use-realtime';
import { formatDate, relativeDueLabel, rupiah } from '@/lib/format';
import { show as showClient } from '@/routes/clients';
import { destroy, convert, index, show } from '@/routes/leads';
import type { LeadActivity, LeadDetail, Option, ServiceOption } from '@/types/crm';

type StageOption = { id: number; name: string; color: string; type: string };

type Props = {
  lead: LeadDetail;
  timeline: LeadActivity[];
  activityTypes: Option[];
  stageOptions: StageOption[];
  sources: Option[];
  statuses: Option[];
  temperatures: Option[];
  services: ServiceOption[];
};

function Detail({
  label,
  children,
  className,
}: {
  label: string;
  children?: ReactNode;
  className?: string;
}) {
  return (
    <div className={className}>
      <dt className="text-xs text-muted-foreground">{label}</dt>
      <dd className="text-sm">{children || '-'}</dd>
    </div>
  );
}

export default function LeadShow({
  lead,
  timeline,
  activityTypes,
  stageOptions,
  sources,
  statuses,
  temperatures,
  services,
}: Props) {
  useRealtime(['leads', 'activities', 'attachments'], ['lead', 'timeline']);

  const [confirm, confirmDialog] = useConfirm();
  const [editModal, setEditModal] = useState(false);

  const masterPrice = lead.services.length
    ? lead.services.reduce((sum, item) => sum + item.price, 0)
    : null;
  const offMaster = masterPrice !== null && masterPrice !== lead.estimated_value;

  return (
    <>
      <Head title={lead.company_name} />

      <div className="flex flex-1 flex-col gap-4 p-4">
        <PageHeader
          title={lead.company_name}
          actions={
            <>
              <Button variant="outline" onClick={() => setEditModal(true)}>
                <Pencil className="size-4" />
                Ubah
              </Button>

              {lead.converted_client_id === null && (
                <Button variant="outline" onClick={() => router.post(convert(lead.id))}>
                  <ArrowRightLeft className="size-4" />
                  Jadikan klien
                </Button>
              )}

              <Button
                variant="ghost"
                size="icon"
                aria-label="Hapus lead"
                onClick={async () => {
                  const confirmed = await confirm({
                    title: `Hapus lead ${lead.company_name}?`,
                    description: 'Lead ini hilang dari daftar beserta catatan dan lampirannya.',
                    confirmLabel: 'Hapus lead',
                    destructive: true,
                  });

                  if (confirmed) {
                    router.delete(destroy(lead.id));
                  }
                }}
              >
                <Trash2 className="size-4" />
              </Button>
            </>
          }
        />

        <div className="grid items-start gap-4 lg:grid-cols-[1fr_1.3fr]">
          <div className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Data lead</CardTitle>
              </CardHeader>
              <CardContent>
                <dl className="grid gap-x-4 gap-y-3 sm:grid-cols-2">
                  <Detail label="Date in">{formatDate(lead.date_in)}</Detail>
                  <Detail label="Industry">{lead.industry}</Detail>
                  <Detail label="Contact person">{lead.contact_name}</Detail>
                  <Detail label="Asal daerah">{lead.region}</Detail>
                  <Detail label="Telepon">{lead.phone}</Detail>
                  <Detail label="Email">{lead.email}</Detail>
                  <Detail label="Source">
                    {lead.source && <StatusBadge value={lead.source} options={sources} />}
                  </Detail>
                  <Detail label="Stage">
                    {lead.stage && (
                      <span className="inline-flex items-center gap-1.5">
                        <span
                          aria-hidden
                          className="size-2 shrink-0 rounded-full"
                          style={{ backgroundColor: lead.stage.color }}
                        />
                        {lead.stage.name}
                      </span>
                    )}
                  </Detail>
                  <Detail label="PIC">{lead.pic}</Detail>
                  <Detail label="PIC impost">{lead.pic_impost}</Detail>

                  <Detail label="Service needed" className="sm:col-span-2">
                    {lead.services.length > 0 && (
                      <ul className="grid gap-1">
                        {lead.services.map((item) => (
                          <li key={item.id} className="flex flex-wrap items-baseline gap-x-1.5">
                            <span>
                              {item.service_name ? `${item.service_name} - ` : ''}
                              {item.name}
                            </span>
                            <span className="text-xs text-muted-foreground">
                              {rupiah(item.price)} / {item.unit}
                            </span>
                          </li>
                        ))}
                      </ul>
                    )}
                  </Detail>

                  <Detail label="Estimated value">
                    <span className="font-medium">{rupiah(lead.estimated_value)}</span>
                    {offMaster && masterPrice !== null && (
                      <span className="block text-xs text-amber-700 dark:text-amber-300">
                        Beda dari jumlah harga master ({rupiah(masterPrice)})
                      </span>
                    )}
                  </Detail>
                  <Detail label="Invoice terakhir">
                    {lead.last_invoice && (
                      <span>
                        {lead.last_invoice.number}
                        <span className="text-muted-foreground">
                          {' '}
                          · {formatDate(lead.last_invoice.issue_date)}
                        </span>
                      </span>
                    )}
                  </Detail>

                  <Detail label="Last contact date">{formatDate(lead.last_contact_date)}</Detail>
                  <Detail label="Next action date">
                    {lead.next_action_date && (
                      <span>
                        {formatDate(lead.next_action_date)}
                        <span className="text-muted-foreground">
                          {' '}
                          · {relativeDueLabel(lead.next_action_date)}
                        </span>
                      </span>
                    )}
                  </Detail>
                  <Detail label="Next action" className="sm:col-span-2">
                    {lead.next_action}
                  </Detail>

                  <Detail label="Deal status">
                    <StatusBadge value={lead.status} options={statuses} />
                  </Detail>
                  <Detail label="Temperature" className="sm:col-span-2">
                    <StatusBadge value={lead.temperature} options={temperatures} />
                  </Detail>

                  {lead.lost_reason && (
                    <Detail label="Alasan gagal" className="sm:col-span-2">
                      {lead.lost_reason}
                    </Detail>
                  )}

                  <Detail label="Link folder" className="sm:col-span-2">
                    {lead.folder_url && (
                      <a
                        href={lead.folder_url}
                        target="_blank"
                        rel="noreferrer noopener"
                        className="inline-flex items-center gap-1 break-all hover:underline"
                      >
                        <ExternalLink aria-hidden className="size-3.5 shrink-0" />
                        {lead.folder_url}
                      </a>
                    )}
                  </Detail>

                  <Detail label="Klien hasil konversi" className="sm:col-span-2">
                    {lead.converted_client && (
                      <Link href={showClient(lead.converted_client.id)} className="hover:underline">
                        {lead.converted_client.company_name}
                      </Link>
                    )}
                  </Detail>

                  <Detail label="Notes ringkas" className="sm:col-span-2">
                    {lead.notes && <span className="whitespace-pre-line">{lead.notes}</span>}
                  </Detail>
                </dl>
              </CardContent>
            </Card>

            <AttachmentsCard
              type="leads"
              id={lead.id}
              attachments={lead.attachments}
              description="Brief, penawaran, atau dokumen pendukung dari klien."
            />
          </div>

          <LeadTimeline leadId={lead.id} timeline={timeline} activityTypes={activityTypes} />
        </div>
      </div>

      {editModal && (
        <LeadFormModal
          lead={lead}
          stageId={lead.stage?.id ?? null}
          stages={stageOptions.map((stage) => ({ id: stage.id, name: stage.name }))}
          sources={sources}
          statuses={statuses}
          temperatures={temperatures}
          services={services}
          onClose={() => setEditModal(false)}
        />
      )}

      {confirmDialog}
    </>
  );
}

LeadShow.layout = ({ lead }: Props) => ({
  breadcrumbs: [
    { title: 'Leads', href: index() },
    { title: lead.company_name, href: show(lead.id) },
  ],
});
