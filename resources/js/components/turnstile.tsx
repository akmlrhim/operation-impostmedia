import { useEffect, useRef } from 'react';

type RenderOptions = {
  sitekey: string;
  theme?: 'light' | 'dark' | 'auto';
  callback?: (token: string) => void;
  'expired-callback'?: () => void;
  'error-callback'?: () => void;
};

declare global {
  interface Window {
    turnstile?: {
      render: (element: HTMLElement, options: RenderOptions) => string;
      remove: (widgetId: string) => void;
    };
  }
}

const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let scriptPromise: Promise<void> | null = null;

function loadScript(): Promise<void> {
  if (window.turnstile) {
    return Promise.resolve();
  }

  scriptPromise ??= new Promise<void>((resolve, reject) => {
    const script = document.createElement('script');

    script.src = SCRIPT_URL;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => {
      scriptPromise = null;
      reject(new Error('Gagal memuat Cloudflare Turnstile.'));
    };

    document.head.appendChild(script);
  });

  return scriptPromise;
}

type Props = {
  siteKey: string;
  onToken: (token: string | null) => void;
  onFailedToLoad?: () => void;
};

export default function Turnstile({ siteKey, onToken, onFailedToLoad }: Props) {
  const container = useRef<HTMLDivElement>(null);

  const handlers = useRef({ onToken, onFailedToLoad });

  useEffect(() => {
    handlers.current = { onToken, onFailedToLoad };
  }, [onToken, onFailedToLoad]);

  useEffect(() => {
    let widgetId: string | undefined;
    let cancelled = false;

    loadScript()
      .then(() => {
        if (cancelled || container.current === null || !window.turnstile) {
          return;
        }

        widgetId = window.turnstile.render(container.current, {
          sitekey: siteKey,
          theme: 'auto',
          callback: (token) => handlers.current.onToken(token),
          'expired-callback': () => handlers.current.onToken(null),
          'error-callback': () => handlers.current.onToken(null),
        });
      })
      .catch(() => {
        if (!cancelled) {
          handlers.current.onFailedToLoad?.();
        }
      });

    return () => {
      cancelled = true;

      if (widgetId !== undefined) {
        window.turnstile?.remove(widgetId);
      }
    };
  }, [siteKey]);

  return <div ref={container} className="flex justify-center" />;
}
