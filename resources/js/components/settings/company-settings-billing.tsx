import type { InertiaForm } from '@inertiajs/react';
import { Field, FormGrid } from '@/components/crm/field';
import Heading from '@/components/heading';
import type { CompanyFormData } from '@/components/settings/company-settings-types';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';

export function CompanySettingsBilling({ form }: { form: InertiaForm<CompanyFormData> }) {
  return (
    <>
      <Separator />

      <Heading
        variant="small"
        title="Isi cetakan invoice"
        description="Dua blok teks di bawah tabel invoice, dicetak apa adanya"
      />

      <FormGrid>
        <Field
          label="Catatan invoice"
          htmlFor="invoice_notes"
          className="sm:col-span-2"
          hint="Dicetak di blok Notes. Ketentuan jatuh tempo pembayaran ditulis di sini."
          error={form.errors.invoice_notes}
        >
          <Textarea
            id="invoice_notes"
            rows={4}
            value={form.data.invoice_notes}
            onChange={(e) => form.setData('invoice_notes', e.target.value)}
            placeholder={
              'Kami mengucapkan terima kasih atas kepercayaan Anda.\n\nJatuh tempo pembayaran 14 hari kalender sejak tanggal invoice.'
            }
          />
        </Field>

        <Field
          label="Syarat & Ketentuan"
          htmlFor="terms"
          className="sm:col-span-2"
          hint="Dicetak di blok Terms. Rekening penerima ditulis di sini juga."
          error={form.errors.terms}
        >
          <Textarea
            id="terms"
            rows={6}
            value={form.data.terms}
            onChange={(e) => form.setData('terms', e.target.value)}
            placeholder={
              'Metode Pembayaran: Transfer\nBank BRI – No. Rek. 000000000000 a.n. Nama Pemilik\n\nKonfirmasi Pembayaran: Mohon kirim bukti transfer melalui WhatsApp/email setelah melakukan pembayaran.'
            }
          />
        </Field>
      </FormGrid>
    </>
  );
}
