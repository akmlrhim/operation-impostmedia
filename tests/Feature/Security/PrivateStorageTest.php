<?php

namespace Tests\Feature\Security;

use App\Actions\Crm\ArchiveDocumentPdf;
use App\Http\Controllers\Crm\AttachmentController;
use App\Models\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivateStorageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Berkas di disk `public` tersaji lewat symlink /storage tanpa autentikasi,
     * dan nama arsip PDF ditebak dari nomor dokumen. Semua harus di disk privat.
     */
    public function test_documents_attachments_and_company_assets_use_the_private_disk(): void
    {
        $this->assertSame('local', ArchiveDocumentPdf::DISK);
        $this->assertSame('local', AttachmentController::DISK);
        $this->assertSame('local', CompanySetting::DISK);
    }

    public function test_the_private_disk_is_not_exposed_through_the_public_symlink(): void
    {
        $this->assertSame(
            storage_path('app/private'),
            config('filesystems.disks.local.root'),
        );

        $this->assertNotSame(
            config('filesystems.disks.public.root'),
            config('filesystems.disks.local.root'),
        );
    }
}
