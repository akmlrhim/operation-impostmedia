import { Link } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { relativeDueLabel, shortDate } from '@/lib/format';
import { show } from '@/routes/leads';

export type AgendaItem = {
  id: number;
  company_name: string;
  next_action: string | null;
  date: string;
  stage: string | null;
  color: string | null;
};

export function AgendaCard({ agenda }: { agenda: AgendaItem[] }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <CalendarClock aria-hidden className="size-4" />
          Agenda tujuh hari ke depan
        </CardTitle>
      </CardHeader>

      <CardContent>
        {agenda.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Tidak ada follow up yang dijadwalkan sampai pekan depan.
          </p>
        ) : (
          <ul className="divide-y divide-border/60">
            {agenda.map((item) => (
              <li key={item.id}>
                <Link
                  href={show(item.id)}
                  className="-mx-2 flex items-start gap-2.5 rounded-sm px-2 py-1.5 transition-colors hover:bg-accent"
                >
                  <span
                    aria-hidden
                    className="mt-1.5 size-2 shrink-0 rounded-full"
                    style={{ backgroundColor: item.color ?? 'var(--muted-foreground)' }}
                  />

                  <span className="min-w-0 flex-1">
                    <span className="block truncate text-[0.8125rem] font-medium">
                      {item.company_name}
                    </span>
                    <span className="block truncate text-xs text-muted-foreground">
                      {item.next_action || 'Follow up'}
                      {item.stage && ` · ${item.stage}`}
                    </span>
                  </span>

                  <span className="shrink-0 text-right">
                    <span className="block num text-xs">{shortDate(item.date)}</span>
                    <span className="block text-xs text-muted-foreground">
                      {relativeDueLabel(item.date)}
                    </span>
                  </span>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  );
}
