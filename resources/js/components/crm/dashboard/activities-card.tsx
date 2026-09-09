import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { relativeTime } from '@/lib/format';

export function ActivitiesCard({
  activities,
  period,
}: {
  period: string;
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
        <CardTitle className="text-base">Aktivitas {period}</CardTitle>
      </CardHeader>

      <CardContent className="max-h-72 overflow-y-auto overscroll-contain">
        {activities.length === 0 ? (
          <p className="text-sm text-muted-foreground">Belum ada aktivitas pada periode ini.</p>
        ) : (
          <ol className="space-y-4">
            {activities.map((activity) => (
              <li key={activity.id} className="flex gap-3">
                <span aria-hidden className="mt-1.5 size-1.5 shrink-0 rounded-full bg-border" />
                <div className="min-w-0 space-y-0.5">
                  <p className="truncate text-sm font-medium">{activity.title}</p>
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
