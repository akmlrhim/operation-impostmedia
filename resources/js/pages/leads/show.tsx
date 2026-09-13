import { Head, Link, router } from '@inertiajs/react';
import { ArrowRightLeft, ExternalLink, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AttachmentsCard } from '@/components/crm/attachments';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { LeadFormModal } from '@/components/crm/lead-form-modal';
import { LeadTimeline } from '@/components/crm/leads/lead-timeline';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useRealtime } from '@/hooks/use-realtime';
import { formatDate, relativeDueLabel, rupiah } from '@/lib/format';
import { useCan } from '@/lib/use-can';
import { show as showClient } from '@/routes/clients';
import { destroy, convert, index, show } from '@/routes/leads';
import type { LeadActivity, LeadDetail, Option, ServiceOption, UserOption } from '@/types/crm';

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
  users: UserOption[];
};

export default function LeadShow({
  lead,
  timeline,
  activityTypes,
  stageOptions,
  sources,
  statuses,
  temperatures,
  services,
  users,
}: Props) {
  useRealtime(['leads', 'activities', 'attachments'], ['lead', 'timeline']);

  const [confirm, confirmDialog] = useConfirm();
  const [editModal, setEditModal] = useState(false);
  const can = useCan();

  const masterPrice = lead.services.length
    ? lead.services.reduce((sum, item) => sum + item.price, 0)
    : null;
  const offMaster = masterPrice !== null && masterPrice !== lead.estimated_value;

  return (
    <>
      <Head title={lead.company_name} />

      <PageHeader
        title={lead.company_name}
        backHref={index().url}
        meta={<StatusBadge value={lead.status} options={statuses} />}
        actions={
          <>
            <Button variant="outline" onClick={() => setEditModal(true)}>
              <Pencil className="size-4" />
              Ubah
            </Button>

            {can['manage-records'] && lead.converted_client_id === null && (
              <Button variant="outline" onClick={() => router.post(convert(lead.id))}>
                <ArrowRightLeft className="size-4" />
                Jadikan klien
              </Button>
            )}

            {can['manage-records'] && (
              <Button
                variant="ghost"
                size="icon-sm"
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
            )}
          </>
        }
      />

      <PageBody>
        <div className="space-y-3">
          <Card>
            <CardHeader>
              <CardTitle>Data lead</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="overflow-x-auto rounded-md border">
                  <table className="w-full text-sm">
                    <tbody className="divide-y">
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Date in
                        </th>
                        <td className="border-l border-border px-3 py-2">{formatDate(lead.date_in)}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Industry
                        </th>
                        <td className="border-l border-border px-3 py-2">{lead.industry || '-'}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Posisi loker
                        </th>
                        <td className="border-l border-border px-3 py-2">{lead.vacancy_position || '-'}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Contact person
                        </th>
                        <td className="border-l border-border px-3 py-2">{lead.contact_name || '-'}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Asal daerah
                        </th>
                        <td className="border-l border-border px-3 py-2">{lead.region || '-'}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Telepon
                        </th>
                        <td className="border-l border-border px-3 py-2">{lead.phone || '-'}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Email
                        </th>
                        <td className="border-l border-border px-3 py-2">{lead.email || '-'}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Source
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          {lead.source ? <StatusBadge value={lead.source} options={sources} /> : '-'}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Stage
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          {lead.stage ? (
                            <span className="inline-flex items-center gap-1.5">
                              <span
                                aria-hidden
                                className="size-2 shrink-0 rounded-full"
                                style={{ backgroundColor: lead.stage.color }}
                              />
                              {lead.stage.name}
                            </span>
                          ) : (
                            '-'
                          )}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Penanggung jawab
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          {lead.assignees && lead.assignees.length > 0 ? (
                            <ul className="flex flex-wrap gap-1.5">
                              {lead.assignees.map((user) => (
                                <li key={user.id}>
                                  <span className="inline-flex items-center rounded-md border px-2 py-0.5 text-xs">
                                    {user.name}
                                  </span>
                                </li>
                              ))}
                            </ul>
                          ) : (
                            '-'
                          )}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          PIC
                        </th>
                        <td className="border-l border-border px-3 py-2">{lead.pic || '-'}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Service needed
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          {lead.services.length > 0 ? (
                            <ol className="grid gap-1.5">
                              {lead.services.map((item, index) => (
                                <li
                                  key={item.id}
                                  className="flex items-baseline justify-between gap-x-3 gap-y-0.5"
                                >
                                  <span className="flex items-baseline gap-2">
                                    <span className="min-w-[1.25rem] text-xs font-semibold text-muted-foreground tabular-nums">
                                      {index + 1}.
                                    </span>
                                    <span>
                                      {item.service_name ? `${item.service_name} - ` : ''}
                                      {item.name}
                                    </span>
                                  </span>
                                  <span className="whitespace-nowrap text-xs text-muted-foreground">
                                    {rupiah(item.price)} / {item.unit}
                                  </span>
                                </li>
                              ))}
                            </ol>
                          ) : (
                            '-'
                          )}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Estimated value
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          <span className="num font-medium">{rupiah(lead.estimated_value)}</span>
                          {offMaster && masterPrice !== null && (
                            <span className="block text-xs text-amber-700 dark:text-amber-300">
                              Beda dari jumlah harga master ({rupiah(masterPrice)})
                            </span>
                          )}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Invoice terakhir
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          {lead.last_invoice ? (
                            <span>
                              {lead.last_invoice.number}
                              <span className="text-muted-foreground">
                                {' '}
                                · {formatDate(lead.last_invoice.issue_date)}
                              </span>
                            </span>
                          ) : (
                            '-'
                          )}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Last contact date
                        </th>
                        <td className="border-l border-border px-3 py-2">{formatDate(lead.last_contact_date)}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Next action date
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          {lead.next_action_date ? (
                            <span>
                              {formatDate(lead.next_action_date)}
                              <span className="text-muted-foreground">
                                {' '}
                                · {relativeDueLabel(lead.next_action_date)}
                              </span>
                            </span>
                          ) : (
                            '-'
                          )}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Next action
                        </th>
                        <td className="border-l border-border px-3 py-2">{lead.next_action || '-'}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Tanggal meeting
                        </th>
                        <td className="border-l border-border px-3 py-2">{formatDate(lead.meeting_date)}</td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Deal status
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          <StatusBadge value={lead.status} options={statuses} />
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Temperature
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          <StatusBadge value={lead.temperature} options={temperatures} />
                        </td>
                      </tr>
                      {lead.lost_reason && (
                        <tr>
                          <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                            Alasan gagal
                          </th>
                          <td className="border-l border-border px-3 py-2 whitespace-pre-line">{lead.lost_reason}</td>
                        </tr>
                      )}
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Link folder
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          {lead.folder_url ? (
                            <a
                              href={lead.folder_url}
                              target="_blank"
                              rel="noreferrer noopener"
                              className="inline-flex items-center gap-1 break-all hover:underline"
                            >
                              <ExternalLink aria-hidden className="size-3.5 shrink-0" />
                              {lead.folder_url}
                            </a>
                          ) : (
                            '-'
                          )}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Klien hasil konversi
                        </th>
                        <td className="border-l border-border px-3 py-2">
                          {lead.converted_client ? (
                            <Link href={showClient(lead.converted_client.id)} className="hover:underline">
                              {lead.converted_client.company_name}
                            </Link>
                          ) : (
                            '-'
                          )}
                        </td>
                      </tr>
                      <tr>
                        <th className="w-1/3 px-3 py-2 text-left align-top text-[0.6875rem] font-semibold tracking-[0.04em] text-muted-foreground uppercase">
                          Notes ringkas
                        </th>
                        <td className="border-l border-border px-3 py-2 whitespace-pre-line">{lead.notes || '-'}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>

            <AttachmentsCard
              type="leads"
              id={lead.id}
              attachments={lead.attachments}
              description="Brief, penawaran, atau dokumen pendukung dari klien."
            />
            <LeadTimeline leadId={lead.id} timeline={timeline} activityTypes={activityTypes} />
          </div>
      </PageBody>

      {editModal && (
        <LeadFormModal
          lead={lead}
          stageId={lead.stage?.id ?? null}
          stages={stageOptions.map((stage) => ({ id: stage.id, name: stage.name }))}
          sources={sources}
          statuses={statuses}
          temperatures={temperatures}
          services={services}
          users={users}
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
