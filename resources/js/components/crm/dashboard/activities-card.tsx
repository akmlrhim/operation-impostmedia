import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { relativeTime } from '@/lib/format';

export function ActivitiesCard({
  activities,
  period,
}: {
  period?: string;
  activities: {
    id: number;
    type: string;
    title: string;
    subject: string | null;
    user: string | null;
    at: string | null;
  }[];
}) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{period ? `Aktivitas ${period}` : 'Aktivitas terbaru'}</CardTitle>
      </CardHeader>

      <CardContent className="max-h-64 overflow-y-auto overscroll-contain">
        {activities.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            {period ? 'Belum ada aktivitas pada periode ini.' : 'Belum ada aktivitas tercatat.'}
          </p>
        ) : (
          <ol className="space-y-2.5">
            {activities.map((activity) => (
              <li key={activity.id} className="flex gap-3">
                <span aria-hidden className="mt-1.5 size-1.5 shrink-0 rounded-full bg-border" />
                <div className="min-w-0 space-y-0.5">
                  <p className="truncate text-[0.8125rem] font-medium">{activity.title}</p>
                  <p className="truncate text-xs text-muted-foreground">
                    {activity.type}
                    {activity.subject && ` · ${activity.subject}`}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {relativeTime(activity.at)}
                    {activity.user && ` · ${activity.user}`}
                  </p>
                </div>
              </li>
            ))}
          </ol>
        )}
      </CardContent>
    </Card>
  );
}
