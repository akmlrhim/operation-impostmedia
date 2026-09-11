import { useState } from 'react';
import Turnstile from '@/components/turnstile';
import { Button } from '@/components/ui/button';
import { redirect } from '@/routes/google';

function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

function GoogleIcon({ className }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" aria-hidden="true">
      <path
        fill="#4285F4"
        d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.63h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.58-5.17 3.58-8.8Z"
      />
      <path
        fill="#34A853"
        d="M12 24c3.24 0 5.96-1.08 7.94-2.92l-3.88-3.01c-1.08.72-2.45 1.15-4.06 1.15-3.13 0-5.78-2.11-6.73-4.95H1.27v3.1A12 12 0 0 0 12 24Z"
      />
      <path
        fill="#FBBC05"
        d="M5.27 14.27a7.2 7.2 0 0 1 0-4.54v-3.1H1.27a12 12 0 0 0 0 10.74l4-3.1Z"
      />
      <path
        fill="#EA4335"
        d="M12 4.77c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0A12 12 0 0 0 1.27 6.63l4 3.1C6.22 6.89 8.87 4.77 12 4.77Z"
      />
    </svg>
  );
}

export default function GoogleLoginButton({
  label,
  turnstileSiteKey,
}: {
  label?: string;
  turnstileSiteKey?: string | null;
} = {}) {
  const [verified, setVerified] = useState(false);
  const [widgetFailed, setWidgetFailed] = useState(false);

  return (
    <form {...redirect.form.post()} className="grid gap-4">
      <input type="hidden" name="_token" value={csrfToken()} />

      {turnstileSiteKey && (
        <Turnstile
          siteKey={turnstileSiteKey}
          onToken={(token) => setVerified(token !== null)}
          onFailedToLoad={() => setWidgetFailed(true)}
        />
      )}

      <Button
        type="submit"
        variant="outline"
        className="w-full"
        disabled={Boolean(turnstileSiteKey) && !verified}
        data-test="google-login-button"
      >
        <GoogleIcon className="h-4 w-4" />
        {label ?? 'Lanjutkan dengan Google'}
      </Button>

      {widgetFailed && (
        <p className="text-center text-xs text-muted-foreground">
          Verifikasi keamanan gagal dimuat. Periksa koneksi atau pemblokir iklan di peramban Anda,
          lalu muat ulang halaman ini.
        </p>
      )}
    </form>
  );
}
