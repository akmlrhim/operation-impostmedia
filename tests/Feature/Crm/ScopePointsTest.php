<?php

namespace Tests\Feature\Crm;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\CrmMasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ScopePointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmMasterDataSeeder::class);
        $this->actingAs(User::factory()->create());

        config([
            'services.groq.key' => 'kunci-tes',
            'services.groq.model' => 'model-tes',
            'services.groq.light_model' => 'model-tes',
            'services.groq.reasoning_effort' => 'low',
            'services.groq.reasoning_models' => ['model-tes'],
        ]);
    }

    public function test_it_writes_points_from_the_line_and_its_catalog_package(): void
    {
        $this->fakeGroq(['Kelola 3 platform media sosial', '8 konten feed design per bulan']);

        $package = $this->makeServicePackage();
        $client = $this->makeClient();

        $response = $this->postJson(route('contracts.scope-points'), [
            'name' => 'Social Media Management — Silver',
            'service_package_id' => $package->id,
            'client_id' => $client->id,
            'title' => 'Pengelolaan Media Sosial 2026',
        ]);

        $response->assertOk()->assertJson([
            'points' => ['Kelola 3 platform media sosial', '8 konten feed design per bulan'],
        ]);

        Http::assertSent(function (Request $request) use ($package, $client): bool {
            $prompt = $request['messages'][1]['content'];

            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer kunci-tes')
                && $request['model'] === 'model-tes'
                && $request['response_format']['type'] === 'json_object'
                && str_contains($prompt, 'Social Media Management — Silver')
                && str_contains($prompt, $package->name)
                && str_contains($prompt, $client->company_name);
        });
    }

    public function test_it_strips_bullets_and_drops_duplicate_points(): void
    {
        $this->fakeGroq([
            '- Kelola 3 platform media sosial',
            '1. Report bulanan',
            '• Report bulanan',
            '   ',
            42,
        ]);

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])
            ->assertOk()
            ->assertExactJson(['points' => ['Kelola 3 platform media sosial', 'Report bulanan']]);
    }

    public function test_it_never_returns_more_points_than_a_mou_row_can_carry(): void
    {
        $this->fakeGroq(array_map(fn (int $i): string => "Poin nomor {$i}", range(1, 20)));

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])
            ->assertOk()
            ->assertJsonCount(8, 'points');
    }

    public function test_a_line_without_a_description_has_nothing_to_write_from(): void
    {
        Http::fake();

        $this->postJson(route('contracts.scope-points'), ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        Http::assertNothingSent();
    }

    public function test_without_a_key_the_button_is_hidden_and_the_endpoint_stays_off(): void
    {
        Http::fake();
        config(['services.groq.key' => null]);

        $this->get(route('contracts.create'))->assertInertia(
            fn (AssertableInertia $page) => $page->where('aiScopePoints', false),
        );

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'GROQ_API_KEY'));

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'groq.com'));
    }

    public function test_groq_being_down_only_cancels_the_help_not_the_mou(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);

        $this->get(route('contracts.create'))->assertOk();
    }

    public function test_an_answer_without_usable_points_is_reported_instead_of_saved(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"catatan":"tidak paham"}']]],
            ]),
        ]);

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])
            ->assertUnprocessable()
            ->assertJsonStructure(['message']);
    }

    public function test_groq_rejecting_the_request_is_reported_with_its_status(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'bad key'], 401)]);

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, '401'));
    }

    /**
     * Jatah harian Groq habis itu keadaan yang wajar dan bakal sering kena, jadi
     * yang dibaca orang kantor harus kalimat biasa, bukan kode status.
     */
    public function test_a_spent_daily_quota_is_explained_in_plain_words(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'rate limited'], 429)]);

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'habis')
                && ! str_contains($message, '429'));
    }

    public function test_the_same_line_is_only_paid_for_once(): void
    {
        $this->fakeGroq(['Kelola 3 platform media sosial']);

        $payload = ['name' => 'Social Media Management'];

        $this->postJson(route('contracts.scope-points'), $payload)->assertOk();
        $this->postJson(route('contracts.scope-points'), $payload)
            ->assertOk()
            ->assertJson(['points' => ['Kelola 3 platform media sosial']]);

        Http::assertSentCount(1);
    }

    /**
     * groq/compound menolak reasoning_effort dengan 400, jadi parameter itu
     * tidak boleh ikut terkirim begitu modelnya diganti ke compound.
     */
    public function test_a_model_that_does_not_think_is_not_sent_the_thinking_dial(): void
    {
        config(['services.groq.light_model' => 'groq/compound']);
        $this->fakeGroq(['Kelola 3 platform media sosial']);

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])->assertOk();

        Http::assertSent(fn (Request $request): bool => $request['model'] === 'groq/compound'
            && ! isset($request['reasoning_effort'])
            && $request['max_completion_tokens'] === 800);
    }

    public function test_a_model_the_key_may_not_touch_says_so_by_name(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'blocked'], 403)]);

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'model-tes')
                && str_contains($message, 'belum diizinkan'));
    }

    public function test_the_request_is_capped_so_a_runaway_answer_cannot_drain_the_quota(): void
    {
        $this->fakeGroq(['Kelola 3 platform media sosial']);

        $this->postJson(route('contracts.scope-points'), ['name' => 'Social Media Management'])->assertOk();

        Http::assertSent(fn (Request $request): bool => $request['max_completion_tokens'] === 800
            && $request['reasoning_effort'] === 'low');
    }

    /**
     * @param  array<int, mixed>  $points
     */
    private function fakeGroq(array $points): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['points' => $points])]],
                ],
            ]),
        ]);
    }

    private function makeClient(string $companyName = 'PT Kopi Nusantara'): Client
    {
        return Client::create([
            'short_code' => Client::generateShortCode($companyName),
            'company_name' => $companyName,
            'contact_name' => 'PIC '.$companyName,
        ]);
    }
}
