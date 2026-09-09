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
    const watched = topicKey.split('|');
    const props = onlyKey ? onlyKey.split('|') : undefined;
    let timer: ReturnType<typeof setTimeout> | undefined;
    let stop: (() => void) | undefined;
    let cancelled = false;

    function handle(payload: CrmChanged) {
      if (!watched.includes(payload.resource)) {
        return;
      }

      clearTimeout(timer);
      timer = setTimeout(() => {
        router.reload({ only: props });
      }, 300);
    }

    void getEcho().then((echo) => {
      if (echo === null || cancelled) {
        return;
      }

      const channel = echo.private('crm').listen('.crm.changed', handle);

      stop = () => channel.stopListening('.crm.changed', handle);
    });

    return () => {
      cancelled = true;
      clearTimeout(timer);
      stop?.();
    };
  }, [topicKey, onlyKey]);
}
