<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrivatizeStoredFiles extends Command
{
    /**
     * Folder yang dulu ditulis ke disk `public` sehingga bisa diunduh siapa pun
     * lewat symlink /storage. Sekarang semuanya dilayani route ber-auth.
     *
     * @var array<int, string>
     */
    private const FOLDERS = ['mou', 'invoice', 'attachments', 'company'];

    protected $signature = 'storage:privatize {--dry-run : Tampilkan rencana tanpa memindahkan berkas}';

    protected $description = 'Pindahkan arsip PDF, lampiran, dan aset perusahaan dari disk publik ke disk privat';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;
        $skipped = 0;

        foreach (self::FOLDERS as $folder) {
            foreach ($public->allFiles($folder) as $path) {
                if ($private->exists($path)) {
                    $this->line("  lewati (sudah ada): {$path}");
                    $skipped++;

                    continue;
                }

                $this->line(($dryRun ? '  rencana: ' : '  pindah: ').$path);

                if (! $dryRun) {
                    $private->put($path, $public->get($path));
                    $public->delete($path);
                }

                $moved++;
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? "{$moved} berkas siap dipindahkan, {$skipped} dilewati."
            : "{$moved} berkas dipindahkan, {$skipped} dilewati.");

        return self::SUCCESS;
    }
}
