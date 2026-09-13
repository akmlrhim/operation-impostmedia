import { Head, router } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { Fragment, useState } from 'react';
import { PageBody } from '@/components/crm/page-body';
import { PageHeader } from '@/components/crm/page-header';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { index as accessIndex, update as updateAccess } from '@/routes/access';

type Role = {
  value: string;
  label: string;
  locked: boolean;
};

type PermissionOption = {
  value: string;
  label: string;
  description: string;
  group: string;
};

type Props = {
  roles: Role[];
  permissions: PermissionOption[];
  matrix: Record<string, string[]>;
};

export default function AccessIndex({ roles, permissions, matrix }: Props) {
  const [draft, setDraft] = useState<Record<string, string[]>>(() => structuredClone(matrix));
  const [processing, setProcessing] = useState(false);

  const editableRoles = roles.filter((role) => !role.locked);

  const groups = permissions.reduce<Record<string, typeof permissions>>((acc, permission) => {
    (acc[permission.group] ??= []).push(permission);

    return acc;
  }, {});

  function toggle(roleValue: string, permissionValue: string, checked: boolean) {
    setDraft((current) => {
      const granted = new Set(current[roleValue] ?? []);

      if (checked) {
        granted.add(permissionValue);
      } else {
        granted.delete(permissionValue);
      }

      return { ...current, [roleValue]: [...granted] };
    });
  }

  function isGranted(roleValue: string, permissionValue: string): boolean {
    if (roles.find((role) => role.value === roleValue)?.locked) {
      return true;
    }

    return (draft[roleValue] ?? []).includes(permissionValue);
  }

  function submit() {
    setProcessing(true);

    const permissions = Object.fromEntries(
      editableRoles.map((role) => [role.value, draft[role.value] ?? []]),
    );

    router.put(
      updateAccess().url,
      { permissions },
      {
        preserveScroll: true,
        onFinish: () => setProcessing(false),
      },
    );
  }

  return (
    <>
      <Head title="Hak Akses" />

      <PageHeader
        title="Hak Akses"
        description="Atur permission yang dimiliki tiap peran. Superuser selalu punya semua hak."
        actions={
          <Button onClick={submit} disabled={processing}>
            {processing ? 'Menyimpan…' : 'Simpan perubahan'}
          </Button>
        }
      />

      <PageBody>
        <Card className="overflow-hidden py-0">
          <Table className="min-w-3xl">
            <TableHeader>
              <TableRow>
                <TableHead>Permission</TableHead>
                {roles.map((role) => (
                  <TableHead key={role.value} className="text-center">
                    <span className="inline-flex items-center gap-1">
                      {role.locked && <Lock className="size-3" />}
                      {role.label}
                    </span>
                  </TableHead>
                ))}
              </TableRow>
            </TableHeader>
            <TableBody>
              {Object.entries(groups).map(([group, groupPermissions]) => (
                <Fragment key={group}>
                  <TableRow className="bg-muted/40 hover:bg-muted/40">
                    <TableCell
                      colSpan={roles.length + 1}
                      className="py-1.5 text-xs font-bold tracking-wide text-muted-foreground uppercase"
                    >
                      {group}
                    </TableCell>
                  </TableRow>
                  {groupPermissions.map((permission) => (
                    <TableRow key={permission.value}>
                      <TableCell>
                        <div className="font-medium">{permission.label}</div>
                        <div className="text-xs text-muted-foreground">
                          {permission.description}
                        </div>
                      </TableCell>
                      {roles.map((role) => {
                        const granted = isGranted(role.value, permission.value);
                        const locked = role.locked;

                        return (
                          <TableCell key={role.value} className="text-center">
                            <Checkbox
                              aria-label={`${permission.label} untuk ${role.label}`}
                              checked={granted}
                              disabled={locked}
                              onCheckedChange={(checked) =>
                                toggle(role.value, permission.value, checked === true)
                              }
                            />
                          </TableCell>
                        );
                      })}
                    </TableRow>
                  ))}
                </Fragment>
              ))}
            </TableBody>
          </Table>
        </Card>
      </PageBody>
    </>
  );
}

AccessIndex.layout = {
  breadcrumbs: [{ title: 'Hak Akses', href: accessIndex() }],
};
