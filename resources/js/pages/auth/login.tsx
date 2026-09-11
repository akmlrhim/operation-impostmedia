import { Head } from '@inertiajs/react';
import GoogleLoginButton from '@/components/google-login-button';
import InputError from '@/components/input-error';

type Props = {
  status?: string;
  errors: { google?: string };
  turnstileSiteKey: string | null;
};

export default function Login({ status, errors, turnstileSiteKey }: Props) {
  return (
    <>
      <Head title="Masuk" />

      <div className="grid gap-4">
        <GoogleLoginButton turnstileSiteKey={turnstileSiteKey} />

        <InputError message={errors.google} className="text-center" />
      </div>

      {status && (
        <div className="mt-4 text-center text-sm font-medium text-green-600">{status}</div>
      )}
    </>
  );
}

Login.layout = {
  title: 'Masuk ke akun anda',
  description:
    'Masuk dengan menggunakan akun google anda yang digunakan juga saat operasional dikantor.',
};
