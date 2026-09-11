import { Head, router } from '@inertiajs/react';
import { Check, Pencil, Power, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useConfirm } from '@/components/crm/confirm-dialog';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { RowActions } from '@/components/crm/row-actions';
import type { RowAction } from '@/components/crm/row-actions';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { UserFormModal } from '@/components/users/user-form-modal';
import { formatDate } from '@/lib/format';
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
  is_last_superuser: boolean;
};

type Props = {
  users: UserRow[];
  roles: Option[];
};

export default function UsersIndex({ users, roles }: Props) {
  const [confirm, confirmDialog] = useConfirm();
  const [editing, setEditing] = useState<UserRow | null>(null);

  const pendingCount = useMemo(
    () => users.filter((user) => user.approved_at === null).length,
    [users],
  );

  function roleLabel(user: UserRow) {
    return roles.find((role) => role.value === user.role)?.label ?? user.role;
  }

  function deleteReason(user: UserRow): string | undefined {
    if (user.is_self) {
      return 'Akun sendiri tidak bisa dihapus';
    }

    if (user.is_last_superuser) {
      return 'Superuser terakhir harus tetap ada';
    }

    return undefined;
  }

  function actionsFor(user: UserRow): RowAction[] {
    const isPending = user.approved_at === null;

    const actions: RowAction[] = [];

    if (isPending) {
      actions.push({
        label: 'Setujui pendaftaran',
        icon: Check,
        onSelect: () =>
          router.post(
            approve(user.id),
            { role: user.role, is_active: true },
            { preserveScroll: true },
          ),
      });
    }

    actions.push({
      label: 'Ubah peran dan akses',
      icon: Pencil,
      onSelect: () => setEditing(user),
      disabledReason: isPending ? 'Setujui pendaftarannya dulu' : undefined,
    });

    if (!isPending) {
      actions.push({
        label: user.is_active ? 'Cabut akses' : 'Pulihkan akses',
        icon: Power,
        disabledReason: user.is_self ? 'Akses sendiri tidak bisa dicabut' : undefined,
        onSelect: async () => {
          if (user.is_active) {
            const confirmed = await confirm({
              title: `Cabut akses ${user.name}?`,
              description:
                'Orangnya tidak bisa masuk lagi. Lead, klien, dan aktivitas miliknya tetap tersimpan, dan aksesnya bisa dipulihkan kapan saja.',
              confirmLabel: 'Cabut akses',
              destructive: true,
            });

            if (!confirmed) {
              return;
            }
          }

          router.put(
            update(user.id),
            { role: user.role, is_active: !user.is_active },
            { preserveScroll: true },
          );
        },
      });
    }

    actions.push({
      label: isPending ? 'Tolak pendaftaran' : 'Hapus pengguna',
      icon: Trash2,
      destructive: true,
      disabledReason: deleteReason(user),
      onSelect: async () => {
        const confirmed = await confirm({
          title: isPending ? `Tolak pendaftaran ${user.name}?` : `Hapus ${user.name}?`,
          description: isPending
            ? 'Barisnya dihapus. Orangnya masih bisa mendaftar lagi lewat Google kapan saja.'
            : 'Akunnya hilang dari daftar. Lead, MoU, dan invoice yang pernah dipegangnya tetap tersimpan, hanya tidak lagi tercatat atas namanya.',
          confirmLabel: isPending ? 'Tolak pendaftaran' : 'Hapus pengguna',
          destructive: true,
        });

        if (confirmed) {
          router.delete(destroy(user.id), { preserveScroll: true });
        }
      },
    });

    return actions;
  }

  return (
    <>
      <Head title="Pengguna" />

      <PageHeader
        title="Pengguna"
        meta={
          pendingCount > 0 ? (
            <Badge variant="outline" className="num">
              {pendingCount} menunggu persetujuan
            </Badge>
          ) : undefined
        }
      />

      <PageBody>
        <Card className="overflow-hidden py-0">
          <Table className="min-w-3xl">
            <TableHeader>
              <TableRow>
                <TableHead>Nama</TableHead>
                <TableHead>Peran</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Bergabung</TableHead>
                <TableHead className="w-12" />
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.length === 0 && (
                <TableRow>
                  <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                    Belum ada pengguna.
                  </TableCell>
                </TableRow>
              )}

              {users.map((user) => {
                const isPending = user.approved_at === null;

                return (
                  <TableRow key={user.id} className={user.is_active ? '' : 'opacity-60'}>
                    <TableCell>
                      <div className="font-medium">
                        {user.name}
                        {user.is_self && (
                          <span className="ml-2 text-xs text-muted-foreground">(Anda)</span>
                        )}
                      </div>
                      <div className="text-xs text-muted-foreground">{user.email}</div>
                    </TableCell>

                    <TableCell>{roleLabel(user)}</TableCell>

                    <TableCell>
                      {isPending && <Badge>Menunggu persetujuan</Badge>}
                      {!isPending && user.is_active && <Badge variant="outline">Aktif</Badge>}
                      {!isPending && !user.is_active && (
                        <Badge variant="outline">Akses dicabut</Badge>
                      )}
                    </TableCell>

                    <TableCell className="num whitespace-nowrap">
                      {formatDate(user.created_at)}
                    </TableCell>

                    <TableCell>
                      <RowActions label={user.name} actions={actionsFor(user)} />
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </Card>
      </PageBody>

      {editing && <UserFormModal user={editing} roles={roles} onClose={() => setEditing(null)} />}

      {confirmDialog}
    </>
  );
}

UsersIndex.layout = {
  breadcrumbs: [{ title: 'Pengguna', href: index() }],
};
