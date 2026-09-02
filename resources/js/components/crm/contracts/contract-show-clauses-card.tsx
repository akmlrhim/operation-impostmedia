import { Link, useForm } from '@inertiajs/react';
import { FilePenLine, Loader2, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { clauses as writeClauses, document as documentRoute } from '@/routes/contracts';
import type { ContractClause } from '@/types/crm';

export function ContractShowClausesCard({
  contractId,
  clauses,
  aiEnabled,
  documentEdited,
}: {
  contractId: number;
  clauses: ContractClause[];
  aiEnabled: boolean;
  documentEdited: boolean;
}) {
  const rewrite = useForm({});
  const written = clauses.some((clause) => clause.points.length > 0);

  return (
    <Card>
      <CardHeader className="flex-row items-start justify-between gap-3 space-y-0">
        <div className="space-y-1">
          <CardTitle className="text-base">Pasal tulisan AI</CardTitle>
          <p className="text-xs text-muted-foreground">
            {documentEdited
              ? 'Dokumen MoU ini sudah disunting sendiri, jadi pasal di bawah tidak lagi ikut tercetak. Kembalikan dokumen ke template dulu kalau mau memakainya lagi.'
              : written
                ? 'Isi inilah yang tercetak di pasal saat dokumen dibuat. Untuk mengoreksi kalimatnya, sunting langsung di dokumen.'
                : 'Belum ditulis. Sampai ditulis, pasal ini tercetak memakai teks cadangan dari template.'}
          </p>
        </div>

        <div className="flex shrink-0 items-center gap-2">
          {aiEnabled && (
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={rewrite.processing || documentEdited}
              onClick={() => rewrite.post(writeClauses(contractId).url, { preserveScroll: true })}
            >
              {rewrite.processing ? (
                <>
                  <Loader2 className="size-4 animate-spin" />
                  Menulis…
                </>
              ) : (
                <>
                  <Sparkles className="size-4" />
                  {written ? 'Tulis ulang' : 'Tulis dengan AI'}
                </>
              )}
            </Button>
          )}

          <Button type="button" variant="ghost" size="sm" asChild>
            <Link href={documentRoute(contractId)}>
              <FilePenLine className="size-4" />
              Sunting
            </Link>
          </Button>
        </div>
      </CardHeader>

      <CardContent className="space-y-4">
        {clauses.map((clause) => (
          <div key={clause.id} className="space-y-1.5">
            <p className="text-xs font-medium text-muted-foreground">{clause.topic}</p>

            {clause.points.length === 0 ? (
              <p className="text-sm text-muted-foreground italic">Belum ditulis.</p>
            ) : (
              <ol className="list-decimal space-y-1 pl-5 text-sm">
                {clause.points.map((point, index) => (
                  <li key={index}>{point}</li>
                ))}
              </ol>
            )}
          </div>
        ))}
      </CardContent>
    </Card>
  );
}
