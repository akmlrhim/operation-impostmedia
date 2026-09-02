import { router } from '@inertiajs/react';

export function stripQueryParams(names: string[]): void {
  const url = new URL(window.location.href);
  let changed = false;

  for (const name of names) {
    if (url.searchParams.has(name)) {
      url.searchParams.delete(name);
      changed = true;
    }
  }

  if (changed) {
    router.replace({ url: url.pathname + url.search });
  }
}
