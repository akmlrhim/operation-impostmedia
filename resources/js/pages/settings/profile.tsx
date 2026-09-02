import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';
import { edit } from '@/routes/profile';
import type { Auth, GoogleAccount } from '@/types/auth';

type PageProps = {
  auth: Auth;
};

export default function Profile({ googleAccount }: { googleAccount: GoogleAccount | null }) {
  const { auth } = usePage<PageProps>().props;
  const getInitials = useInitials();

  return (
    <>
      <Head title="Profile settings" />

      <h1 className="sr-only">Profile settings</h1>

      <div className="space-y-6">
        <Heading
          variant="small"
          title="Profile"
          description="Ubah nama tampilan Anda di aplikasi ini"
        />

        <div className="flex items-center gap-4">
          <Avatar className="size-16 overflow-hidden rounded-full">
            <AvatarImage
              src={googleAccount?.avatar_url ?? undefined}
              alt={auth.user.name}
              referrerPolicy="no-referrer"
            />
            <AvatarFallback className="bg-neutral-200 text-lg text-black dark:bg-neutral-700 dark:text-white">
              {getInitials(auth.user.name)}
            </AvatarFallback>
          </Avatar>

          <p className="text-xs text-muted-foreground">
            Foto profil diambil dari akun Google Anda. Ubah di akun Google, lalu masuk ulang di
            sini.
          </p>
        </div>

        <Form
          {...ProfileController.update.form()}
          options={{
            preserveScroll: true,
          }}
          className="space-y-6"
        >
          {({ processing, errors }) => (
            <>
              <div className="grid gap-2">
                <Label htmlFor="name">Nama</Label>

                <Input
                  id="name"
                  className="mt-1 block w-full"
                  defaultValue={auth.user.name}
                  name="name"
                  required
                  autoComplete="name"
                  placeholder="Nama lengkap"
                />

                <InputError className="mt-2" message={errors.name} />
              </div>

              <div className="grid gap-2">
                <Label htmlFor="email">Email</Label>

                <Input
                  id="email"
                  type="email"
                  className="mt-1 block w-full"
                  value={googleAccount?.email ?? auth.user.email}
                  readOnly
                  disabled
                />

                <p className="text-xs text-muted-foreground">
                  Dikelola akun Google Anda. Ubah di akun Google, lalu masuk ulang di sini.
                </p>
              </div>

              <div className="flex items-center gap-4">
                <Button disabled={processing} data-test="update-profile-button">
                  Simpan
                </Button>
              </div>
            </>
          )}
        </Form>
      </div>

      <DeleteUser />
    </>
  );
}

Profile.layout = {
  breadcrumbs: [
    {
      title: 'Profile settings',
      href: edit(),
    },
  ],
};
