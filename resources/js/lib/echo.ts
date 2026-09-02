import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

const key = import.meta.env.VITE_PUSHER_APP_KEY;
const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;

let instance: Echo<'pusher'> | null = null;

export function getEcho(): Echo<'pusher'> | null {
  if (!key) {
    return null;
  }

  if (!instance) {
    window.Pusher = Pusher;

    instance = new Echo({
      broadcaster: 'pusher',
      key,
      cluster: cluster || 'mt1',
      forceTLS: true,
    });
  }

  return instance;
}

export function currentSocketId(): string | undefined {
  return instance?.socketId();
}
