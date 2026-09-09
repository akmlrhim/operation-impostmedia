<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractDocument;
use App\Support\Crm\ContractIndexQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractPayloadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * HTML MoU yang sudah dirender terukur 925 KB untuk satu MoU karena logo,
     * tanda tangan, dan stempel tertanam sebagai data URI. Selama kolomnya
     * menempel di `contracts`, setiap route-model binding menariknya dari MySQL
     * dan halaman daftar mengirimnya sekali per baris.
     */
    public function test_the_rendered_html_does_not_live_on_the_contracts_table(): void
    {
        $this->assertFalse(Schema::hasColumn('contracts', 'body'));
        $this->assertFalse(Schema::hasColumn('contracts', 'document_body'));
        $this->assertTrue(Schema::hasColumn('contract_documents', 'body'));
        $this->assertTrue(Schema::hasColumn('contract_documents', 'document_body'));
    }

    public function test_a_serialized_contract_carries_no_document_html(): void
    {
        $contract = $this->makeContract();
        $contract->cacheRenderedBody(str_repeat('x', 5000));

        $payload = $contract->fresh()->toArray();

        $this->assertArrayNotHasKey('body', $payload);
        $this->assertArrayNotHasKey('document_body', $payload);
        $this->assertSame('MOU/001/IM/IX/2026', $payload['number']);
    }

    public function test_the_rendered_body_is_readable_and_replaceable(): void
    {
        $contract = $this->makeContract();

        $this->assertNull($contract->renderedBody());

        $contract->cacheRenderedBody('<p>hasil render</p>');
        $this->assertSame('<p>hasil render</p>', $contract->renderedBody());

        $contract->cacheRenderedBody('<p>render kedua</p>');
        $this->assertSame('<p>render kedua</p>', $contract->renderedBody());
        $this->assertSame(1, ContractDocument::query()->count());
    }

    public function test_editing_the_document_drops_the_cached_render(): void
    {
        $contract = $this->makeContract();
        $contract->cacheRenderedBody('<p>hasil render</p>');

        $contract->saveEditedBody('<p>suntingan tangan</p>');

        $this->assertSame('<p>suntingan tangan</p>', $contract->editedBody());
        $this->assertNull($contract->renderedBody());
        $this->assertTrue($contract->isDocumentEdited());
    }

    public function test_resetting_the_document_clears_both_versions(): void
    {
        $contract = $this->makeContract();
        $contract->saveEditedBody('<p>suntingan tangan</p>');

        $contract->forgetDocument();

        $this->assertNull($contract->editedBody());
        $this->assertNull($contract->renderedBody());
        $this->assertFalse($contract->isDocumentEdited());
        $this->assertSame(0, ContractDocument::query()->count());
    }

    public function test_the_list_query_does_not_select_the_heavy_columns(): void
    {
        $columns = (new \ReflectionClass(ContractIndexQuery::class))
            ->getConstant('COLUMNS');

        $this->assertNotContains('contracts.*', $columns);
        $this->assertContains('contracts.number', $columns);
    }

    private function makeContract(): Contract
    {
        $client = Client::query()->create([
            'company_name' => 'PT Uji Muatan',
            'short_code' => 'PUM',
        ]);

        return Contract::query()->create([
            'number' => 'MOU/001/IM/IX/2026',
            'client_id' => $client->id,
            'title' => 'Kerja sama uji',
        ]);
    }
}
