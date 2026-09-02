import { Form } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

export default function DeleteUser() {
  return (
    <div className="space-y-6">
      <Heading
        variant="small"
        title="Hapus akun"
        description="Hapus akun Anda beserta seluruh datanya"
      />
      <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
        <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
          <p className="font-medium">Perhatian</p>
          <p className="text-sm">Tindakan ini tidak bisa dibatalkan.</p>
        </div>

        <Dialog>
          <DialogTrigger asChild>
            <Button variant="destructive" data-test="delete-user-button">
              Hapus akun
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogTitle>Hapus akun Anda?</DialogTitle>
            <DialogDescription>
              Seluruh data yang menempel pada akun ini ikut terhapus permanen, dan sesi Anda
              langsung diakhiri.
            </DialogDescription>

            <Form
              {...ProfileController.destroy.form()}
              options={{
                preserveScroll: true,
              }}
              className="space-y-6"
            >
              {({ processing }) => (
                <DialogFooter className="gap-2">
                  <DialogClose asChild>
                    <Button variant="secondary">Batal</Button>
                  </DialogClose>

                  <Button variant="destructive" disabled={processing} asChild>
                    <button type="submit" data-test="confirm-delete-user-button">
                      Hapus akun
                    </button>
                  </Button>
                </DialogFooter>
              )}
            </Form>
          </DialogContent>
        </Dialog>
      </div>
    </div>
  );
}
