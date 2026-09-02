import { Head, Link } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';

type Props = {
  name: string;
  email: string;
};

export default function PendingApproval({ name, email }: Props) {
  return (
    <>
      <Head title="Menunggu verifikasi" />

      <div className="grid gap-6">
        <div className="rounded-lg border bg-muted/40 p-4 text-center">
          <p className="font-medium">{name}</p>
          <p className="text-sm text-muted-foreground">{email}</p>
        </div>

        <p className="text-center text-sm text-muted-foreground">
          Pendaftaran Anda sudah tercatat. Admin akan meninjau dan menentukan peran Anda sebelum
          data perusahaan bisa dibuka. Silakan masuk lagi setelah dikabari admin.
        </p>

        <Button variant="outline" className="w-full" asChild>
          <Link href={logout()} as="button">
            <LogOut className="mr-2 size-4" />
            Keluar
          </Link>
        </Button>
      </div>
    </>
  );
}

PendingApproval.layout = {
  title: 'Menunggu verifikasi admin',
  description: 'Akun Anda sudah dibuat, tinggal menunggu persetujuan',
};
