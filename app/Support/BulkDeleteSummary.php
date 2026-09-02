<?php

namespace App\Support;

class BulkDeleteSummary
{
    /**
     * @return array{type: string, message: string}
     */
    public static function toast(int $deleted, int $skipped, string $noun, string $reason): array
    {
        if ($deleted === 0 && $skipped === 0) {
            return ['type' => 'error', 'message' => "Tidak ada {$noun} yang dipilih."];
        }

        if ($skipped === 0) {
            return ['type' => 'success', 'message' => "{$deleted} {$noun} dihapus."];
        }

        if ($deleted === 0) {
            return ['type' => 'error', 'message' => "Semua {$noun} yang dipilih dilewati karena {$reason}."];
        }

        return [
            'type' => 'success',
            'message' => "{$deleted} {$noun} dihapus, {$skipped} dilewati karena {$reason}.",
        ];
    }
}
