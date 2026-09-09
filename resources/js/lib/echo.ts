import type Echo from 'laravel-echo';
import type Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

const key = import.meta.env.VITE_PUSHER_APP_KEY;
const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;

let instance: Echo<'pusher'> | null = null;
let pending: Promise<Echo<'pusher'> | null> | null = null;

/**
 * laravel-echo dan pusher-js hanya diunduh saat halaman benar-benar memasang
 * listener realtime, bukan ikut bundel awal setiap kunjungan.
 */
export function getEcho(): Promise<Echo<'pusher'> | null> {
  if (!key) {
    return Promise.resolve(null);
  }

  if (instance) {
    return Promise.resolve(instance);
  }

  pending ??= Promise.all([import('laravel-echo'), import('pusher-js')]).then(
    ([echoModule, pusherModule]) => {
      window.Pusher = pusherModule.default;

      instance ??= new echoModule.default({
        broadcaster: 'pusher',
        key,
        cluster: cluster || 'mt1',
        forceTLS: true,
      });

      return instance;
    },
  );

  return pending;
}

export function currentSocketId(): string | undefined {
  return instance?.socketId();
}
