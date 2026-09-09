<?php

namespace App\Actions\Crm;

use App\Enums\ContractType;
use App\Models\CompanySetting;
use App\Models\Contract;
use App\Support\CompanyProfile;
use App\Support\Documents\BlockRenderer;
use App\Support\Documents\DocumentVariables;
use App\Support\Documents\MouTemplate;

class RenderContractDocument
{
    public function __construct(private BlockRenderer $renderer) {}

    public function handle(Contract $contract, bool $persist = true): string
    {
        $contract->load(['items.servicePackage', 'client']);

        $html = $contract->type === ContractType::Mou
            ? $this->fromMouTemplate($contract)
            : $this->legacy($contract);

        if ($persist) {
            $contract->cacheRenderedBody($html);
        }

        return $html;
    }

    public function editable(Contract $contract): string
    {
        $edited = $contract->editedBody();

        if ($edited !== null) {
            return $edited;
        }

        $contract->load(['items.servicePackage', 'client']);

        if ($contract->type !== ContractType::Mou) {
            return $this->legacy($contract);
        }

        return $this->renderer->body(
            MouTemplate::blocks($contract),
            MouTemplate::settings(),
            DocumentVariables::forContract($contract),
            $this->images($contract),
            $contract,
            $contract->aiClausePoints(),
        );
    }

    public function editorPage(Contract $contract, string $body): string
    {
        $contract->loadMissing('client');

        return $this->renderer->page(
            $body,
            $contract->type === ContractType::Mou ? MouTemplate::settings() : [],
            DocumentVariables::forContract($contract),
            $this->images($contract),
            preview: true,
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function images(Contract $contract): array
    {
        return [
            ...CompanySetting::current()->documentImages(),
            'client_signature' => $contract->signatureData(),
        ];
    }

    private function fromMouTemplate(Contract $contract): string
    {
        $edited = $contract->editedBody();

        if ($edited !== null) {
            return $this->renderer->page(
                $edited,
                MouTemplate::settings(),
                DocumentVariables::forContract($contract),
                $this->images($contract),
            );
        }

        return $this->renderer->render(
            MouTemplate::blocks($contract),
            MouTemplate::settings(),
            DocumentVariables::forContract($contract),
            $this->images($contract),
            $contract,
            clauses: $contract->aiClausePoints(),
        );
    }

    private function legacy(Contract $contract): string
    {
        return view('documents.mou', [
            'contract' => $contract,
            'client' => $contract->client,
            'company' => CompanySetting::current(),
            'profile' => CompanyProfile::all(),
        ])->render();
    }
}
