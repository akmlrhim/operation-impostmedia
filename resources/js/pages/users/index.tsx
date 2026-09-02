import { Head, router } from '@inertiajs/react';
import { Check, Power, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { PageHeader } from '@/components/crm/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { approve, destroy, index, update } from '@/routes/users';
import type { Option } from '@/types/crm';

type UserRow = {
  id: number;
  name: string;
  email: string;
  role: string;
  is_active: boolean;
  approved_at: string | null;
  created_at: string | null;
  is_self: boolean;
};

type Props = {
  users: UserRow[];
  roles: Option[];
};

export default function UsersIndex({ users, roles }: Props) {
  const [confirm, confirmDialog] = useConfirm();
  const [drafts, setDrafts] = useState<Record<number, string>>({});

  const pendingCount = useMemo(
    () => users.filter((user) => user.approved_at === null).length,
    [users],
  );

  function roleOf(user: UserRow) {
    return drafts[user.id] ?? user.role;
  }

  function setRole(user: UserRow, role: string) {
    setDrafts((current) => ({ ...current, [user.id]: role }));
  }

  function saveRole(user: UserRow) {
    router.put(
      update(user.id),
      { role: roleOf(user), is_active: user.is_active },
      { preserveScroll: true },
    );
  }

  return (
    <>
      <Head title="User" />

      <div className="flex flex-1 flex-col gap-4 p-4">
        <PageHeader
          title="User"
          actions={
            pendingCount > 0 ? (
              <Badge variant="outline">{pendingCount} menunggu persetujuan</Badge>
            ) : undefined
          }
        />

        <Card className="overflow-hidden rounded-sm py-0">
          <Table className="min-w-3xl">
            <TableHeader>
              <TableRow>
                <TableHead>Nama</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="w-52">Peran</TableHead>
                <TableHead className="w-56 text-right">Tindakan</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.length === 0 && (
                <TableRow>
                  <TableCell colSpan={4} className="py-10 text-center text-muted-foreground">
                    Belum ada user.
                  </TableCell>
                </TableRow>
              )}

              {users.map((user) => {
                const isPending = user.approved_at === null;
                const roleChanged = roleOf(user) !== user.role;

                return (
                  <TableRow key={user.id} className={user.is_active ? '' : 'opacity-50'}>
                    <TableCell>
                      <div className="font-medium">
                        {user.name}
                        {user.is_self && (
                          <span className="ml-2 text-xs text-muted-foreground">(Anda)</span>
                        )}
                      </div>
                      <div className="text-sm text-muted-foreground">{user.email}</div>
                    </TableCell>

                    <TableCell>
                      {isPending && <Badge>Menunggu persetujuan</Badge>}
                      {!isPending && user.is_active && <Badge variant="outline">Aktif</Badge>}
                      {!isPending && !user.is_active && (
                        <Badge variant="outline">Dinonaktifkan</Badge>
                      )}
                    </TableCell>

                    <TableCell>
                      <Select value={roleOf(user)} onValueChange={(value) => setRole(user, value)}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {roles.map((role) => (
                            <SelectItem key={role.value} value={role.value}>
                              {role.label}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </TableCell>

                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        {isPending && (
                          <>
                            <Button
                              size="sm"
                              onClick={() =>
                                router.post(
                                  approve(user.id),
                                  { role: roleOf(user), is_active: true },
                                  { preserveScroll: true },
                                )
                              }
                            >
                              <Check className="size-4" />
                              Setujui
                            </Button>
                            <Button
                              size="sm"
                              variant="ghost"
                              onClick={async () => {
                                const confirmed = await confirm({
                                  title: `Tolak pendaftaran ${user.name}?`,
                                  description:
                                    'Barisnya dihapus. Orangnya masih bisa mendaftar lagi lewat Google kapan saja.',
                                  confirmLabel: 'Tolak pendaftaran',
                                  destructive: true,
                                });

                                if (confirmed) {
                                  router.delete(destroy(user.id), { preserveScroll: true });
                                }
                              }}
                            >
                              <Trash2 className="size-4" />
                              Tolak
                            </Button>
                          </>
                        )}

                        {!isPending && (
                          <>
                            {roleChanged && (
                              <Button size="sm" onClick={() => saveRole(user)}>
                                <Check className="size-4" />
                                Simpan peran
                              </Button>
                            )}

                            {!user.is_self && (
                              <Button
                                size="sm"
                                variant="ghost"
                                onClick={async () => {
                                  if (user.is_active) {
                                    const confirmed = await confirm({
                                      title: `Cabut akses ${user.name}?`,
                                      description:
                                        'Lead, klien, dan aktivitas miliknya tetap tersimpan. Aksesnya bisa dinyalakan lagi kapan saja.',
                                      confirmLabel: 'Cabut akses',
                                      destructive: true,
                                    });

                                    if (!confirmed) {
                                      return;
                                    }
                                  }

                                  router.put(
                                    update(user.id),
                                    { role: roleOf(user), is_active: !user.is_active },
                                    { preserveScroll: true },
                                  );
                                }}
                              >
                                <Power className="size-4" />
                                {user.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                              </Button>
                            )}
                          </>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </Card>
      </div>

      {confirmDialog}
    </>
  );
}

UsersIndex.layout = {
  breadcrumbs: [{ title: 'User', href: index() }],
};
