import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { getEcho } from '@/lib/echo';

type CrmChanged = {
  resource: string;
  action: 'created' | 'updated' | 'deleted' | 'restored';
  id: number | null;
};

export function useRealtime(topics: string[], only: string[] = []): void {
  const topicKey = topics.join('|');
  const onlyKey = only.join('|');

  useEffect(() => {
    const echo = getEcho();

    if (!echo) {
      return;
    }

    const watched = topicKey.split('|');
    const props = onlyKey ? onlyKey.split('|') : undefined;
    let timer: ReturnType<typeof setTimeout> | undefined;

    function handle(payload: CrmChanged) {
      if (!watched.includes(payload.resource)) {
        return;
      }

      clearTimeout(timer);
      timer = setTimeout(() => {
        router.reload({ only: props });
      }, 300);
    }

    const channel = echo.private('crm').listen('.crm.changed', handle);

    return () => {
      clearTimeout(timer);
      channel.stopListening('.crm.changed', handle);
    };
  }, [topicKey, onlyKey]);
}
