import { Link } from '@inertiajs/react';
import { ChevronRight, FileSignature, ListChecks, Receipt, Target } from 'lucide-react';
import type { AttentionItem } from '@/components/crm/dashboard/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate, relativeDueLabel } from '@/lib/format';
import { rupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show as showContract } from '@/routes/contracts';
import { index as invoicesIndex, show as showInvoice } from '@/routes/invoices';
import { index as leadsIndex } from '@/routes/leads';

const attentionKinds = {
  invoice: { icon: Receipt, href: (id: number) => showInvoice(id) },
  lead: { icon: Target, href: () => leadsIndex() },
  contract: { icon: FileSignature, href: (id: number) => showContract(id) },
} as const;

const severityTones = {
  critical: 'text-red-600 dark:text-red-400',
  serious: 'text-amber-600 dark:text-amber-400',
  warning: 'text-muted-foreground',
} as const;

export function AttentionCard({ attention }: { attention: AttentionItem[] }) {
  return (
    <Card className="lg:col-span-2">
      <CardHeader className="flex-row items-center gap-3">
        <CardTitle className="mr-auto flex items-center gap-2 text-base">
          <ListChecks className="size-4" />
          Butuh perhatian
        </CardTitle>
        <Link
          href={invoicesIndex()}
          className="flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
          Semua invoice
          <ChevronRight className="size-3.5" />
        </Link>
      </CardHeader>

      <CardContent>
        {attention.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Tidak ada tunggakan, follow up tertunda, atau kontrak yang segera berakhir.
          </p>
        ) : (
          <ul className="divide-y">
            {attention.map((item) => {
              const kind = attentionKinds[item.kind];

              return (
                <li key={`${item.kind}-${item.id}`}>
                  <Link
                    href={kind.href(item.id)}
                    className="-mx-2 flex items-center gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-accent"
                  >
                    <kind.icon
                      aria-hidden
                      className={cn('size-4 shrink-0', severityTones[item.severity])}
                    />

                    <span className="min-w-0 flex-1">
                      <span className="block truncate text-sm font-medium">{item.title}</span>
                      <span className="block truncate text-xs text-muted-foreground">
                        {item.subtitle} · {formatDate(item.date)}
                      </span>
                    </span>

                    <span className="shrink-0 text-right">
                      {item.amount !== null && (
                        <span className="block text-sm font-medium">{rupiah(item.amount)}</span>
                      )}
                      <span className={cn('block text-xs', severityTones[item.severity])}>
                        {relativeDueLabel(item.date)}
                      </span>
                    </span>
                  </Link>
                </li>
              );
            })}
          </ul>
        )}
      </CardContent>
    </Card>
  );
}
