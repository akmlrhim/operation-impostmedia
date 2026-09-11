<?php

namespace Tests\Feature\Crm;

use App\Enums\LeadTemperature;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use App\Support\Crm\DashboardAttention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LeadSheetColumnsTest extends TestCase
{
    use RefreshDatabase;

    private LeadStage $stage;

    private ServicePackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $this->stage = LeadStage::create([
            'name' => 'Prospek', 'slug' => 'prospek', 'color' => '#64748b', 'position' => 0, 'type' => 'open',
        ]);

        $service = Service::create(['type' => 'umkm', 'name' => 'Legalitas']);
        $this->package = ServicePackage::create([
            'service_id' => $service->id, 'name' => 'Paket NIB', 'price' => 750000, 'unit' => 'paket',
        ]);
    }

    public function test_lead_stores_every_column_from_the_sheet(): void
    {
        $this->post(route('leads.store'), [
            'lead_stage_id' => $this->stage->id,
            'date_in' => '2026-09-01',
            'company_name' => 'PT Kopi Nusantara',
            'industry' => 'F&B',
            'contact_name' => 'Dewi Lestari',
            'phone' => '08123456789',
            'email' => 'dewi@kopi.test',
            'region' => 'Banjarmasin',
            'source' => 'referral',
            'pic' => 'Rahim',
            'service_package_ids' => [$this->package->id],
            'estimated_value' => 96_000_000,
            'last_contact_date' => '2026-09-03',
            'next_action_date' => '2026-09-10',
            'next_action' => 'Kirim penawaran',
            'temperature' => 'hot',
            'notes' => 'Butuh NIB cepat',
            'folder_url' => 'https://drive.google.com/drive/folders/abc',
            'status' => 'open',
        ])->assertRedirect();

        $lead = Lead::firstOrFail();

        $this->assertSame('2026-09-01', $lead->date_in->toDateString());
        $this->assertSame('F&B', $lead->industry);
        $this->assertSame('Banjarmasin', $lead->region);
        $this->assertSame('Rahim', $lead->pic);
        $this->assertSame([$this->package->id], $lead->servicePackages->pluck('id')->all());
        $this->assertSame('2026-09-03', $lead->last_contact_date->toDateString());
        $this->assertSame('2026-09-10', $lead->next_action_date->toDateString());
        $this->assertSame('Kirim penawaran', $lead->next_action);
        $this->assertSame('https://drive.google.com/drive/folders/abc', $lead->folder_url);
        $this->assertSame(LeadTemperature::Hot, $lead->temperature);
    }

    public function test_lead_created_outside_the_form_starts_cold(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Belum Dihubungi',
            'contact_name' => 'Budi',
        ]);

        $this->assertSame(LeadTemperature::Cold, $lead->fresh()->temperature);
    }

    public function test_a_lead_can_need_more_than_one_service(): void
    {
        $extra = ServicePackage::create([
            'service_id' => $this->package->service_id,
            'name' => 'Paket Halal',
            'price' => 1_250_000,
            'unit' => 'paket',
        ]);

        $this->post(route('leads.store'), [
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Dua Layanan',
            'contact_name' => 'Budi',
            'service_package_ids' => [$extra->id, $this->package->id],
            'estimated_value' => 2_000_000,
            'status' => 'open',
            'temperature' => 'warm',
        ])->assertRedirect();

        $lead = Lead::firstOrFail();

        $this->assertSame([$extra->id, $this->package->id], $lead->servicePackages->pluck('id')->all());

        $this->put(route('leads.update', $lead), [
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Dua Layanan',
            'contact_name' => 'Budi',
            'service_package_ids' => [$this->package->id],
            'estimated_value' => 750_000,
            'status' => 'open',
            'temperature' => 'warm',
        ])->assertRedirect();

        $this->assertSame([$this->package->id], $lead->fresh()->servicePackages->pluck('id')->all());
    }

    public function test_an_unknown_service_package_is_rejected(): void
    {
        $this->post(route('leads.store'), [
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Layanan Hantu',
            'contact_name' => 'Budi',
            'service_package_ids' => [99999],
            'estimated_value' => 0,
            'status' => 'open',
            'temperature' => 'cold',
        ])->assertSessionHasErrors('service_package_ids.0');

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_temperature_only_accepts_cold_warm_or_hot(): void
    {
        $this->post(route('leads.store'), [
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Panas',
            'contact_name' => 'Budi',
            'estimated_value' => 0,
            'status' => 'open',
            'temperature' => 'panas',
        ])->assertSessionHasErrors('temperature');

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_date_in_defaults_to_today(): void
    {
        Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Tanpa Tanggal',
            'contact_name' => 'Budi',
        ]);

        $this->assertSame(now()->toDateString(), Lead::firstOrFail()->date_in->toDateString());
    }

    public function test_table_tab_carries_the_sheet_columns_and_last_invoice(): void
    {
        $client = Client::create(['company_name' => 'PT Kopi', 'short_code' => 'KOPI']);

        Invoice::create([
            'number' => 'IM-INV-0001', 'client_id' => $client->id,
            'issue_date' => '2026-01-01', 'due_date' => '2026-02-01', 'total' => 100,
        ]);
        Invoice::create([
            'number' => 'IM-INV-0002', 'client_id' => $client->id,
            'issue_date' => '2026-03-01', 'due_date' => '2026-04-01', 'total' => 200,
        ]);

        Lead::create([
            'lead_stage_id' => $this->stage->id,
            'company_name' => 'PT Kopi',
            'contact_name' => 'Dewi',
            'industry' => 'F&B',
            'region' => 'Banjarmasin',
            'pic' => 'Rahim',
            'next_action' => 'Follow up',
            'next_action_date' => '2026-09-10',
            'folder_url' => 'https://drive.google.com/x',
            'temperature' => 'warm',
            'converted_client_id' => $client->id,
        ]);

        Lead::firstOrFail()->servicePackages()->attach($this->package->id, ['position' => 0]);

        $this->get(route('leads.index', ['tab' => 'table']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('services')
                ->where('stageTables.0.leads.0.industry', 'F&B')
                ->where('stageTables.0.leads.0.region', 'Banjarmasin')
                ->where('stageTables.0.leads.0.pic', 'Rahim')
                ->where('stageTables.0.leads.0.service_packages.0.name', 'Paket NIB')
                ->where('stageTables.0.leads.0.last_invoice.number', 'IM-INV-0002')
                ->where('stageTables.0.leads.0.next_action', 'Follow up')
                ->where('stageTables.0.leads.0.folder_url', 'https://drive.google.com/x')
                ->where('stageTables.0.leads.0.temperature', 'warm')
                ->etc());
    }

    public function test_table_tab_sorts_by_the_new_date_columns(): void
    {
        Lead::create([
            'lead_stage_id' => $this->stage->id, 'company_name' => 'PT Lama',
            'contact_name' => 'A', 'date_in' => '2026-01-01',
        ]);
        Lead::create([
            'lead_stage_id' => $this->stage->id, 'company_name' => 'PT Baru',
            'contact_name' => 'B', 'date_in' => '2026-08-01',
        ]);

        $this->get(route('leads.index', ['tab' => 'table', 'sort' => 'date_in', 'direction' => 'desc']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stageTables.0.leads.0.company_name', 'PT Baru')
                ->etc());
    }

    public function test_csv_export_uses_the_sheet_header(): void
    {
        $lead = Lead::create([
            'lead_stage_id' => $this->stage->id, 'company_name' => 'PT Ekspor',
            'contact_name' => 'C',
        ]);

        $lead->servicePackages()->attach($this->package->id, ['position' => 0]);

        $response = $this->get(route('leads.export', ['ids' => [$lead->id]]));
        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Temperature', $csv);
        $this->assertStringContainsString('Link Folder', $csv);
        $this->assertStringContainsString('Paket NIB', $csv);
    }

    public function test_dashboard_lists_overdue_next_actions(): void
    {
        Lead::create([
            'lead_stage_id' => $this->stage->id, 'company_name' => 'PT Telat',
            'contact_name' => 'D', 'status' => 'open',
            'next_action' => 'Telepon ulang', 'next_action_date' => now()->subDays(3)->toDateString(),
        ]);

        $attention = DashboardAttention::attention();

        $this->assertSame('PT Telat', $attention[0]['title']);
        $this->assertSame('Telepon ulang · Prospek', $attention[0]['subtitle']);
        $this->assertSame(now()->subDays(3)->toDateString(), $attention[0]['date']);
    }
}
