import { useForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import { FormModal } from '@/components/crm/form-modal';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { update } from '@/routes/users';
import type { Option } from '@/types/crm';

export type EditableUser = {
  id: number;
  name: string;
  email: string;
  role: string;
  is_active: boolean;
  is_self: boolean;
};

type UserFormData = {
  role: string;
  is_active: boolean;
};

export function UserFormModal({
  user,
  roles,
  onClose,
}: {
  user: EditableUser;
  roles: Option[];
  onClose: () => void;
}) {
  const form = useForm<UserFormData>({
    role: user.role,
    is_active: user.is_active,
  });

  return (
    <FormModal
      title={`Ubah ${user.name}`}
      size="sm"
      onOpenChange={(open) => !open && onClose()}
      onSubmit={(event) => {
        event.preventDefault();
        form.submit(update(user.id), { preserveScroll: true, onSuccess: onClose });
      }}
      footer={
        <>
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" disabled={form.processing}>
            Simpan perubahan
          </Button>
        </>
      }
    >
      <FormGrid className="sm:grid-cols-1">
        <Field label="Nama">
          <p className="text-sm">{user.name}</p>
        </Field>

        <Field label="Email" hint="Ikut akun Google, tidak bisa diubah dari sini.">
          <p className="text-sm text-muted-foreground">{user.email}</p>
        </Field>

        <Field label="Peran" htmlFor="role" required error={form.errors.role}>
          <Select value={form.data.role} onValueChange={(value) => form.setData('role', value)}>
            <SelectTrigger id="role">
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
        </Field>

        <Field
          label="Akses"
          htmlFor="is_active"
          hint="Yang dicabut aksesnya tidak bisa masuk, tapi datanya tetap tersimpan."
          error={form.errors.is_active}
        >
          <Select
            value={form.data.is_active ? 'active' : 'inactive'}
            onValueChange={(value) => form.setData('is_active', value === 'active')}
          >
            <SelectTrigger id="is_active">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="active">Aktif</SelectItem>
              <SelectItem value="inactive">Dicabut</SelectItem>
            </SelectContent>
          </Select>
        </Field>
      </FormGrid>

      {user.is_self && (
        <p className="text-xs text-muted-foreground">
          Ini akun Anda sendiri. Peran superuser dan aksesnya tidak bisa dicabut dari sini.
        </p>
      )}
    </FormModal>
  );
}
